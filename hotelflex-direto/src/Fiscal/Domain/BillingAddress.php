<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

use HotelFlex\Domain\DomainException;

final readonly class BillingAddress
{
    private const STATES = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public function __construct(
        public string $street,
        public string $number,
        public string $district,
        public string $city,
        public string $state,
        public string $postalCode,
        /** IBGE code of the city (7 digits), used for municipal tax rules. */
        public ?string $municipalityCode = null,
    ) {
    }

    /** @param array<string, string> $input street, number, district, city, state, postal_code, municipality_code */
    public static function fromInput(array $input): self
    {
        $postalCode = (string) preg_replace('/\D/', '', $input['postal_code'] ?? '');
        $state = strtoupper(trim($input['state'] ?? ''));
        $municipalityCode = (string) preg_replace('/\D/', '', $input['municipality_code'] ?? '');
        $address = new self(self::clean($input['street'] ?? ''), self::clean($input['number'] ?? '', 20), self::clean($input['district'] ?? ''), self::clean($input['city'] ?? ''), $state, $postalCode, strlen($municipalityCode) === 7 ? $municipalityCode : null);
        if ($address->street === '' || $address->number === '' || $address->city === '') {
            throw new DomainException('Preencha rua, número e cidade do endereço de faturamento.');
        }
        if (!in_array($state, self::STATES, true) || strlen($postalCode) !== 8) {
            throw new DomainException('Informe a UF (ex.: SP) e o CEP com 8 números.');
        }

        return $address;
    }

    public function format(): string
    {
        $district = $this->district !== '' ? " - {$this->district}" : '';

        return "{$this->street}, {$this->number}{$district} - {$this->city}/{$this->state} - CEP " . substr($this->postalCode, 0, 5) . '-' . substr($this->postalCode, 5);
    }

    /** @return array<string, string|null> */
    public function toRow(): array
    {
        return ['street' => $this->street, 'street_number' => $this->number, 'district' => $this->district, 'city' => $this->city, 'state' => $this->state, 'postal_code' => $this->postalCode, 'municipality_code' => $this->municipalityCode];
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): ?self
    {
        if (($row['street'] ?? null) === null) {
            return null;
        }

        return new self((string) $row['street'], (string) $row['street_number'], (string) $row['district'], (string) $row['city'], (string) $row['state'], (string) $row['postal_code'], $row['municipality_code'] !== null ? (string) $row['municipality_code'] : null);
    }

    private static function clean(string $value, int $maxLength = 120): string
    {
        return mb_substr(trim($value), 0, $maxLength);
    }
}
