<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Domain\RoomStatus;

enum RoomPanelFilter: string
{
    case All = 'todos';
    case Arrivals = 'chegadas';
    case Early = 'antecipados';
    case Housekeeping = 'limpeza';
    case Departures = 'hospedados';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Todos',
            self::Arrivals => 'Chegadas hoje',
            self::Early => 'Vago antecipado',
            self::Housekeeping => 'Limpeza',
            self::Departures => 'Hospedados',
        };
    }

    public function matches(RoomCardView $card): bool
    {
        return match ($this) {
            self::All => true,
            self::Arrivals => $card->arrival !== null,
            self::Early => $card->isEarlyVacant,
            self::Housekeeping => $card->room->status === RoomStatus::Dirty || $card->room->status === RoomStatus::Cleaning,
            self::Departures => $card->inHouse !== null,
        };
    }

    /** @param list<RoomCardView> $cards @return list<RoomCardView> */
    public function apply(array $cards): array
    {
        return array_values(array_filter($cards, $this->matches(...)));
    }
}
