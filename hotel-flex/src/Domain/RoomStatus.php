<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum RoomStatus: string
{
    case Occupied = 'occupied';
    case Dirty = 'dirty';
    case Cleaning = 'cleaning';
    case Clean = 'clean';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::Occupied => 'Ocupado',
            self::Dirty => 'Aguardando limpeza',
            self::Cleaning => 'Em limpeza',
            self::Clean => 'Limpo',
            self::Released => 'Liberado ao hóspede',
        };
    }

    public function canTransitionTo(self $nextStatus): bool
    {
        return match ($this) {
            self::Occupied => $nextStatus === self::Dirty,
            self::Dirty => $nextStatus === self::Cleaning,
            self::Cleaning => $nextStatus === self::Clean,
            self::Clean => $nextStatus === self::Released,
            self::Released => $nextStatus === self::Occupied,
        };
    }

    public function isInHousekeeping(): bool
    {
        return $this === self::Dirty || $this === self::Cleaning;
    }
}
