<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

final class Pricing
{
    /** The hotel's tolerance hours are free; only the hours beyond it are charged. */
    public static function calculateEarlyCheckInFee(Hotel $hotel, Room $room, int $hours): int
    {
        return max(0, $hours - $hotel->earlyFreeHours) * $room->hourlyRateCents;
    }

    public static function calculateLateCheckoutFee(Room $room, int $hours): int
    {
        return max(0, $hours) * $room->hourlyRateCents;
    }

    public static function calculateBillingSummary(Hotel $hotel, Room $room, Reservation $reservation): BillingSummary
    {
        return new BillingSummary(
            $room->dailyRateCents * $reservation->nights,
            self::calculateEarlyCheckInFee($hotel, $room, $reservation->earlyCheckInHours),
            self::calculateLateCheckoutFee($room, $reservation->lateCheckoutHours),
        );
    }
}
