<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\ServiceRequest;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;

/** Builds read models for the screens using id indexes instead of nested loops. */
final class HotelQueryService
{
    public function __construct(
        private readonly RoomRepository $rooms,
        private readonly ReservationRepository $reservations,
        private readonly ServiceRequestRepository $requests,
    ) {
    }

    /** @return list<RoomView> */
    public function getRoomViews(): array
    {
        $reservationsByRoom = $this->indexBy($this->reservations->findAll(), static fn (Reservation $reservation) => $reservation->roomId);

        return array_map(
            static fn (Room $room) => new RoomView($room, $reservationsByRoom[$room->id] ?? null),
            $this->rooms->findAll(),
        );
    }

    public function getRoomViewForReservation(Reservation $reservation): ?RoomView
    {
        $room = $this->rooms->findById($reservation->roomId);

        return $room ? new RoomView($room, $reservation) : null;
    }

    /** @return list<RequestView> */
    public function getRequestViews(): array
    {
        return $this->buildRequestViews($this->requests->findAll(), $this->reservations->findAll());
    }

    /** @return list<RequestView> */
    public function getRequestViewsForReservation(Reservation $reservation): array
    {
        return $this->buildRequestViews($this->requests->findByReservationId($reservation->id), [$reservation]);
    }

    /** @return list<Reservation> */
    public function getReservations(): array
    {
        return $this->reservations->findAll();
    }

    public function findReservation(int $reservationId): ?Reservation
    {
        return $this->reservations->findById($reservationId);
    }

    /**
     * @param list<ServiceRequest> $requests
     * @param list<Reservation> $reservations
     * @return list<RequestView>
     */
    private function buildRequestViews(array $requests, array $reservations): array
    {
        $reservationsById = $this->indexBy($reservations, static fn (Reservation $reservation) => $reservation->id);
        $roomsById = $this->indexBy($this->rooms->findAll(), static fn (Room $room) => $room->id);
        $views = [];
        foreach ($requests as $request) {
            $reservation = $reservationsById[$request->reservationId] ?? null;
            $room = $reservation ? ($roomsById[$reservation->roomId] ?? null) : null;
            if ($reservation && $room) {
                $views[] = new RequestView($request, $reservation, $room);
            }
        }

        return $views;
    }

    /**
     * @template T
     * @param list<T> $items
     * @param callable(T): int $getKey
     * @return array<int, T>
     */
    private function indexBy(array $items, callable $getKey): array
    {
        $indexed = [];
        foreach ($items as $item) {
            $indexed[$getKey($item)] = $item;
        }

        return $indexed;
    }
}
