<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use DateTimeImmutable;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\PaymentPlan;
use PHPUnit\Framework\Attributes\TestDox;

/** Objetivo: busca e reserva respeitam a disponibilidade real, inclusive a lotação do café. */
#[TestDox('Falta de quartos e capacidade')]
final class RoomAvailabilityTest extends IntegrationTestCase
{
    #[TestDox('Hotel lotado: a busca não oferece nenhum quarto')]
    public function testFullHotelOffersNothing(): void
    {
        foreach ([1, 2, 3] as $roomId) {
            $this->book('ana@hotelflex.test', $roomId, '2026-12-10', '2026-12-12');
        }

        $this->assertSame([], $this->app->bookingSearch()->search(self::dates('2026-12-10', '2026-12-12'), 'São Paulo'));
        $this->assertCount(1, $this->app->bookingSearch()->search(self::dates('2026-12-10', '2026-12-12'), ''), 'Só a pousada deve sobrar');
    }

    #[TestDox('Último quarto: o segundo hóspede a tentar é recusado')]
    public function testLastRoomGoesToTheFirstGuest(): void
    {
        $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');

        $this->expectExceptionObject(new DomainException('O quarto não está disponível nessas datas.'));
        $this->book('bruno@hotelflex.test', 1, '2026-12-11', '2026-12-13');
    }

    #[TestDox('Reserva aguardando pagamento segura o quarto até expirar')]
    public function testPendingPaymentHoldsTheRoom(): void
    {
        $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::DepositPlusDesk);
        $search = fn () => array_map(static fn ($offer) => $offer->room->id, $this->app->bookingSearch()->search(self::dates('2026-12-10', '2026-12-12'), 'São Paulo'));

        $this->assertNotContains(1, $search());
        $this->clock->travelTo('2026-12-02 11:00:00');
        $this->app->chargeService()->expireOverdue();
        $this->assertContains(1, $search());
    }

    #[TestDox('Café: hóspedes ocupam lugares primeiro (2 quartos × 2 = 4 de 10)')]
    public function testBreakfastSeatsCountHotelGuests(): void
    {
        $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $this->book('bruno@hotelflex.test', 2, '2026-12-10', '2026-12-11');
        $hotel = $this->app->breakfastService()->getHotelSellingBreakfast(1);

        $availability = $this->app->breakfastService()->getAvailability($hotel, new DateTimeImmutable('2026-12-11'));
        $this->assertSame(4, $availability->guestSeats, 'Quem dormiu no hotel, inclusive no dia da saída');
        $this->assertSame(6, $availability->freeSeats);
    }

    #[TestDox('Café: hóspedes + avulsos + venda na hora nunca passam da capacidade')]
    public function testBreakfastNeverExceedsCapacity(): void
    {
        $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        $this->book('bruno@hotelflex.test', 2, '2026-12-10', '2026-12-12');
        $breakfast = $this->app->breakfastService();
        $breakfast->book($this->user('carla@hotelflex.test'), 1, new DateTimeImmutable('2026-12-11'), 6, BreakfastPaymentOption::Desk);

        try {
            $breakfast->book($this->user('carla@hotelflex.test'), 1, new DateTimeImmutable('2026-12-11'), 1, BreakfastPaymentOption::Pix);
            $this->fail('Não deveria haver lugar para mais uma pessoa.');
        } catch (DomainException $error) {
            $this->assertSame('Não há mais lugares nesse dia.', $error->getMessage());
        }
        $this->clock->travelTo('2026-12-11 08:00:00');
        $this->expectExceptionObject(new DomainException('Não há mais lugares nesse dia.'));
        $breakfast->sellWalkIn($this->user('recepcao.centro@hotelflex.test'), 1, 1, PaymentMethod::Cash, '');
    }
}
