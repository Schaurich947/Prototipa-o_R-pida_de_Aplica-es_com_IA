<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use DateTimeImmutable;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Repository\ChargeRepository;
use HotelFlex\Service\StoredReceipt;
use PHPUnit\Framework\Attributes\TestDox;

/** Objetivo: prazos e janelas funcionam no limite exato (1 minuto antes / 1 minuto depois). */
#[TestDox('Prazos e tempo')]
final class DeadlinesTest extends IntegrationTestCase
{
    #[TestDox('Prazo do Pix: 1 minuto antes de vencer a reserva continua; 1 minuto depois, expira')]
    public function testPaymentDeadlineBoundary(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');

        $this->clock->travelTo('2026-12-02 09:59:00');
        $this->assertSame(0, $this->app->chargeService()->expireOverdue());
        $this->clock->travelTo('2026-12-02 10:01:00');
        $this->assertSame(1, $this->app->chargeService()->expireOverdue());
        $this->assertSame('expired', $this->reservationStatus($reservation));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) FROM notifications WHERE reservation_id = :id AND subject LIKE '%expirada%' AND channel = 'email'", ['id' => $reservation]));
    }

    #[TestDox('Comprovante enviado dentro do prazo não deixa a reserva expirar enquanto o hotel confere')]
    public function testReceiptUnderReviewDoesNotExpire(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12');
        (new ChargeRepository($this->db))->attachReceipt($this->openCharges($reservation)[0]['id'], new StoredReceipt('teste.pdf', 'pix.pdf', 'application/pdf'));

        $this->clock->travelTo('2026-12-05 10:00:00');
        $this->assertSame(0, $this->app->chargeService()->expireOverdue());
        $this->assertSame('pending_payment', $this->reservationStatus($reservation));
    }

    #[TestDox('Cancelamento grátis até 48h antes: 1 minuto antes devolve, 1 minuto depois não')]
    public function testFreeCancellationBoundary(): void
    {
        $refundWhenCancelledAt = function (string $moment, int $roomId): int {
            $this->clock->travelTo(self::DEFAULT_NOW);
            $reservation = $this->book('ana@hotelflex.test', $roomId, '2026-12-10', '2026-12-12');
            $this->payFirstCharge($reservation);
            $this->clock->travelTo($moment);
            $this->app->reservationService()->cancel($this->user('ana@hotelflex.test'), $reservation);

            return $this->countRows("SELECT COALESCE(SUM(amount_cents), 0) FROM charges WHERE reservation_id = :id AND status = 'refund_pending'", ['id' => $reservation]);
        };

        $this->assertSame(60800, $refundWhenCancelledAt('2026-12-08 13:59:00', 1));
        $this->assertSame(0, $refundWhenCancelledAt('2026-12-08 14:01:00', 2));
    }

    #[TestDox('"50% + restante 24h antes" fica indisponível quando a entrada é no dia seguinte')]
    public function testDepositPlanUnavailableWhenCheckInIsClose(): void
    {
        $this->expectExceptionObject(new DomainException('Esta forma de pagamento não está disponível para uma entrada tão próxima. Escolha outra.'));
        $this->book('ana@hotelflex.test', 1, '2026-12-02', '2026-12-03', PaymentPlan::Deposit);
    }

    #[TestDox('Virada da meia-noite: às 23:59 ainda dá para reservar para hoje; à 00:01, "hoje" já é ontem')]
    public function testMidnightBoundary(): void
    {
        $this->clock->travelTo('2026-12-01 23:59:00');
        $this->assertGreaterThan(0, $this->book('ana@hotelflex.test', 1, '2026-12-01', '2026-12-02', PaymentPlan::DepositPlusDesk));

        $this->clock->travelTo('2026-12-02 00:01:00');
        $this->expectExceptionObject(new DomainException('A data de entrada não pode estar no passado.'));
        $this->book('bruno@hotelflex.test', 2, '2026-12-01', '2026-12-02', PaymentPlan::DepositPlusDesk);
    }

    #[TestDox('Café: venda na hora só durante o horário (8h sim, 10h não)')]
    public function testWalkInOnlyDuringBreakfastHours(): void
    {
        $staff = $this->user('recepcao.centro@hotelflex.test');
        $this->clock->travelTo('2026-12-01 08:00:00');
        $this->app->breakfastService()->sellWalkIn($staff, 1, 2, PaymentMethod::Cash, 'Visitante');
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) FROM breakfast_bookings WHERE walk_in_name = 'Visitante' AND total_cents = 11000"));

        $this->clock->travelTo('2026-12-01 10:00:00');
        $this->expectExceptionObject(new DomainException('O café desse dia já terminou. Escolha outra data.'));
        $this->app->breakfastService()->sellWalkIn($staff, 1, 1, PaymentMethod::Cash, '');
    }

    #[TestDox('Café com Pix sem comprovante até o início do café expira e libera os lugares')]
    public function testUnpaidBreakfastExpires(): void
    {
        $this->app->breakfastService()->book($this->user('carla@hotelflex.test'), 1, new DateTimeImmutable('2026-12-02'), 4, BreakfastPaymentOption::Pix);
        $this->clock->travelTo('2026-12-02 06:01:00');

        $this->assertSame(1, $this->app->breakfastService()->expireOverdue());
        $hotel = $this->app->breakfastService()->getHotelSellingBreakfast(1);
        $this->assertSame(10, $this->app->breakfastService()->getAvailability($hotel, new DateTimeImmutable('2026-12-02'))->freeSeats);
    }
}
