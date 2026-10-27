<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\RoomStatus;

enum RoomFilter: string
{
    case All = 'todos';
    case Early = 'antecipados';
    case Housekeeping = 'limpeza';
    case Released = 'liberados';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Todos',
            self::Early => 'Vago antecipado',
            self::Housekeeping => 'Limpeza',
            self::Released => 'Liberados',
        };
    }

    public function matches(RoomView $view): bool
    {
        return match ($this) {
            self::All => true,
            self::Early => $view->isEarlyVacant,
            self::Housekeeping => $view->room->status->isInHousekeeping(),
            self::Released => $view->room->status === RoomStatus::Released,
        };
    }

    /**
     * @param list<RoomView> $views
     * @return list<RoomView>
     */
    public function apply(array $views): array
    {
        return array_values(array_filter($views, $this->matches(...)));
    }
}
