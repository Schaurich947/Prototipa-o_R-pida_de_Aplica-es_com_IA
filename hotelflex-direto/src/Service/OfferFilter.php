<?php

declare(strict_types=1);

namespace HotelFlex\Service;

/** Narrows and orders the search results; runs in memory over the free rooms already found. */
final readonly class OfferFilter
{
    public function __construct(
        public string $category,
        public ?int $maxDailyRateCents,
        public OfferSort $sort,
    ) {
    }

    /** @param list<RoomOffer> $offers @return list<RoomOffer> */
    public function apply(array $offers): array
    {
        $filtered = array_values(array_filter($offers, $this->matches(...)));
        usort($filtered, $this->sort->compare(...));

        return $filtered;
    }

    /** @param list<RoomOffer> $offers @return list<string> */
    public static function getCategories(array $offers): array
    {
        $categories = array_values(array_unique(array_map(static fn (RoomOffer $offer) => $offer->room->category, $offers)));
        sort($categories);

        return $categories;
    }

    private function matches(RoomOffer $offer): bool
    {
        if ($this->category !== '' && $offer->room->category !== $this->category) {
            return false;
        }

        return $this->maxDailyRateCents === null || $offer->room->dailyRateCents <= $this->maxDailyRateCents;
    }
}
