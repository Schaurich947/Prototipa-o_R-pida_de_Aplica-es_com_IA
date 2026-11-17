<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

use DateTimeImmutable;

/** Invoice data saved by a customer and chosen before each payment. */
final readonly class BillingProfile
{
    public function __construct(
        public int $id,
        public int $userId,
        public BillingKind $kind,
        public string $name,
        public TaxDocument $document,
        public ?string $email,
        public ?BillingAddress $address,
        public string $country,
        /** Snapshot of the CNPJ lookup when the profile was saved (company only). */
        public ?CompanyRecord $company = null,
        /** Companies only (or ISENTO); a person never has a state registration. */
        public ?string $stateRegistration = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $kind = BillingKind::from((string) $row['kind']);

        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            $kind,
            (string) $row['name'],
            TaxDocument::fromStored($kind->documentType(), (string) $row['document']),
            $row['email'] !== null ? (string) $row['email'] : null,
            BillingAddress::fromRow($row),
            (string) $row['country'],
            $row['company_status'] !== null ? CompanyRecord::fromProfileRow($row) : null,
            ($row['state_registration'] ?? null) !== null ? (string) $row['state_registration'] : null,
        );
    }

    public function describe(): string
    {
        return "{$this->name} · {$this->document->type->label()} {$this->document->mask()}";
    }

    public function lookedUpAt(): ?DateTimeImmutable
    {
        return $this->company?->lookedUpAt;
    }
}
