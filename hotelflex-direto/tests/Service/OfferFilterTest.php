<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Service;

use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\RoomStatus;
use HotelFlex\Service\OfferFilter;
use HotelFlex\Service\OfferSort;
use HotelFlex\Service\RoomOffer;
use PHPUnit\Framework\TestCase;

final class OfferFilterTest extends TestCase
{
    public function testSortsByPriceBothWays(): void
    {
        $offers = [$this->offer('B', 'Luxo', 45000), $this->offer('A', 'Standard', 32000), $this->offer('C', 'Suíte', 68000)];

        $this->assertSame(['A', 'B', 'C'], $this->hotelNames((new OfferFilter('', null, OfferSort::PriceAsc))->apply($offers)));
        $this->assertSame(['C', 'B', 'A'], $this->hotelNames((new OfferFilter('', null, OfferSort::PriceDesc))->apply($offers)));
    }

    public function testSortsByHotelName(): void
    {
        $offers = [$this->offer('Pousada', 'Luxo', 45000), $this->offer('Aurora', 'Standard', 32000)];

        $this->assertSame(['Aurora', 'Pousada'], $this->hotelNames((new OfferFilter('', null, OfferSort::NameAsc))->apply($offers)));
        $this->assertSame(['Pousada', 'Aurora'], $this->hotelNames((new OfferFilter('', null, OfferSort::NameDesc))->apply($offers)));
    }

    public function testFiltersByCategoryAndMaxDailyRate(): void
    {
        $offers = [$this->offer('A', 'Standard', 32000), $this->offer('B', 'Luxo', 45000), $this->offer('C', 'Luxo', 80000)];

        $this->assertSame(['B'], $this->hotelNames((new OfferFilter('Luxo', 50000, OfferSort::PriceAsc))->apply($offers)));
        $this->assertSame(['Luxo', 'Standard'], OfferFilter::getCategories($offers));
    }

    private function offer(string $hotelName, string $category, int $dailyRateCents): RoomOffer
    {
        $hotel = new Hotel(1, $hotelName, 'São Paulo', 14, 12, 8, 18, 1, 1, 'pix@hotel.test', null, 24, 48);

        return new RoomOffer($hotel, new Room(1, 1, '101', $category, $dailyRateCents, 5000, RoomStatus::Clean, null), 2);
    }

    /** @param list<RoomOffer> $offers @return list<string> */
    private function hotelNames(array $offers): array
    {
        return array_map(static fn (RoomOffer $offer) => $offer->hotel->name, $offers);
    }
}
