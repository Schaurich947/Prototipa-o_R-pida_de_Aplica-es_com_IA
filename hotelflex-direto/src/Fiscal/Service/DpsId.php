<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\InvoiceKind;

/** Same composition as the national NFS-e DPS id: "DPS" + city + issuer type + CNPJ + series + number. */
final class DpsId
{
    private const ISSUER_IS_COMPANY = '2';

    /** NFC-e numbers run apart from NFS-e numbers, so the kind keeps both ids unique. */
    public static function build(HotelFiscalProfile $issuer, int $number, InvoiceKind $kind = InvoiceKind::Nfse): string
    {
        return ($kind === InvoiceKind::Nfse ? 'DPS' : 'NFC') . $issuer->municipalityCode . self::ISSUER_IS_COMPANY . $issuer->cnpj->value
            . str_pad($issuer->series, 5, '0', STR_PAD_LEFT) . str_pad((string) $number, 15, '0', STR_PAD_LEFT);
    }
}
