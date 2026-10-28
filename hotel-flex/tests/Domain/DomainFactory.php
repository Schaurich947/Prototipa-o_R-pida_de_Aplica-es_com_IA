<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\RoomStatus;

/** Hotel policy used in the tests: entry 14h, exit 12h, window 8h-18h, 1h free, 1h cleaning. */
final class DomainFactory
{
    public static function hotel(int $earlyFreeHours = 1): Hotel
    {
        return new Hotel(1, 'HotelFlex Centro', 'São Paulo', 14, 12, 8, 18, $earlyFreeHours, 1);
    }

    public static function room(RoomStatus $status = RoomStatus::Clean, ?string $cleanedAt = '2026-10-27 10:30'): Room
    {
        return new Room(1, 1, '101', 'Standard', 32000, 5000, $status, $cleanedAt ? new DateTimeImmutable($cleanedAt) : null);
    }

    public static function reservation(
        string $checkInDate = '2026-10-27',
        int $nights = 2,
        int $earlyHours = 0,
        int $lateHours = 0,
        ReservationStatus $status = ReservationStatus::Confirmed,
        int $id = 1,
    ): Reservation {
        return new Reservation($id, "HF-{$id}", 10, 1, new DateTimeImmutable($checkInDate), $nights, $earlyHours, $lateHours, $status);
    }
}
