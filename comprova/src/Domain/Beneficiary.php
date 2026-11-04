<?php

declare(strict_types=1);

namespace Comprova\Domain;

use DateTimeImmutable;

final readonly class Beneficiary
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?PhoneNumber $whatsappPhone,
        public ?DateTimeImmutable $whatsappOptInAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            $row['whatsapp_phone'] !== null ? PhoneNumber::fromE164Digits((string) $row['whatsapp_phone']) : null,
            $row['whatsapp_opt_in_at'] !== null ? new DateTimeImmutable((string) $row['whatsapp_opt_in_at']) : null,
        );
    }

    /** Business-initiated WhatsApp messages require the recipient's prior consent. */
    public function canReceiveWhatsApp(): bool
    {
        return $this->whatsappPhone !== null && $this->whatsappOptInAt !== null;
    }
}
