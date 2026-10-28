<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Availability;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\UserRepository;

final class StayViewFactory
{
    /** Caches live only for one HTTP request. @var array<int, Hotel> */
    private array $hotelCache = [];
    /** @var array<int, Room> */
    private array $roomCache = [];
    /** @var array<int, string> */
    private array $guestNameCache = [];

    public function __construct(
        private readonly HotelRepository $hotels,
        private readonly RoomRepository $rooms,
        private readonly ReservationRepository $reservations,
        private readonly UserRepository $users,
    ) {
    }

    public function build(Reservation $reservation): StayView
    {
        $room = $this->getRoom($reservation->roomId);
        $hotel = $this->getHotel($room->hotelId);
        $previous = $this->reservations->findDepartureOn($room->id, $reservation->checkInDate, $reservation->id);
        $next = $this->reservations->findArrivalOn($room->id, $reservation->checkOutDate(), $reservation->id);

        return new StayView(
            $reservation,
            $room,
            $hotel,
            Availability::getMaxEarlyCheckInHours($hotel, $reservation, $previous),
            Availability::getMaxLateCheckoutHours($hotel, $reservation, $next),
            $this->getGuestName($reservation->userId),
        );
    }

    /** @param list<Reservation> $reservations @return list<StayView> */
    public function buildMany(array $reservations): array
    {
        return array_map($this->build(...), $reservations);
    }

    public function getHotel(int $hotelId): Hotel
    {
        return $this->hotelCache[$hotelId] ??= $this->hotels->findById($hotelId) ?? throw new DomainException('Hotel não encontrado.');
    }

    private function getGuestName(int $userId): string
    {
        return $this->guestNameCache[$userId] ??= $this->users->findById($userId)?->name ?? 'Hóspede';
    }

    private function getRoom(int $roomId): Room
    {
        return $this->roomCache[$roomId] ??= $this->rooms->findById($roomId) ?? throw new DomainException('Quarto não encontrado.');
    }
}
