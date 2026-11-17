<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** Copy of the taker ("tomador") stored in the invoice: later profile edits never change an issued invoice. */
final readonly class InvoiceTaker
{
    /** Only for reservations made before CPF/CNPJ became mandatory: such invoices are rejected until the data is provided. */
    public const MISSING_NAME = 'Tomador não informado';

    public function __construct(
        public string $name,
        public ?TaxDocument $document,
        public ?string $email,
        public ?string $address,
        public ?string $municipalityCode,
        public ?bool $isSimples = null,
        /** State registration of a company taker (or ISENTO). People never have one. */
        public ?string $stateRegistration = null,
    ) {
    }

    public static function fromProfile(BillingProfile $profile): self
    {
        return new self($profile->name, $profile->document, $profile->email, $profile->address?->format(), $profile->address?->municipalityCode, $profile->company?->isSimples, $profile->stateRegistration);
    }

    public static function missing(): self
    {
        return new self(self::MISSING_NAME, null, null, null, null);
    }

    /** Counter sale: the reception types the customer's CPF or CNPJ. */
    public static function withDocumentOnly(string $name, TaxDocument $document): self
    {
        return new self($name !== '' ? $name : 'Cliente', $document, null, null, null);
    }

    public function isIdentified(): bool
    {
        return $this->document !== null;
    }

    public function isCompany(): bool
    {
        return $this->document?->type === DocumentType::Cnpj;
    }

    public function describeDocument(): string
    {
        return $this->document !== null ? "{$this->document->type->label()} {$this->document->format()}" : 'não informado';
    }
}
