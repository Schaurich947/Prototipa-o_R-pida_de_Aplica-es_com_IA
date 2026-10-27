<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final readonly class Reservation
{
    public function __construct(
        public int $id,
        public string $code,
        public string $guestName,
        public int $roomId,
        public DateTimeImmutable $checkInDate,
        public int $nights,
        public int $lateCheckoutHours,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['guest_name'],
            (int) $row['room_id'],
            new DateTimeImmutable((string) $row['check_in_date']),
            (int) $row['nights'],
            (int) $row['late_checkout_hours'],
        );
    }

    public function withLateCheckoutHours(int $hours): self
    {
        return new self($this->id, $this->code, $this->guestName, $this->roomId, $this->checkInDate, $this->nights, $hours);
    }
}
