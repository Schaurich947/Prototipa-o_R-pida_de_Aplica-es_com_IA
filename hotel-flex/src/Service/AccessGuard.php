<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\ServiceRequest;
use HotelFlex\Domain\User;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;

/**
 * Server-side authorization: guests only touch their own reservations and staff
 * only their own hotel. Missing and forbidden records get the same message,
 * so ids from other accounts cannot be probed.
 */
final class AccessGuard
{
    private const NOT_FOUND = 'Registro não encontrado.';

    public function __construct(
        private readonly ReservationRepository $reservations,
        private readonly RoomRepository $rooms,
        private readonly ServiceRequestRepository $requests,
    ) {
    }

    public function getOwnReservation(User $actor, int $reservationId): Reservation
    {
        $reservation = $this->reservations->findById($reservationId);
        if ($reservation === null || !$actor->canAccessReservation($reservation)) {
            throw new DomainException(self::NOT_FOUND);
        }

        return $reservation;
    }

    public function getManagedRoom(User $actor, int $roomId): Room
    {
        $room = $this->rooms->findById($roomId);
        if ($room === null || !$actor->canManageHotel($room->hotelId)) {
            throw new DomainException(self::NOT_FOUND);
        }

        return $room;
    }

    public function getManagedReservation(User $actor, int $reservationId): Reservation
    {
        $reservation = $this->reservations->findById($reservationId) ?? throw new DomainException(self::NOT_FOUND);
        $this->getManagedRoom($actor, $reservation->roomId);

        return $reservation;
    }

    public function getManagedRequest(User $actor, int $requestId): ServiceRequest
    {
        $request = $this->requests->findById($requestId) ?? throw new DomainException(self::NOT_FOUND);
        $this->getManagedReservation($actor, $request->reservationId);

        return $request;
    }
}
