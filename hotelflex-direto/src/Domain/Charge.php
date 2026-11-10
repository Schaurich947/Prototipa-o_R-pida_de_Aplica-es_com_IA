<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final readonly class Charge
{
    public function __construct(
        public int $id,
        public int $reservationId,
        public ChargeKind $kind,
        public string $description,
        public int $amountCents,
        public ChargeStatus $status,
        public ?DateTimeImmutable $dueAt,
        public ?string $receiptPath,
        public ?string $receiptOriginalName,
        public ?string $receiptMime,
        public ?string $rejectionReason,
        public DateTimeImmutable $createdAt,
        public bool $payAtDesk = false,
        public ?PaymentMethod $paidMethod = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $optionalString = static fn (string $key): ?string => $row[$key] !== null ? (string) $row[$key] : null;

        return new self(
            (int) $row['id'],
            (int) $row['reservation_id'],
            ChargeKind::from((string) $row['kind']),
            (string) $row['description'],
            (int) $row['amount_cents'],
            ChargeStatus::from((string) $row['status']),
            $row['due_at'] !== null ? new DateTimeImmutable((string) $row['due_at']) : null,
            $optionalString('receipt_path'),
            $optionalString('receipt_original_name'),
            $optionalString('receipt_mime'),
            $optionalString('rejection_reason'),
            new DateTimeImmutable((string) $row['created_at']),
            (bool) ($row['pay_at_desk'] ?? false),
            ($row['paid_method'] ?? null) !== null ? PaymentMethod::from((string) $row['paid_method']) : null,
        );
    }

    /** The guest can (re)send a receipt while the charge waits for payment. */
    public function acceptsReceipt(): bool
    {
        return $this->status === ChargeStatus::Pending && $this->kind !== ChargeKind::Refund && !$this->payAtDesk;
    }

    /** Open charges can always be settled in person, whatever was agreed. */
    public function canBeReceivedAtDesk(): bool
    {
        return $this->status === ChargeStatus::Pending && $this->kind !== ChargeKind::Refund;
    }
}
