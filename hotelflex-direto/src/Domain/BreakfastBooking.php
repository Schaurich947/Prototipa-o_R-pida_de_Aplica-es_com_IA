<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

final readonly class BreakfastBooking
{
    public function __construct(
        public int $id,
        public string $code,
        public int $userId,
        public int $hotelId,
        public DateTimeImmutable $date,
        public int $people,
        public int $unitPriceCents,
        public int $discountPercent,
        public int $totalCents,
        public BreakfastPaymentOption $paymentOption,
        public BreakfastStatus $status,
        public bool $isPaid,
        public ?PaymentMethod $paidMethod,
        public ?DateTimeImmutable $dueAt,
        public ?string $receiptPath,
        public ?string $receiptOriginalName,
        public ?string $receiptMime,
        public ?string $rejectionReason,
        public ?string $walkInName = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $optional = static fn (string $key): ?string => $row[$key] !== null ? (string) $row[$key] : null;

        return new self(
            (int) $row['id'],
            (string) $row['code'],
            (int) $row['user_id'],
            (int) $row['hotel_id'],
            new DateTimeImmutable((string) $row['breakfast_date']),
            (int) $row['people'],
            (int) $row['unit_price_cents'],
            (int) $row['discount_percent'],
            (int) $row['total_cents'],
            BreakfastPaymentOption::from((string) $row['payment_option']),
            BreakfastStatus::from((string) $row['status']),
            (bool) $row['is_paid'],
            $optional('paid_method') !== null ? PaymentMethod::from((string) $row['paid_method']) : null,
            $optional('due_at') !== null ? new DateTimeImmutable((string) $row['due_at']) : null,
            $optional('receipt_path'),
            $optional('receipt_original_name'),
            $optional('receipt_mime'),
            $optional('rejection_reason'),
            $optional('walk_in_name'),
        );
    }

    public function acceptsReceipt(): bool
    {
        return $this->status === BreakfastStatus::PendingPayment && $this->paymentOption === BreakfastPaymentOption::Pix;
    }

    public function canBeReceivedAtDesk(): bool
    {
        return !$this->isPaid && in_array($this->status, [BreakfastStatus::PendingPayment, BreakfastStatus::Confirmed], true);
    }

    /** Can be cancelled until breakfast starts on the day; a paid one is refunded by the hotel. */
    public function isCancellableBy(DateTimeImmutable $now, Hotel $hotel): bool
    {
        $isOpen = in_array($this->status, [BreakfastStatus::PendingPayment, BreakfastStatus::UnderReview, BreakfastStatus::Confirmed], true);

        return $isOpen && $now < $this->date->setTime($hotel->breakfastStartHour, 0);
    }
}
