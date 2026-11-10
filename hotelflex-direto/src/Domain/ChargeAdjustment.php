<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/**
 * Keeps the charges in line with the reservation total after any change
 * (extra hours, new dates). An unpaid charge absorbs the difference first;
 * otherwise a new extra charge or a refund credit is created.
 */
final readonly class ChargeAdjustment
{
    private function __construct(
        public ?int $pendingChargeId,
        public ?int $newPendingAmountCents,
        public int $extraChargeCents,
        public int $refundCents,
    ) {
    }

    /** @param list<Charge> $charges */
    public static function plan(int $totalCents, array $charges): self
    {
        $active = array_values(array_filter($charges, static fn (Charge $charge) => $charge->status->countsTowardsTotal()));
        $refunds = array_filter($charges, static fn (Charge $charge) => $charge->kind === ChargeKind::Refund && in_array($charge->status, [ChargeStatus::RefundPending, ChargeStatus::Refunded], true));
        $covered = self::sumAmounts($active) - self::sumAmounts(array_values($refunds));
        $delta = $totalCents - $covered;
        $pending = array_values(array_filter($active, static fn (Charge $charge) => $charge->status === ChargeStatus::Pending))[0] ?? null;
        if ($delta === 0) {
            return new self(null, null, 0, 0);
        }
        if ($pending !== null) {
            $newAmount = $pending->amountCents + $delta;

            return new self($pending->id, max(0, $newAmount), 0, max(0, -$newAmount));
        }

        return new self(null, null, max(0, $delta), max(0, -$delta));
    }

    /** @param list<Charge> $charges */
    private static function sumAmounts(array $charges): int
    {
        return array_sum(array_map(static fn (Charge $charge) => $charge->amountCents, $charges));
    }

    public function isEmpty(): bool
    {
        return $this->pendingChargeId === null && $this->extraChargeCents === 0 && $this->refundCents === 0;
    }
}
