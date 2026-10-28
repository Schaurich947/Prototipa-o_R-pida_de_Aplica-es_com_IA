<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final readonly class Reservation
{
    public function __construct(
        public int $id,
        public string $code,
        public int $userId,
        public int $roomId,
        public DateTimeImmutable $checkInDate,
        public int $nights,
        public int $earlyCheckInHours,
        public int $lateCheckoutHours,
        public ReservationStatus $status,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['code'],
            (int) $row['user_id'],
            (int) $row['room_id'],
            new DateTimeImmutable((string) $row['check_in_date']),
            (int) $row['nights'],
            (int) $row['early_check_in_hours'],
            (int) $row['late_checkout_hours'],
            ReservationStatus::from((string) $row['status']),
        );
    }

    public function checkOutDate(): DateTimeImmutable
    {
        return $this->checkInDate->modify("+{$this->nights} days");
    }

    public function startsAt(Hotel $hotel): DateTimeImmutable
    {
        return $this->checkInDate->setTime($hotel->checkInHour - $this->earlyCheckInHours, 0);
    }

    public function endsAt(Hotel $hotel): DateTimeImmutable
    {
        return $this->checkOutDate()->setTime($hotel->checkOutHour + $this->lateCheckoutHours, 0);
    }

    /** Guests can still change or cancel only before arriving. */
    public function isEditableBy(DateTimeImmutable $today): bool
    {
        return $this->status === ReservationStatus::Confirmed && $this->checkInDate >= $today->setTime(0, 0);
    }
}
