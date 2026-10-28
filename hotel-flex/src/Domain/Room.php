<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final readonly class Room
{
    public function __construct(
        public int $id,
        public int $hotelId,
        public string $number,
        public string $category,
        public int $dailyRateCents,
        public int $hourlyRateCents,
        public RoomStatus $status,
        public ?DateTimeImmutable $cleanedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['hotel_id'],
            (string) $row['number'],
            (string) $row['category'],
            (int) $row['daily_rate_cents'],
            (int) $row['hourly_rate_cents'],
            RoomStatus::from((string) $row['status']),
            $row['cleaned_at'] !== null ? new DateTimeImmutable((string) $row['cleaned_at']) : null,
        );
    }
}
