<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

use DateTimeImmutable;

/** CBS/IBS rate for a service type during a period. Rules are data, so a new law changes rows, not code. */
final readonly class TaxRule
{
    public function __construct(
        public TaxType $tax,
        public ServiceType $serviceType,
        public int $rateBasisPoints,
        /** Specific regimes cut the rate (e.g. 40% for lodging under LC 214/2025). 4000 = 40%. */
        public int $reductionBasisPoints,
        public DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validUntil,
        public bool $isInformative,
        /** Whether a company taker may claim this tax as credit (forbidden for lodging under LC 214/2025). */
        public bool $allowsAcquirerCredit,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            TaxType::from((string) $row['tax']),
            ServiceType::from((string) $row['service_type']),
            (int) $row['rate_bp'],
            (int) $row['reduction_bp'],
            new DateTimeImmutable((string) $row['valid_from']),
            $row['valid_until'] !== null ? new DateTimeImmutable((string) $row['valid_until']) : null,
            (bool) $row['is_informative'],
            (bool) $row['allows_acquirer_credit'],
        );
    }

    public function effectiveRateBasisPoints(): int
    {
        return intdiv($this->rateBasisPoints * (10000 - $this->reductionBasisPoints), 10000);
    }
}
