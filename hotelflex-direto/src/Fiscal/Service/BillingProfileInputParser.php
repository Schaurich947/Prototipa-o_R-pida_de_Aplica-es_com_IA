<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\User;
use HotelFlex\Fiscal\Domain\BillingAddress;
use HotelFlex\Fiscal\Domain\BillingKind;
use HotelFlex\Fiscal\Domain\CompanyRecord;
use HotelFlex\Fiscal\Domain\TaxDocument;

/** Server-side validation of the billing form (the browser validates too, for usability only). */
final class BillingProfileInputParser
{
    private const ADDRESS_FIELDS = ['street', 'number', 'district', 'city', 'state', 'postal_code', 'municipality_code'];

    /**
     * @param array<string, string> $input
     * @param callable(TaxDocument): ?CompanyRecord $findCompany
     */
    public function parse(User $owner, array $input, callable $findCompany): ParsedBillingProfile
    {
        $kind = BillingKind::tryFrom($input['kind'] ?? '') ?? throw new DomainException('Escolha em nome de quem sai a nota.');
        $document = $this->parseDocument($kind, $input['document'] ?? '');
        $company = $kind === BillingKind::Company ? $findCompany($document) : null;
        $address = $this->parseAddress($kind, $input, $company);

        return new ParsedBillingProfile($kind, $this->parseName($kind, $owner, $input['name'] ?? '', $company), $document, $this->parseEmail($input['email'] ?? ''), $address, $this->parseCountry($kind, $input['country'] ?? ''), $company);
    }

    private function parseDocument(BillingKind $kind, string $value): TaxDocument
    {
        return match ($kind) {
            BillingKind::Self, BillingKind::OtherPerson => TaxDocument::cpf($value),
            BillingKind::Company => TaxDocument::cnpj($value),
            BillingKind::Foreign => TaxDocument::foreign($value),
        };
    }

    /** A company's name comes from the Receita Federal when the lookup answered, so it matches the official record. */
    private function parseName(BillingKind $kind, User $owner, string $typedName, ?CompanyRecord $company): string
    {
        $name = $company?->legalName ?? ($kind === BillingKind::Self && trim($typedName) === '' ? $owner->name : trim($typedName));
        if (mb_strlen($name) < 3) {
            throw new DomainException('Informe o nome completo ou a razão social.');
        }

        return mb_substr($name, 0, 150);
    }

    /** @param array<string, string> $input */
    private function parseAddress(BillingKind $kind, array $input, ?CompanyRecord $company): ?BillingAddress
    {
        $typed = array_intersect_key($input, array_flip(self::ADDRESS_FIELDS));
        $hasTyped = array_filter($typed, static fn ($value) => trim((string) $value) !== '') !== [];
        if ($hasTyped) {
            return BillingAddress::fromInput($typed);
        }
        if ($kind->requiresAddress() && $company?->address === null) {
            throw new DomainException('Informe o endereço da empresa: ele é obrigatório na nota fiscal.');
        }

        return $company?->address;
    }

    private function parseEmail(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false || strlen($value) > 190) {
            throw new DomainException('E-mail para envio da nota inválido.');
        }

        return $value;
    }

    private function parseCountry(BillingKind $kind, string $value): string
    {
        if ($kind !== BillingKind::Foreign) {
            return 'BR';
        }
        $country = strtoupper(trim($value));
        if (!preg_match('/^[A-Z]{2}$/', $country) || $country === 'BR') {
            throw new DomainException('Informe o país do documento com 2 letras (ex.: AR, US, PT).');
        }

        return $country;
    }
}
