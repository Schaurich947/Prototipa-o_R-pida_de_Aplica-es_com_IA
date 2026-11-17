<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** Who the invoice is issued to: it may differ from the person who booked. */
enum BillingKind: string
{
    case Self = 'proprio';
    case OtherPerson = 'outra-pessoa';
    case Company = 'empresa';
    case Foreign = 'estrangeiro';

    public function label(): string
    {
        return match ($this) {
            self::Self => 'Em meu nome (CPF)',
            self::OtherPerson => 'Em nome de outra pessoa (CPF)',
            self::Company => 'Em nome de uma empresa (CNPJ)',
            self::Foreign => 'Sou estrangeiro (sem CPF)',
        };
    }

    public function documentType(): DocumentType
    {
        return match ($this) {
            self::Self, self::OtherPerson => DocumentType::Cpf,
            self::Company => DocumentType::Cnpj,
            self::Foreign => DocumentType::Foreign,
        };
    }

    /** Companies need the full address on the invoice; people may leave it blank. */
    public function requiresAddress(): bool
    {
        return $this === self::Company;
    }
}
