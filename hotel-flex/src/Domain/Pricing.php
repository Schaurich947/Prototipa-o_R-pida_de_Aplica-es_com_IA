<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

final class Pricing
{
    public static function calculateLateCheckoutFee(Room $room, int $hours): int
    {
        Availability::assertValidLateCheckoutHours($room, $hours);

        return $room->hourlyRateCents * $hours;
    }

    /** Hours were validated when saved, so building a summary never throws while rendering. */
    public static function calculateBillingSummary(Room $room, Reservation $reservation): BillingSummary
    {
        return new BillingSummary(
            $room->dailyRateCents * $reservation->nights,
            $room->hourlyRateCents * $reservation->lateCheckoutHours,
        );
    }
}
