<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

enum DocumentType: string
{
    case Cpf = 'cpf';
    case Cnpj = 'cnpj';
    /** Passport or another foreign document, for guests without a CPF. */
    case Foreign = 'estrangeiro';

    public function label(): string
    {
        return match ($this) {
            self::Cpf => 'CPF',
            self::Cnpj => 'CNPJ',
            self::Foreign => 'Documento estrangeiro',
        };
    }
}
