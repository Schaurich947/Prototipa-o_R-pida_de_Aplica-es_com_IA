<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final class Availability
{
    public static function getCheckInDateTime(Reservation $reservation): DateTimeImmutable
    {
        return $reservation->checkInDate->setTime(HotelPolicy::STANDARD_CHECK_IN_HOUR, 0);
    }

    public static function isEarlyVacant(Room $room, ?Reservation $reservation): bool
    {
        if ($reservation === null || $room->status !== RoomStatus::Clean || $room->cleanedAt === null) {
            return false;
        }

        return $room->cleanedAt < self::getCheckInDateTime($reservation);
    }

    public static function getMaxLateCheckoutHours(Room $room): int
    {
        $lastHour = min($room->checkoutLimitHour, HotelPolicy::MAX_CHECK_OUT_HOUR);

        return max(0, $lastHour - HotelPolicy::STANDARD_CHECK_OUT_HOUR);
    }

    public static function getCheckoutHour(int $lateCheckoutHours): int
    {
        return HotelPolicy::STANDARD_CHECK_OUT_HOUR + $lateCheckoutHours;
    }

    public static function assertValidLateCheckoutHours(Room $room, int $hours): void
    {
        if ($hours < 0) {
            throw new DomainException('A quantidade de horas não pode ser negativa.');
        }
        $maxHours = self::getMaxLateCheckoutHours($room);
        if ($hours > $maxHours) {
            throw new DomainException("Este quarto permite no máximo {$maxHours} hora(s) adicionais.");
        }
    }
}
