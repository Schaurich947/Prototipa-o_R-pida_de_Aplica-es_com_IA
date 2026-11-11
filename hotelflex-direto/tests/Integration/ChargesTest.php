<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Integration;

use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Domain\RequestType;
use PHPUnit\Framework\Attributes\TestDox;

/** Objetivo: o dinheiro sempre certo até o centavo, em qualquer sequência de mudanças. */
#[TestDox('Valores e cobranças')]
final class ChargesTest extends IntegrationTestCase
{
    #[TestDox('Diárias à vista recebem 5% de desconto (2 × R$ 320 = R$ 608,00)')]
    public function testFullPaymentDiscount(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Full);

        $this->assertSame([60800], array_column($this->charges($reservation), 'amount'));
    }

    #[TestDox('Sinal com valor ímpar: as duas partes somam exatamente o total')]
    public function testDepositPartsAddUpToTheTotal(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 3, '2026-12-10', '2026-12-13', PaymentPlan::Deposit);
        $amounts = array_column($this->charges($reservation), 'amount');

        $this->assertSame(132300, array_sum($amounts), '3 × R$ 450 com 2% de desconto');
        $this->assertSame([66150, 66150], $amounts);
    }

    #[TestDox('Desconto vale só nas diárias: 3h de saída tardia custam R$ 150 cheios')]
    public function testExtraHoursAreNotDiscounted(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Full);
        $this->payFirstCharge($reservation);
        $this->app->roomService()->setFlexibleHours($this->user('recepcao.centro@hotelflex.test'), $reservation, RequestType::LateCheckout, 3);

        $extra = $this->openCharges($reservation)[0];
        $this->assertSame(['extra', 15000], [$extra['kind'], $extra['amount']]);
    }

    #[TestDox('Adiar para mais diárias depois de pagar gera cobrança só da diferença')]
    public function testLongerStayChargesTheDifference(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Full);
        $this->payFirstCharge($reservation);
        $this->app->reservationService()->changeDates($this->user('ana@hotelflex.test'), $reservation, self::dates('2026-12-10', '2026-12-13'));

        $this->assertSame(30400, $this->openCharges($reservation)[0]['amount'], 'R$ 912 (3 diárias com 5%) − R$ 608 pagos');
    }

    #[TestDox('Encurtar a estadia depois de pagar cancela a cobrança aberta e gera crédito do excedente')]
    public function testShorterStayBecomesRefund(): void
    {
        $ana = $this->user('ana@hotelflex.test');
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Full);
        $this->payFirstCharge($reservation);
        $this->app->reservationService()->changeDates($ana, $reservation, self::dates('2026-12-10', '2026-12-13'));
        $this->app->reservationService()->changeDates($ana, $reservation, self::dates('2026-12-10', '2026-12-11'));

        $byStatus = array_column($this->charges($reservation), 'amount', 'status');
        $this->assertSame(60800, $byStatus['paid']);
        $this->assertSame(30400, $byStatus['refund_pending'], 'Pagou R$ 608, a nova diária custa R$ 304');
        $this->assertSame([], $this->openCharges($reservation));
    }

    #[TestDox('Sequência hora extra → adiar → cancelar no prazo: devolve exatamente o que foi pago')]
    public function testSequenceEndsWithExactRefund(): void
    {
        $ana = $this->user('ana@hotelflex.test');
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Full);
        $this->payFirstCharge($reservation);
        $this->app->roomService()->setFlexibleHours($this->user('recepcao.centro@hotelflex.test'), $reservation, RequestType::LateCheckout, 2);
        $this->app->reservationService()->changeDates($ana, $reservation, self::dates('2026-12-11', '2026-12-13'));
        $this->app->reservationService()->cancel($ana, $reservation);

        $byStatus = array_column($this->charges($reservation), 'amount', 'status');
        $this->assertSame(60800, $byStatus['refund_pending']);
        $this->assertSame(0, $this->countRows("SELECT COUNT(*) FROM charges WHERE reservation_id = :id AND status = 'pending'", ['id' => $reservation]));
    }

    #[TestDox('Cancelar antes de pagar não gera devolução e cancela as cobranças')]
    public function testCancelBeforePaymentHasNoRefund(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Deposit);
        $this->app->reservationService()->cancel($this->user('ana@hotelflex.test'), $reservation);

        $this->assertSame(['cancelled', 'cancelled'], array_column($this->charges($reservation), 'status'));
    }

    #[TestDox('Pagar a reserva inteira no balcão também confirma a reserva')]
    public function testDeskPaymentConfirmsTheReservation(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Full);
        $charge = $this->openCharges($reservation)[0];
        $this->app->chargeService()->receiveAtDesk($this->user('recepcao.centro@hotelflex.test'), $charge['id'], PaymentMethod::CreditCard);

        $this->assertSame('confirmed', $this->reservationStatus($reservation));
        $this->assertSame(1, $this->countRows("SELECT COUNT(*) FROM charges WHERE id = :id AND paid_method = 'credit_card'", ['id' => $charge['id']]));
    }

    #[TestDox('Cada mudança de valor avisa o hóspede (fila de mensagens)')]
    public function testValueChangesNotifyTheGuest(): void
    {
        $reservation = $this->book('ana@hotelflex.test', 1, '2026-12-10', '2026-12-12', PaymentPlan::Full);
        $this->payFirstCharge($reservation);
        $this->app->roomService()->setFlexibleHours($this->user('recepcao.centro@hotelflex.test'), $reservation, RequestType::LateCheckout, 3);

        $subjects = $this->db->query("SELECT DISTINCT subject FROM notifications WHERE reservation_id = {$reservation} AND user_id = 4")->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertContains("Reserva {$this->code($reservation)} · nova cobrança", $subjects);
        $this->assertContains("Reserva {$this->code($reservation)} · reserva confirmada", $subjects);
    }

    private function code(int $reservationId): string
    {
        return (string) $this->db->query("SELECT code FROM reservations WHERE id = {$reservationId}")->fetchColumn();
    }
}
