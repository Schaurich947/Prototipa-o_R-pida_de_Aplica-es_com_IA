<?php

declare(strict_types=1);

namespace HotelFlex\Service;

enum OfferSort: string
{
    case PriceAsc = 'menor-preco';
    case PriceDesc = 'maior-preco';
    case NameAsc = 'nome-az';
    case NameDesc = 'nome-za';

    public function label(): string
    {
        return match ($this) {
            self::PriceAsc => 'Menor preço',
            self::PriceDesc => 'Maior preço',
            self::NameAsc => 'Nome do hotel (A–Z)',
            self::NameDesc => 'Nome do hotel (Z–A)',
        };
    }

    public function compare(RoomOffer $first, RoomOffer $second): int
    {
        $byPrice = $first->totalCents() <=> $second->totalCents();
        $byName = strcoll($first->hotel->name, $second->hotel->name) ?: $byPrice;

        return match ($this) {
            self::PriceAsc => $byPrice ?: $byName,
            self::PriceDesc => -$byPrice ?: $byName,
            self::NameAsc => $byName,
            self::NameDesc => -$byName,
        };
    }
}
