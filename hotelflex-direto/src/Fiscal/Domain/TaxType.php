<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

enum TaxType: string
{
    case Iss = 'iss';
    case Icms = 'icms';
    case Cbs = 'cbs';
    case Ibs = 'ibs';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function isReformTax(): bool
    {
        return $this === self::Cbs || $this === self::Ibs;
    }
}
