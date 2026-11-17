<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** Copy of the taker ("tomador") stored in the invoice: later profile edits never change an issued invoice. */
final readonly class InvoiceTaker
{
    public const ANONYMOUS_NAME = 'Consumidor não identificado';

    public function __construct(
        public string $name,
        public ?TaxDocument $document,
        public ?string $email,
        public ?string $address,
        public ?string $municipalityCode,
        public ?bool $isSimples = null,
    ) {
    }

    public static function fromProfile(BillingProfile $profile): self
    {
        return new self($profile->name, $profile->document, $profile->email, $profile->address?->format(), $profile->address?->municipalityCode, $profile->company?->isSimples);
    }

    public static function anonymous(): self
    {
        return new self(self::ANONYMOUS_NAME, null, null, null, null);
    }

    /** Counter sale where the customer only asks for "CPF na nota". */
    public static function withCpfOnly(string $name, TaxDocument $cpf): self
    {
        return new self($name !== '' ? $name : 'Consumidor', $cpf, null, null, null);
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
