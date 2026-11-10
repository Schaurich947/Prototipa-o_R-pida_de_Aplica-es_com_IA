<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum ChargeKind: string
{
    case Booking = 'booking';
    case Extra = 'extra';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::Booking => 'Diárias',
            self::Extra => 'Cobrança adicional',
            self::Refund => 'Crédito a devolver',
        };
    }
}
