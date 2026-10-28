<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\RoomStatus;
use HotelFlex\Domain\User;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;

/** Daily hotel operation: housekeeping, early release, check-in/out and flexible hours. */
final class RoomService
{
    public function __construct(
        private readonly RoomRepository $rooms,
        private readonly ReservationRepository $reservations,
        private readonly ServiceRequestRepository $requests,
        private readonly AccessGuard $guard,
        private readonly StayViewFactory $stays,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    public function startCleaning(User $actor, int $roomId): void
    {
        $this->changeStatus($this->guard->getManagedRoom($actor, $roomId), RoomStatus::Cleaning);
    }

    public function markRoomClean(User $actor, int $roomId): void
    {
        $this->changeStatus($this->guard->getManagedRoom($actor, $roomId), RoomStatus::Clean);
    }

    public function releaseRoomToGuest(User $actor, int $roomId): void
    {
        $room = $this->guard->getManagedRoom($actor, $roomId);
        $arrival = $this->reservations->findArrivalOn($roomId, $this->today(), 0);
        if (!Availability::isEarlyVacant($this->stays->getHotel($room->hotelId), $room, $arrival, $this->today())) {
            throw new DomainException('O quarto precisa estar limpo antes do horário de check-in para ser liberado.');
        }
        $this->changeStatus($room, RoomStatus::Released);
    }

    public function checkIn(User $actor, int $reservationId): void
    {
        $reservation = $this->guard->getManagedReservation($actor, $reservationId);
        $room = $this->guard->getManagedRoom($actor, $reservation->roomId);
        if ($reservation->checkInDate > $this->today()) {
            throw new DomainException('O check-in só pode ser feito a partir da data da reserva.');
        }
        if ($room->status !== RoomStatus::Clean && $room->status !== RoomStatus::Released) {
            throw new DomainException('O quarto ainda não está pronto para receber o hóspede.');
        }
        $this->transaction->run(function () use ($reservation, $room): void {
            $this->changeReservationStatus($reservation->id, ReservationStatus::Confirmed, ReservationStatus::CheckedIn);
            $this->updateRoomStatus($room, RoomStatus::Occupied);
        });
    }

    public function checkOut(User $actor, int $reservationId): void
    {
        $reservation = $this->guard->getManagedReservation($actor, $reservationId);
        $room = $this->guard->getManagedRoom($actor, $reservation->roomId);
        $this->transaction->run(function () use ($reservation, $room): void {
            $this->changeReservationStatus($reservation->id, ReservationStatus::CheckedIn, ReservationStatus::CheckedOut);
            $this->updateRoomStatus($room, RoomStatus::Dirty);
            $this->requests->cancelPendingForReservation($reservation->id, $this->clock->now());
        });
    }

    /** Reception can set agreed hours directly (e.g. at the front desk). */
    public function setFlexibleHours(User $actor, int $reservationId, RequestType $type, int $hours): void
    {
        $stay = $this->stays->build($this->guard->getManagedReservation($actor, $reservationId));
        if (!$stay->reservation->status->isActive()) {
            throw new DomainException('Só é possível ajustar horários de reservas ativas.');
        }
        $stay->assertHoursAllowed($type, $hours);
        $type === RequestType::EarlyCheckIn
            ? $this->reservations->updateEarlyCheckInHours($reservationId, $hours)
            : $this->reservations->updateLateCheckoutHours($reservationId, $hours);
    }

    private function changeStatus(Room $room, RoomStatus $nextStatus): void
    {
        if (!$room->status->canTransitionTo($nextStatus)) {
            throw new DomainException('Esta mudança de status não é permitida para o quarto.');
        }
        $this->updateRoomStatus($room, $nextStatus);
    }

    private function updateRoomStatus(Room $room, RoomStatus $nextStatus): void
    {
        $cleanedAt = $nextStatus === RoomStatus::Clean ? $this->clock->now() : null;
        if (!$this->rooms->updateStatus($room->id, $room->status, $nextStatus, $cleanedAt)) {
            throw new DomainException('O quarto foi atualizado por outra pessoa. Confira o painel e tente novamente.');
        }
    }

    private function changeReservationStatus(int $reservationId, ReservationStatus $from, ReservationStatus $to): void
    {
        if (!$this->reservations->updateStatus($reservationId, $from, $to)) {
            throw new DomainException('A reserva não está no status esperado para esta ação.');
        }
    }

    private function today(): DateTimeImmutable
    {
        return $this->clock->now()->setTime(0, 0);
    }
}
