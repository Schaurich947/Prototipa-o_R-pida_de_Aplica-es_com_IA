<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Room;

final readonly class RoomOffer
{
    public function __construct(
        public Hotel $hotel,
        public Room $room,
        public int $nights,
    ) {
    }

    public function totalCents(): int
    {
        return $this->room->dailyRateCents * $this->nights;
    }
}
