<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RequestType;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Objetivo: nunca existirem duas estadias no mesmo quarto ao mesmo tempo, contando horas extras e 1h de limpeza.
 * Regras do HotelFlex Centro: entrada 14h, saída 12h, janela 8h–18h, 1h de limpeza entre hóspedes.
 */
#[TestDox('Datas e conflitos de reserva')]
final class DateConflictsTest extends IntegrationTestCase
{
    private const ROOM = 1;

    #[TestDox('Encaixe no mesmo dia: um sai às 12h e o próximo entra às 14h')]
    public function testSameDayTurnoverIsAllowed(): void
    {
        $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-12', '2026-12-14');

        $this->assertSame(2, $this->countRows('SELECT COUNT(*) FROM reservations WHERE room_id = 1'));
    }

    #[TestDox('Saída tardia que invadiria a chegada seguinte é bloqueada (só cabe 1h)')]
    public function testLateCheckoutCannotInvadeNextArrival(): void
    {
        $first = $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-12', '2026-12-14');
        $staff = $this->user('recepcao.centro@hotelflex.test');

        $this->app->roomService()->setFlexibleHours($staff, $first, RequestType::LateCheckout, 1);
        $this->expectExceptionObject(new DomainException('Disponível no máximo 1 hora(s) para esta reserva.'));
        $this->app->roomService()->setFlexibleHours($staff, $first, RequestType::LateCheckout, 2);
    }

    #[TestDox('Nova reserva não cabe depois de uma saída tardia já combinada')]
    public function testBookingAfterAgreedLateCheckoutIsBlocked(): void
    {
        $first = $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $this->app->roomService()->setFlexibleHours($this->user('recepcao.centro@hotelflex.test'), $first, RequestType::LateCheckout, 2);

        $this->expectExceptionObject(new DomainException('O quarto não está disponível nessas datas.'));
        $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-12', '2026-12-14');
    }

    #[TestDox('Limite exato: saída às 13h + 1h de limpeza = próxima entrada às 14h é permitida')]
    public function testExactCleaningBoundaryIsAllowed(): void
    {
        $first = $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $this->app->roomService()->setFlexibleHours($this->user('recepcao.centro@hotelflex.test'), $first, RequestType::LateCheckout, 1);

        $this->assertGreaterThan(0, $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-12', '2026-12-14'));
    }

    #[TestDox('Reserva que fica dentro de outra é bloqueada')]
    public function testReservationInsideAnotherIsBlocked(): void
    {
        $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-20');

        $this->expectExceptionObject(new DomainException('O quarto não está disponível nessas datas.'));
        $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-12', '2026-12-15');
    }

    #[TestDox('Reserva que "abraça" outra (começa antes e termina depois) é bloqueada')]
    public function testReservationAroundAnotherIsBlocked(): void
    {
        $this->book('ana@hotelflex.test', self::ROOM, '2026-12-12', '2026-12-15');

        $this->expectExceptionObject(new DomainException('O quarto não está disponível nessas datas.'));
        $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-05', '2026-12-25');
    }

    #[TestDox('Adiar para cima de outra reserva é recusado e nada muda (datas e cobranças)')]
    public function testChangingDatesOntoAnotherChangesNothing(): void
    {
        $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $bruno = $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-15', '2026-12-17');
        $chargesBefore = $this->charges($bruno);

        try {
            $this->app->reservationService()->changeDates($this->user('bruno@hotelflex.test'), $bruno, self::dates('2026-12-11', '2026-12-13'));
            $this->fail('A alteração deveria ter sido recusada.');
        } catch (DomainException $error) {
            $this->assertSame('O quarto não está disponível nessas datas.', $error->getMessage());
        }
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) FROM reservations WHERE id = :id AND check_in_date = '2026-12-15' AND nights = 2", ['id' => $bruno]));
        $this->assertSame($chargesBefore, $this->charges($bruno));
    }

    #[TestDox('Reserva cancelada libera o quarto na hora')]
    public function testCancelledReservationFreesTheRoom(): void
    {
        $ana = $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $this->app->reservationService()->cancel($this->user('ana@hotelflex.test'), $ana);

        $this->assertGreaterThan(0, $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12'));
    }

    #[TestDox('Reserva expirada (sem comprovante no prazo) libera o quarto')]
    public function testExpiredReservationFreesTheRoom(): void
    {
        $ana = $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $this->clock->travelTo('2026-12-02 10:01:00');
        $this->app->chargeService()->expireOverdue();

        $this->assertSame('expired', $this->reservationStatus($ana));
        $this->assertGreaterThan(0, $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12'));
    }

    #[TestDox('Entrada antecipada fica limitada por quem sai do quarto no mesmo dia')]
    public function testEarlyCheckInLimitedByPreviousGuest(): void
    {
        $this->book('ana@hotelflex.test', self::ROOM, '2026-12-10', '2026-12-12');
        $bruno = $this->book('bruno@hotelflex.test', self::ROOM, '2026-12-12', '2026-12-14');
        $staff = $this->user('recepcao.centro@hotelflex.test');

        $this->app->roomService()->setFlexibleHours($staff, $bruno, RequestType::EarlyCheckIn, 1);
        $this->expectExceptionObject(new DomainException('Disponível no máximo 1 hora(s) para esta reserva.'));
        $this->app->roomService()->setFlexibleHours($staff, $bruno, RequestType::EarlyCheckIn, 2);
    }
}
