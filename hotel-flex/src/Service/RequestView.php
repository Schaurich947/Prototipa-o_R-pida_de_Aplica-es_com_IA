<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\ServiceRequest;

final readonly class RequestView
{
    public function __construct(
        public ServiceRequest $request,
        public Reservation $reservation,
        public Room $room,
    ) {
    }

    /** Fee is recalculated from the room rate, never taken from what the client sent. */
    public function feeCents(): int
    {
        return $this->request->extraHours * $this->room->hourlyRateCents;
    }
}
