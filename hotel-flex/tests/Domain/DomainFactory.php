<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\RoomStatus;

final class DomainFactory
{
    public static function room(RoomStatus $status = RoomStatus::Clean, ?string $cleanedAt = '2026-10-27 10:30', int $checkoutLimitHour = 18): Room
    {
        return new Room(1, '101', 'Standard', 32000, 5000, $checkoutLimitHour, $status, $cleanedAt ? new DateTimeImmutable($cleanedAt) : null);
    }

    public static function reservation(int $lateCheckoutHours = 0, string $checkInDate = '2026-10-27'): Reservation
    {
        return new Reservation(1, 'HF-1001', 'Ana Souza', 1, new DateTimeImmutable($checkInDate), 2, $lateCheckoutHours);
    }
}
