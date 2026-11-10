<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

use DateTimeImmutable;

/**
 * How the guest pays the booking. Paying earlier earns a discount on the nights;
 * the deposit is always Pix with receipt, because it guarantees the booking.
 */
enum PaymentPlan: string
{
    case Full = 'integral';
    case Deposit = 'sinal';
    case DepositPlusDesk = 'sinal-balcao';

    public const DEPOSIT_PERCENT = 50;
    /** With the Deposit plan, the rest is due this many hours before check-in. */
    public const REST_DUE_HOURS_BEFORE_CHECK_IN = 24;

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Pix à vista: 100% na reserva',
            self::Deposit => self::DEPOSIT_PERCENT . '% via Pix na reserva + restante via Pix até ' . self::REST_DUE_HOURS_BEFORE_CHECK_IN . 'h antes da entrada',
            self::DepositPlusDesk => self::DEPOSIT_PERCENT . '% via Pix na reserva + restante no hotel (dinheiro, cartão ou Pix no balcão)',
        };
    }

    public function discountPercent(): int
    {
        return match ($this) {
            self::Full => 5,
            self::Deposit => 2,
            self::DepositPlusDesk => 0,
        };
    }

    public static function calculateDiscount(int $baseCents, int $percent): int
    {
        return intdiv($baseCents * $percent, 100);
    }

    public function isRestPaidAtDesk(): bool
    {
        return $this === self::DepositPlusDesk;
    }

    /** When the second charge is due; null for a single payment. */
    public function getRestDueAt(DateTimeImmutable $checkInAt): ?DateTimeImmutable
    {
        return match ($this) {
            self::Full => null,
            self::Deposit => $checkInAt->modify('-' . self::REST_DUE_HOURS_BEFORE_CHECK_IN . ' hours'),
            self::DepositPlusDesk => $checkInAt,
        };
    }

    /** The two-step Pix plan only makes sense if the rest is due after the deposit deadline. */
    public function isAvailable(DateTimeImmutable $firstPaymentDueAt, DateTimeImmutable $checkInAt): bool
    {
        $restDueAt = $this->getRestDueAt($checkInAt);

        return $restDueAt === null || $restDueAt > $firstPaymentDueAt;
    }

    /** @return list<int> amounts in cents, in payment order; the parts always add up to the total */
    public function splitAmount(int $totalCents): array
    {
        if ($this === self::Full) {
            return [$totalCents];
        }
        $deposit = intdiv($totalCents * self::DEPOSIT_PERCENT, 100);

        return [$deposit, $totalCents - $deposit];
    }
}
