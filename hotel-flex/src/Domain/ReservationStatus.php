<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum ReservationStatus: string
{
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmada',
            self::CheckedIn => 'Hospedado',
            self::CheckedOut => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Confirmed || $this === self::CheckedIn;
    }
}
