<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\Room;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;

/** Read models for the hotel screens, indexed by id to avoid nested loops. */
final class HotelPanelQuery
{
    public function __construct(
        private readonly RoomRepository $rooms,
        private readonly ReservationRepository $reservations,
        private readonly ServiceRequestRepository $requests,
        private readonly StayViewFactory $stays,
    ) {
    }

    /** @return list<RoomCardView> */
    public function getRoomCards(Hotel $hotel, DateTimeImmutable $today): array
    {
        $current = $this->reservations->findCurrentForHotel($hotel->id, $today);
        $arrivalsByRoom = $this->indexByRoom(array_filter($current, static fn (Reservation $r) => $r->status === ReservationStatus::Confirmed));
        $inHouseByRoom = $this->indexByRoom(array_filter($current, static fn (Reservation $r) => $r->status === ReservationStatus::CheckedIn));

        return array_map(function (Room $room) use ($hotel, $today, $arrivalsByRoom, $inHouseByRoom): RoomCardView {
            $arrival = $arrivalsByRoom[$room->id] ?? null;
            $inHouse = $inHouseByRoom[$room->id] ?? null;

            return new RoomCardView(
                $room,
                $arrival ? $this->stays->build($arrival) : null,
                $inHouse ? $this->stays->build($inHouse) : null,
                Availability::isEarlyVacant($hotel, $room, $arrival, $today),
            );
        }, $this->rooms->findByHotel($hotel->id));
    }

    /** @return list<RequestView> */
    public function getRequestViews(Hotel $hotel): array
    {
        $views = [];
        foreach ($this->requests->findByHotel($hotel->id) as $request) {
            $reservation = $this->reservations->findById($request->reservationId);
            if ($reservation !== null) {
                $views[] = new RequestView($request, $this->stays->build($reservation));
            }
        }

        return $views;
    }

    /** @param iterable<Reservation> $reservations @return array<int, Reservation> */
    private function indexByRoom(iterable $reservations): array
    {
        $indexed = [];
        foreach ($reservations as $reservation) {
            $indexed[$reservation->roomId] = $reservation;
        }

        return $indexed;
    }
}
