<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Availability;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\User;
use HotelFlex\Repository\ChargeRepository;
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
    /** @var array<int, User|null> */
    private array $guestCache = [];

    public function __construct(
        private readonly HotelRepository $hotels,
        private readonly RoomRepository $rooms,
        private readonly ReservationRepository $reservations,
        private readonly UserRepository $users,
        private readonly ?ChargeRepository $charges = null,
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
            $this->getGuest($reservation->userId)?->name ?? 'Hóspede',
            $this->getGuest($reservation->userId)?->whatsappPhone,
            $reservation->id > 0 ? (int) $this->charges?->sumOpen($reservation->id) : 0,
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

    private function getGuest(int $userId): ?User
    {
        if (!array_key_exists($userId, $this->guestCache)) {
            $this->guestCache[$userId] = $this->users->findById($userId);
        }

        return $this->guestCache[$userId];
    }

    private function getRoom(int $roomId): Room
    {
        return $this->roomCache[$roomId] ??= $this->rooms->findById($roomId) ?? throw new DomainException('Quarto não encontrado.');
    }
}
