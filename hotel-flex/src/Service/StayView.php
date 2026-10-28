<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\BillingSummary;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Pricing;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;

/** Read model of one stay: reservation + room + hotel with the limits already computed. */
final readonly class StayView
{
    public BillingSummary $billing;

    public function __construct(
        public Reservation $reservation,
        public Room $room,
        public Hotel $hotel,
        public int $maxEarlyCheckInHours,
        public int $maxLateCheckoutHours,
        public string $guestName,
    ) {
        $this->billing = Pricing::calculateBillingSummary($hotel, $room, $reservation);
    }

    public function startsAt(): DateTimeImmutable
    {
        return $this->reservation->startsAt($this->hotel);
    }

    public function endsAt(): DateTimeImmutable
    {
        return $this->reservation->endsAt($this->hotel);
    }

    public function getMaxHours(RequestType $type): int
    {
        return $type === RequestType::EarlyCheckIn ? $this->maxEarlyCheckInHours : $this->maxLateCheckoutHours;
    }

    public function getCurrentHours(RequestType $type): int
    {
        return $type === RequestType::EarlyCheckIn ? $this->reservation->earlyCheckInHours : $this->reservation->lateCheckoutHours;
    }

    public function calculateFee(RequestType $type, int $hours): int
    {
        return $type === RequestType::EarlyCheckIn
            ? Pricing::calculateEarlyCheckInFee($this->hotel, $this->room, $hours)
            : Pricing::calculateLateCheckoutFee($this->room, $hours);
    }

    public function assertHoursAllowed(RequestType $type, int $hours): void
    {
        Availability::assertHoursWithinLimit($hours, $this->getMaxHours($type));
    }
}
