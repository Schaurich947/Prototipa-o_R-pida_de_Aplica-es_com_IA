<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\Availability;
use HotelFlex\Domain\BillingSummary;
use HotelFlex\Domain\Pricing;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;

/** Read model combining a room with its reservation, ready for the screens. */
final readonly class RoomView
{
    public bool $isEarlyVacant;
    public int $maxLateCheckoutHours;
    public ?BillingSummary $billing;

    public function __construct(
        public Room $room,
        public ?Reservation $reservation,
    ) {
        $this->isEarlyVacant = Availability::isEarlyVacant($room, $reservation);
        $this->maxLateCheckoutHours = Availability::getMaxLateCheckoutHours($room);
        $this->billing = $reservation ? Pricing::calculateBillingSummary($room, $reservation) : null;
    }
}
