<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

/**
 * Flexible hours are limited by the hotel policy and by the neighbouring stays
 * in the same room, keeping the cleaning buffer between them.
 */
final class Availability
{
    public static function isEarlyVacant(Hotel $hotel, Room $room, ?Reservation $arrival, DateTimeImmutable $today): bool
    {
        $isArrivingToday = $arrival !== null
            && $arrival->status === ReservationStatus::Confirmed
            && $arrival->checkInDate->format('Y-m-d') === $today->format('Y-m-d');
        if (!$isArrivingToday || $room->status !== RoomStatus::Clean || $room->cleanedAt === null) {
            return false;
        }

        return $room->cleanedAt < $arrival->checkInDate->setTime($hotel->checkInHour, 0);
    }

    public static function getMaxEarlyCheckInHours(Hotel $hotel, Reservation $reservation, ?Reservation $previousDeparture): int
    {
        $lowerBound = $reservation->checkInDate->setTime($hotel->earliestCheckInHour, 0);
        if ($previousDeparture !== null) {
            $lowerBound = max($lowerBound, self::withBuffer($hotel, $previousDeparture->endsAt($hotel)));
        }

        return self::countWholeHours($lowerBound, $reservation->checkInDate->setTime($hotel->checkInHour, 0));
    }

    public static function getMaxLateCheckoutHours(Hotel $hotel, Reservation $reservation, ?Reservation $nextArrival): int
    {
        $upperBound = $reservation->checkOutDate()->setTime($hotel->latestCheckOutHour, 0);
        if ($nextArrival !== null) {
            $upperBound = min($upperBound, self::withBuffer($hotel, $nextArrival->startsAt($hotel), -1));
        }

        return self::countWholeHours($reservation->checkOutDate()->setTime($hotel->checkOutHour, 0), $upperBound);
    }

    /** @param list<Reservation> $otherStays active stays in the same room */
    public static function hasConflict(Hotel $hotel, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt, array $otherStays): bool
    {
        foreach ($otherStays as $other) {
            $otherStartsBeforeEnd = $other->startsAt($hotel) < self::withBuffer($hotel, $endsAt);
            $otherEndsAfterStart = self::withBuffer($hotel, $other->endsAt($hotel)) > $startsAt;
            if ($otherStartsBeforeEnd && $otherEndsAfterStart) {
                return true;
            }
        }

        return false;
    }

    public static function assertHoursWithinLimit(int $hours, int $maxHours): void
    {
        if ($hours < 0) {
            throw new DomainException('A quantidade de horas não pode ser negativa.');
        }
        if ($hours > $maxHours) {
            throw new DomainException("Disponível no máximo {$maxHours} hora(s) para esta reserva.");
        }
    }

    private static function withBuffer(Hotel $hotel, DateTimeImmutable $moment, int $direction = 1): DateTimeImmutable
    {
        return $moment->modify(sprintf('%+d hours', $direction * $hotel->cleaningBufferHours));
    }

    private static function countWholeHours(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return max(0, intdiv($to->getTimestamp() - $from->getTimestamp(), 3600));
    }
}
