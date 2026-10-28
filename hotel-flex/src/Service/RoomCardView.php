<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Room;

/** One room in the hotel panel: who arrives today and who is staying now. */
final readonly class RoomCardView
{
    public function __construct(
        public Room $room,
        public ?StayView $arrival,
        public ?StayView $inHouse,
        public bool $isEarlyVacant,
    ) {
    }

    public function additionalRevenueCents(): int
    {
        return ($this->arrival?->billing->additionalCents() ?? 0) + ($this->inHouse?->billing->additionalCents() ?? 0);
    }
}
