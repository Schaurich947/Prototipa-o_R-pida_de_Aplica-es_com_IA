<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** When the taker withholds the ISS instead of the hotel paying it. Each municipality's law decides; the hotel's accountant chooses. */
enum IssWithholdingRule: string
{
    case Never = 'nunca';
    case CompanySameCity = 'empresa-mesma-cidade';

    public function label(): string
    {
        return match ($this) {
            self::Never => 'Nunca: o hotel recolhe o ISS',
            self::CompanySameCity => 'Empresas do mesmo município retêm o ISS (exceto Simples e MEI)',
        };
    }

    public function isWithheldBy(InvoiceTaker $taker, string $hotelMunicipalityCode): bool
    {
        if ($this === self::Never || !$taker->isCompany() || $taker->isSimples === true) {
            return false;
        }

        return $taker->municipalityCode !== null && $taker->municipalityCode === $hotelMunicipalityCode;
    }
}
