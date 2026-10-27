<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RequestStatus;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\RoomStatus;
use HotelFlex\Domain\ServiceRequest;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;

final class RequestService
{
    public function __construct(
        private readonly ServiceRequestRepository $requests,
        private readonly ReservationRepository $reservations,
        private readonly RoomRepository $rooms,
        private readonly RoomService $roomService,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    public function createRequest(int $reservationId, RequestType $type, int $extraHours = 0): void
    {
        $reservation = $this->getReservation($reservationId);
        if ($this->requests->hasPending($reservationId, $type)) {
            throw new DomainException('Já existe uma solicitação pendente deste tipo.');
        }
        $this->assertRequestIsValid($reservation, $type, $extraHours);
        $hours = $type === RequestType::LateCheckout ? $extraHours : 0;
        $this->requests->create($reservationId, $type, $hours, $this->clock->now());
    }

    public function approveRequest(int $requestId): void
    {
        $this->transaction->run(function () use ($requestId): void {
            $request = $this->getPendingRequest($requestId);
            $this->applyApproval($request);
            $this->markResolved($requestId, RequestStatus::Approved);
        });
    }

    public function rejectRequest(int $requestId): void
    {
        $this->getPendingRequest($requestId);
        $this->markResolved($requestId, RequestStatus::Rejected);
    }

    private function assertRequestIsValid(Reservation $reservation, RequestType $type, int $extraHours): void
    {
        $room = $this->rooms->findById($reservation->roomId) ?? throw new DomainException('Quarto não encontrado.');
        if ($type === RequestType::LateCheckout) {
            Availability::assertValidLateCheckoutHours($room, $extraHours);
            if ($extraHours === 0) {
                throw new DomainException('Selecione ao menos 1 hora adicional.');
            }

            return;
        }
        if (!Availability::isEarlyVacant($room, $reservation)) {
            throw new DomainException('O quarto ainda não está disponível para entrada antecipada.');
        }
    }

    private function applyApproval(ServiceRequest $request): void
    {
        if ($request->type === RequestType::LateCheckout) {
            $this->roomService->setLateCheckoutHours($request->reservationId, $request->extraHours);

            return;
        }
        $roomId = $this->getReservation($request->reservationId)->roomId;
        $isAlreadyReleased = $this->rooms->findById($roomId)?->status === RoomStatus::Released;
        if (!$isAlreadyReleased) {
            $this->roomService->releaseRoomToGuest($roomId);
        }
    }

    private function getReservation(int $reservationId): Reservation
    {
        return $this->reservations->findById($reservationId) ?? throw new DomainException('Reserva não encontrada.');
    }

    private function getPendingRequest(int $requestId): ServiceRequest
    {
        $request = $this->requests->findById($requestId) ?? throw new DomainException('Solicitação não encontrada.');
        if (!$request->isPending()) {
            throw new DomainException('Esta solicitação já foi respondida.');
        }

        return $request;
    }

    private function markResolved(int $requestId, RequestStatus $status): void
    {
        if (!$this->requests->resolve($requestId, $status, $this->clock->now())) {
            throw new DomainException('Esta solicitação já foi respondida.');
        }
    }
}
