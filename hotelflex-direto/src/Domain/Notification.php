<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

/** A message in the outbox; its text is kept and doubles as the reservation's message history. */
final readonly class Notification
{
    public function __construct(
        public int $id,
        public ?int $reservationId,
        public int $userId,
        public string $channel,
        public string $recipient,
        public string $subject,
        public string $body,
        public string $status,
        public int $attempts,
        public ?string $lastError,
        public DateTimeImmutable $createdAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['reservation_id'] !== null ? (int) $row['reservation_id'] : null,
            (int) $row['user_id'],
            (string) $row['channel'],
            (string) $row['recipient'],
            (string) $row['subject'],
            (string) $row['body'],
            (string) $row['status'],
            (int) $row['attempts'],
            $row['last_error'] !== null ? (string) $row['last_error'] : null,
            new DateTimeImmutable((string) $row['created_at']),
        );
    }

    public function channelLabel(): string
    {
        return $this->channel === 'whatsapp' ? 'WhatsApp' : 'E-mail';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Enviada',
            'failed' => 'Falhou',
            default => 'Na fila',
        };
    }
}
