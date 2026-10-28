<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** Each hotel on the platform has its own check-in/out policy. */
final readonly class Hotel
{
    public function __construct(
        public int $id,
        public string $name,
        public string $city,
        public int $checkInHour,
        public int $checkOutHour,
        public int $earliestCheckInHour,
        public int $latestCheckOutHour,
        /** Early check-in hours that are not charged (tolerance). */
        public int $earlyFreeHours,
        /** Time housekeeping needs between one guest leaving and the next arriving. */
        public int $cleaningBufferHours,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['city'],
            (int) $row['check_in_hour'],
            (int) $row['check_out_hour'],
            (int) $row['earliest_check_in_hour'],
            (int) $row['latest_check_out_hour'],
            (int) $row['early_free_hours'],
            (int) $row['cleaning_buffer_hours'],
        );
    }
}
