<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\StayDates;
use HotelFlex\Domain\User;
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
    public function search(StayDates $dates, string $city): array
    {
        $offers = [];
        foreach ($this->rooms->findFreeForDates($dates->checkInDate, $dates->checkOutDate, $city) as $room) {
            if ($this->fitsBetweenNeighbours($room, $dates->checkInDate, $dates->checkOutDate)) {
                $offers[] = new RoomOffer($this->stays->getHotel($room->hotelId), $room, $dates->nights);
            }
        }

        return $offers;
    }

    /**
     * What the guest is about to book, shown on the review screen before anything is saved.
     * Availability is checked again when the booking is confirmed.
     */
    public function buildPreview(User $guest, int $roomId, StayDates $dates): StayView
    {
        $room = $this->rooms->findById($roomId) ?? throw new DomainException('Quarto não encontrado.');
        if (!$this->fitsBetweenNeighbours($room, $dates->checkInDate, $dates->checkOutDate)) {
            throw new DomainException('Este quarto acabou de ficar indisponível nessas datas. Escolha outro.');
        }
        $draft = new Reservation(0, 'Nova', $guest->id, $room->id, $dates->checkInDate, $dates->nights, 0, 0, ReservationStatus::PendingPayment);

        return $this->stays->build($draft);
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
