<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Pdf;

/** One item of a consumer invoice (NFC-e). */
final readonly class InvoiceLine
{
    public function __construct(
        public string $code,
        public string $description,
        public int $quantity,
        public int $unitPriceCents,
        public int $totalCents,
    ) {
    }
}
