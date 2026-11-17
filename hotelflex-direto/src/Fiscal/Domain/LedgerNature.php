<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** How each tax amount of an invoice is settled, for the hotel's pre-control. */
enum LedgerNature: string
{
    /** The hotel pays it. */
    case Own = 'proprio';
    /** The taker withholds and pays it (e.g. ISS retained by a company). */
    case Withheld = 'retido';
    /** Shown on the invoice but not paid (CBS/IBS during the 2026 test year). */
    case Informative = 'informativo';

    public function label(): string
    {
        return match ($this) {
            self::Own => 'A recolher pelo hotel',
            self::Withheld => 'Retido pelo tomador',
            self::Informative => 'Informativo',
        };
    }
}
