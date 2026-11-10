<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** Monetary values in cents. */
final readonly class BillingSummary
{
    public function __construct(
        public int $baseCents,
        public int $earlyCheckInCents,
        public int $lateCheckoutCents,
        /** Discount on the nights for paying earlier (see PaymentPlan). */
        public int $discountCents = 0,
        public int $discountPercent = 0,
    ) {
    }

    public function additionalCents(): int
    {
        return $this->earlyCheckInCents + $this->lateCheckoutCents;
    }

    public function totalCents(): int
    {
        return $this->baseCents - $this->discountCents + $this->additionalCents();
    }
}
