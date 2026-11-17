<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

use DateTimeImmutable;

/** What the CNPJ lookup returned (Receita Federal open data or a simulation). */
final readonly class CompanyRecord
{
    public const ACTIVE_STATUS = 'ATIVA';

    public function __construct(
        public string $cnpj,
        public string $legalName,
        /** ATIVA, BAIXADA, INAPTA, SUSPENSA or NULA, as published by the Receita Federal. */
        public string $status,
        public ?bool $isSimples,
        public ?bool $isMei,
        public ?BillingAddress $address,
        public string $source,
        public DateTimeImmutable $lookedUpAt,
    ) {
    }

    public function isActive(): bool
    {
        return strtoupper($this->status) === self::ACTIVE_STATUS;
    }

    public function describeRegime(): string
    {
        return match (true) {
            $this->isMei === true => 'MEI',
            $this->isSimples === true => 'Simples Nacional',
            $this->isSimples === false => 'Fora do Simples',
            default => 'Regime não informado',
        };
    }

    /** @return array<string, mixed> cache payload */
    public function toArray(): array
    {
        return [
            'cnpj' => $this->cnpj, 'legal_name' => $this->legalName, 'status' => $this->status, 'is_simples' => $this->isSimples, 'is_mei' => $this->isMei,
            'address' => $this->address?->toRow(), 'source' => $this->source, 'looked_up_at' => $this->lookedUpAt->format(DATE_ATOM),
        ];
    }

    /** @param array<string, mixed> $data cache payload */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['cnpj'], (string) $data['legal_name'], (string) $data['status'],
            isset($data['is_simples']) ? (bool) $data['is_simples'] : null, isset($data['is_mei']) ? (bool) $data['is_mei'] : null,
            is_array($data['address'] ?? null) ? BillingAddress::fromRow($data['address']) : null,
            (string) $data['source'], new DateTimeImmutable((string) $data['looked_up_at']),
        );
    }

    /** @param array<string, mixed> $row billing_profiles row */
    public static function fromProfileRow(array $row): self
    {
        return new self(
            (string) $row['document'],
            (string) $row['name'],
            (string) $row['company_status'],
            $row['company_is_simples'] !== null ? (bool) $row['company_is_simples'] : null,
            $row['company_is_mei'] !== null ? (bool) $row['company_is_mei'] : null,
            BillingAddress::fromRow($row),
            (string) $row['lookup_source'],
            new DateTimeImmutable((string) $row['looked_up_at']),
        );
    }
}
