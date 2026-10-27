<?php

declare(strict_types=1);

namespace HotelFlex\Domain;

enum Role: string
{
    case Staff = 'staff';
    case Guest = 'guest';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Recepção / Governança',
            self::Guest => 'Hóspede',
            self::Admin => 'Administrador',
        };
    }
}
