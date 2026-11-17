<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Domain;

enum InvoiceKind: string
{
    case Nfse = 'nfse';
    case Nfce = 'nfce';

    public function label(): string
    {
        return $this === self::Nfse ? 'NFS-e' : 'NFC-e';
    }

    public function description(): string
    {
        return $this === self::Nfse ? 'Nota Fiscal de Serviço eletrônica' : 'Nota Fiscal de Consumidor eletrônica';
    }
}
