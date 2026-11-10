<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

/** Free cancellation until the hotel's limit before check-in; after that, what was paid is kept. */
final class CancellationPolicy
{
    public static function getFreeCancellationDeadline(Hotel $hotel, Reservation $reservation): DateTimeImmutable
    {
        return $reservation->checkInDate
            ->setTime($hotel->checkInHour, 0)
            ->modify("-{$hotel->freeCancellationHours} hours");
    }

    public static function isRefundable(Hotel $hotel, Reservation $reservation, DateTimeImmutable $now): bool
    {
        return $now <= self::getFreeCancellationDeadline($hotel, $reservation);
    }
}
