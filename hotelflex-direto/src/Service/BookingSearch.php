<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\Room;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;

/** Finds free rooms in every hotel of the platform for the requested nights. */
final class BookingSearch
{
    public function __construct(
        private readonly RoomRepository $rooms,
        private readonly ReservationRepository $reservations,
        private readonly HotelRepository $hotels,
        private readonly StayViewFactory $stays,
    ) {
    }

    /** @return list<string> */
    public function getCities(): array
    {
        return $this->hotels->findCities();
    }

    /** @return list<RoomOffer> */
    public function search(DateTimeImmutable $checkInDate, int $nights, string $city): array
    {
        $checkOutDate = $checkInDate->modify("+{$nights} days");
        $offers = [];
        foreach ($this->rooms->findFreeForDates($checkInDate, $checkOutDate, $city) as $room) {
            if ($this->fitsBetweenNeighbours($room, $checkInDate, $checkOutDate)) {
                $offers[] = new RoomOffer($this->stays->getHotel($room->hotelId), $room, $nights);
            }
        }

        return $offers;
    }

    /** Same-day turnovers are allowed only if flexible hours plus cleaning still fit. */
    private function fitsBetweenNeighbours(Room $room, DateTimeImmutable $checkInDate, DateTimeImmutable $checkOutDate): bool
    {
        $hotel = $this->stays->getHotel($room->hotelId);
        $neighbours = $this->reservations->findStaysNear($room->id, $checkInDate, $checkOutDate);

        return !Availability::hasConflict(
            $hotel,
            $checkInDate->setTime($hotel->checkInHour, 0),
            $checkOutDate->setTime($hotel->checkOutHour, 0),
            $neighbours,
        );
    }
}
