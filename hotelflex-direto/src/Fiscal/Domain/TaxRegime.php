<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

enum TaxRegime: string
{
    case Simples = 'simples';
    case Presumed = 'presumido';
    case Real = 'real';
    case Mei = 'mei';

    public function label(): string
    {
        return match ($this) {
            self::Simples => 'Simples Nacional',
            self::Presumed => 'Lucro Presumido',
            self::Real => 'Lucro Real',
            self::Mei => 'MEI',
        };
    }
}
