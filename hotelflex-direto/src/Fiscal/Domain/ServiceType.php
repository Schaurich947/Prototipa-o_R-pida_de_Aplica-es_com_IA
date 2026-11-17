<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

/** Lodging is a service (ISS, NFS-e). Breakfast sold to non-guests is food (ICMS, NFC-e). */
enum ServiceType: string
{
    case Lodging = 'hospedagem';
    case Food = 'alimentacao';

    public function invoiceKind(): InvoiceKind
    {
        return $this === self::Lodging ? InvoiceKind::Nfse : InvoiceKind::Nfce;
    }

    public function label(): string
    {
        return $this === self::Lodging ? 'Hospedagem' : 'Alimentação (café da manhã)';
    }
}
