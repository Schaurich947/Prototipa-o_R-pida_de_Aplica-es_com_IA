<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/**
 * Seats left for a breakfast day. Hotel guests come first: every room occupied that
 * morning takes the hotel's estimated seats per room before any standalone sale.
 */
final readonly class BreakfastAvailability
{
    public int $guestSeats;
    public int $freeSeats;

    public function __construct(
        public int $capacity,
        public int $occupiedRooms,
        int $seatsPerRoom,
        public int $bookedSeats,
    ) {
        $this->guestSeats = $occupiedRooms * $seatsPerRoom;
        $this->freeSeats = max(0, $capacity - $this->guestSeats - $bookedSeats);
    }
}
