<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum RequestType: string
{
    case EarlyCheckIn = 'early_checkin';
    case LateCheckout = 'late_checkout';

    public function label(): string
    {
        return match ($this) {
            self::EarlyCheckIn => 'Entrada antecipada',
            self::LateCheckout => 'Saída tardia',
        };
    }
}
