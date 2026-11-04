<?php

declare(strict_types=1);

namespace Comprova\Domain;

use DateTimeImmutable;

final readonly class Notification
{
    public function __construct(
        public int $id,
        public int $paymentId,
        public NotificationChannel $channel,
        public string $recipient,
        public NotificationStatus $status,
        public int $attempts,
        public ?string $lastError,
        public ?DateTimeImmutable $sentAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['payment_id'],
            NotificationChannel::from((string) $row['channel']),
            (string) $row['recipient'],
            NotificationStatus::from((string) $row['status']),
            (int) $row['attempts'],
            $row['last_error'] !== null ? (string) $row['last_error'] : null,
            $row['sent_at'] !== null ? new DateTimeImmutable((string) $row['sent_at']) : null,
        );
    }
}
