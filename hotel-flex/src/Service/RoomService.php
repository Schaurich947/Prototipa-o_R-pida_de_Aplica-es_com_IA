<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Availability;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\RoomStatus;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;

final class RoomService
{
    public function __construct(
        private readonly RoomRepository $rooms,
        private readonly ReservationRepository $reservations,
        private readonly Clock $clock,
    ) {
    }

    public function startCleaning(int $roomId): void
    {
        $this->changeStatus($this->getRoom($roomId), RoomStatus::Cleaning);
    }

    public function markRoomClean(int $roomId): void
    {
        $this->changeStatus($this->getRoom($roomId), RoomStatus::Clean);
    }

    public function releaseRoomToGuest(int $roomId): void
    {
        $room = $this->getRoom($roomId);
        if (!Availability::isEarlyVacant($room, $this->reservations->findByRoomId($roomId))) {
            throw new DomainException('O quarto precisa estar limpo antes do horário de check-in para ser liberado.');
        }
        $this->changeStatus($room, RoomStatus::Released);
    }

    public function setLateCheckoutHours(int $reservationId, int $hours): void
    {
        $reservation = $this->reservations->findById($reservationId)
            ?? throw new DomainException('Reserva não encontrada.');
        Availability::assertValidLateCheckoutHours($this->getRoom($reservation->roomId), $hours);
        $this->reservations->updateLateCheckoutHours($reservationId, $hours);
    }

    private function getRoom(int $roomId): Room
    {
        return $this->rooms->findById($roomId) ?? throw new DomainException('Quarto não encontrado.');
    }

    private function changeStatus(Room $room, RoomStatus $nextStatus): void
    {
        if (!$room->status->canTransitionTo($nextStatus)) {
            throw new DomainException('Esta mudança de status não é permitida para o quarto.');
        }
        $cleanedAt = $nextStatus === RoomStatus::Clean ? $this->clock->now() : null;
        if (!$this->rooms->updateStatus($room->id, $room->status, $nextStatus, $cleanedAt)) {
            throw new DomainException('O quarto foi atualizado por outra pessoa. Confira o painel e tente novamente.');
        }
    }
}
