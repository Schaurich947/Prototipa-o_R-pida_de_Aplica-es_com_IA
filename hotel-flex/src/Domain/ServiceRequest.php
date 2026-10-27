<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final readonly class ServiceRequest
{
    public function __construct(
        public int $id,
        public int $reservationId,
        public RequestType $type,
        public int $extraHours,
        public RequestStatus $status,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $resolvedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['reservation_id'],
            RequestType::from((string) $row['type']),
            (int) $row['extra_hours'],
            RequestStatus::from((string) $row['status']),
            new DateTimeImmutable((string) $row['created_at']),
            $row['resolved_at'] !== null ? new DateTimeImmutable((string) $row['resolved_at']) : null,
        );
    }

    public function isPending(): bool
    {
        return $this->status === RequestStatus::Pending;
    }
}
