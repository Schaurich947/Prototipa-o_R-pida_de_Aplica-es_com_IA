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
        /** Payment goes straight to the hotel, without an intermediary. */
        public string $pixKey,
        public ?PhoneNumber $whatsappPhone,
        public int $paymentDeadlineHours,
        public int $freeCancellationHours,
        public int $breakfastPriceCents = 0,
        public int $breakfastStartHour = 6,
        public int $breakfastEndHour = 10,
        public int $breakfastDailyCapacity = 0,
        /** Price for people who show up without booking; always higher than the regular price. */
        public int $breakfastWalkInPriceCents = 0,
        /** Estimated breakfast seats each occupied room takes (reservations do not store head count). */
        public int $breakfastSeatsPerRoom = 2,
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
            (string) $row['pix_key'],
            $row['whatsapp_phone'] !== null ? PhoneNumber::fromE164Digits((string) $row['whatsapp_phone']) : null,
            (int) $row['payment_deadline_hours'],
            (int) $row['free_cancellation_hours'],
            (int) ($row['breakfast_price_cents'] ?? 0),
            (int) ($row['breakfast_start_hour'] ?? 6),
            (int) ($row['breakfast_end_hour'] ?? 10),
            (int) ($row['breakfast_daily_capacity'] ?? 0),
            (int) ($row['breakfast_walk_in_price_cents'] ?? 0),
            (int) ($row['breakfast_seats_per_room'] ?? 2),
        );
    }

    /** Walk-in sale only when the hotel set a price above the regular one. */
    public function sellsWalkInBreakfast(): bool
    {
        return $this->sellsBreakfast() && $this->breakfastWalkInPriceCents > $this->breakfastPriceCents;
    }

    public function sellsBreakfast(): bool
    {
        return $this->breakfastPriceCents > 0 && $this->breakfastDailyCapacity > 0;
    }
}
