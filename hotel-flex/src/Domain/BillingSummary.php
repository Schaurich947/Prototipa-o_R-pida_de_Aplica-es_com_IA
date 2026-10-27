<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

/** Monetary values in cents. */
final readonly class BillingSummary
{
    public function __construct(
        public int $baseCents,
        public int $additionalCents,
    ) {
    }

    public function totalCents(): int
    {
        return $this->baseCents + $this->additionalCents;
    }
}
