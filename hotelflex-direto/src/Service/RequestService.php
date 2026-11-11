<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RequestStatus;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\User;
use HotelFlex\Repository\ServiceRequestRepository;

/** Guests ask for flexible hours; the hotel approves or rejects them. */
final class RequestService
{
    public function __construct(
        private readonly ServiceRequestRepository $requests,
        private readonly AccessGuard $guard,
        private readonly StayViewFactory $stays,
        private readonly RoomService $roomService,
        private readonly Notifier $notifier,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    public function createRequest(User $actor, int $reservationId, RequestType $type, int $hours): void
    {
        $stay = $this->stays->build($this->guard->getOwnReservation($actor, $reservationId));
        $this->assertCanRequest($stay, $type, $hours);
        if ($this->requests->hasPending($reservationId, $type)) {
            throw new DomainException('Já existe uma solicitação pendente deste tipo para a reserva.');
        }
        $this->requests->create($reservationId, $type, $hours, $this->clock->now());
        $this->notifier->notifyHotelStaff($stay, ReservationMessages::newRequest($stay, mb_strtolower($type->label()), $hours));
    }

    public function approveRequest(User $actor, int $requestId): void
    {
        $request = $this->guard->getManagedRequest($actor, $requestId);
        $this->assertPending($request->status);
        $this->transaction->run(function () use ($actor, $request): void {
            $this->roomService->setFlexibleHours($actor, $request->reservationId, $request->type, $request->extraHours);
            $this->markResolved($request->id, RequestStatus::Approved);
        });
    }

    /** The guest withdraws a pending request (e.g. to ask for a different time). */
    public function cancelRequest(User $actor, int $requestId): int
    {
        $request = $this->requests->findById($requestId) ?? throw new DomainException('Pedido não encontrado.');
        $this->guard->getOwnReservation($actor, $request->reservationId);
        $this->assertPending($request->status);
        $this->markResolved($request->id, RequestStatus::Cancelled);

        return $request->reservationId;
    }

    public function rejectRequest(User $actor, int $requestId): void
    {
        $request = $this->guard->getManagedRequest($actor, $requestId);
        $this->assertPending($request->status);
        $this->markResolved($request->id, RequestStatus::Rejected);
        $stay = $this->stays->build($this->guard->getManagedReservation($actor, $request->reservationId));
        $this->notifier->notifyGuest($stay, ReservationMessages::requestRejected($stay, mb_strtolower($request->type->label())));
    }

    private function assertCanRequest(StayView $stay, RequestType $type, int $hours): void
    {
        $status = $stay->reservation->status;
        if (!$status->isActive()) {
            throw new DomainException('Esta reserva não aceita mais alterações de horário.');
        }
        if ($status === ReservationStatus::PendingPayment) {
            throw new DomainException('Os pedidos de horário ficam disponíveis depois que o pagamento da reserva for confirmado.');
        }
        if ($type === RequestType::EarlyCheckIn && $status === ReservationStatus::CheckedIn) {
            throw new DomainException('A entrada antecipada só pode ser pedida antes do check-in.');
        }
        if ($hours === $stay->getCurrentHours($type)) {
            throw new DomainException('Escolha um horário diferente do atual.');
        }
        $stay->assertHoursAllowed($type, $hours);
    }

    private function assertPending(RequestStatus $status): void
    {
        if ($status !== RequestStatus::Pending) {
            throw new DomainException('Esta solicitação já foi respondida.');
        }
    }

    private function markResolved(int $requestId, RequestStatus $status): void
    {
        if (!$this->requests->resolve($requestId, $status, $this->clock->now())) {
            throw new DomainException('Esta solicitação já foi respondida.');
        }
    }
}
