<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** The hotel as the invoice issuer ("prestador"). Rates and rules come from the hotel's accountant. */
final readonly class HotelFiscalProfile
{
    public function __construct(
        public int $hotelId,
        public string $legalName,
        public TaxDocument $cnpj,
        public string $municipalRegistration,
        /** Only needed to sell breakfast to non-guests with an NFC-e (ICMS). */
        public ?string $stateRegistration,
        public TaxRegime $taxRegime,
        public string $municipalityCode,
        public string $address,
        /** National service list (LC 116/2003): 09.01 = lodging. */
        public string $serviceCode,
        public int $issRateBasisPoints,
        public int $icmsRateBasisPoints,
        public string $series,
        public IssWithholdingRule $issWithholding,
        public bool $allowsAnonymousConsumer,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['hotel_id'],
            (string) $row['legal_name'],
            TaxDocument::fromStored(DocumentType::Cnpj, (string) $row['cnpj']),
            (string) $row['municipal_registration'],
            $row['state_registration'] !== null ? (string) $row['state_registration'] : null,
            TaxRegime::from((string) $row['tax_regime']),
            (string) $row['municipality_code'],
            (string) $row['address'],
            (string) $row['service_code'],
            (int) $row['iss_rate_bp'],
            (int) $row['icms_rate_bp'],
            (string) $row['series'],
            IssWithholdingRule::from((string) $row['iss_withholding']),
            (bool) $row['allows_anonymous_consumer'],
        );
    }

    /** Missing data that blocks the issue of this kind of invoice; empty when ready. */
    public function getMissingData(InvoiceKind $kind): ?string
    {
        if ($kind === InvoiceKind::Nfce && ($this->stateRegistration === null || $this->stateRegistration === '')) {
            return 'O hotel precisa da inscrição estadual para emitir NFC-e (venda de café a não hóspedes).';
        }

        return null;
    }
}
