<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** Taxes of one invoice, in cents. */
final readonly class TaxBreakdown
{
    public function __construct(
        public int $issCents,
        public bool $isIssWithheld,
        public int $icmsCents,
        public int $cbsCents,
        public int $ibsCents,
        public bool $isReformInformative,
        public bool $allowsAcquirerCredit,
    ) {
    }

    /** "Valor aproximado dos tributos" shown to the consumer (Lei 12.741/2012). */
    public function approximateTotalCents(): int
    {
        return $this->issCents + $this->icmsCents + $this->cbsCents + $this->ibsCents;
    }

    /** @return list<array{tax: TaxType, nature: LedgerNature, cents: int}> */
    public function toLedgerLines(): array
    {
        $reformNature = $this->isReformInformative ? LedgerNature::Informative : LedgerNature::Own;
        $lines = [
            ['tax' => TaxType::Iss, 'nature' => $this->isIssWithheld ? LedgerNature::Withheld : LedgerNature::Own, 'cents' => $this->issCents],
            ['tax' => TaxType::Icms, 'nature' => LedgerNature::Own, 'cents' => $this->icmsCents],
            ['tax' => TaxType::Cbs, 'nature' => $reformNature, 'cents' => $this->cbsCents],
            ['tax' => TaxType::Ibs, 'nature' => $reformNature, 'cents' => $this->ibsCents],
        ];

        return array_values(array_filter($lines, static fn (array $line) => $line['cents'] > 0));
    }
}
