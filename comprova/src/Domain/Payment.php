<?php

declare(strict_types=1);

namespace Comprova\Domain;

use DateTimeImmutable;

final readonly class Payment
{
    public function __construct(
        public int $id,
        public int $beneficiaryId,
        public ?string $externalId,
        public PaymentSource $source,
        public int $amountCents,
        public ?string $description,
        public DateTimeImmutable $paidAt,
        public string $receiptPath,
        public string $receiptOriginalName,
        public string $receiptMime,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['beneficiary_id'],
            $row['external_id'] !== null ? (string) $row['external_id'] : null,
            PaymentSource::from((string) $row['source']),
            (int) $row['amount_cents'],
            $row['description'] !== null ? (string) $row['description'] : null,
            new DateTimeImmutable((string) $row['paid_at']),
            (string) $row['receipt_path'],
            (string) $row['receipt_original_name'],
            (string) $row['receipt_mime'],
        );
    }
}
