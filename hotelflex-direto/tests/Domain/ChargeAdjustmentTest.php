<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\Charge;
use HotelFlex\Domain\ChargeAdjustment;
use HotelFlex\Domain\ChargeKind;
use HotelFlex\Domain\ChargeStatus;
use PHPUnit\Framework\TestCase;

final class ChargeAdjustmentTest extends TestCase
{
    public function testNothingToDoWhenChargesMatchTheTotal(): void
    {
        $this->assertTrue(ChargeAdjustment::plan(64000, [$this->charge(1, 64000, ChargeStatus::Paid)])->isEmpty());
    }

    public function testUnpaidChargeAbsorbsTheDifference(): void
    {
        $plan = ChargeAdjustment::plan(70000, [$this->charge(7, 64000, ChargeStatus::Pending)]);

        $this->assertSame(7, $plan->pendingChargeId);
        $this->assertSame(70000, $plan->newPendingAmountCents);
        $this->assertSame(0, $plan->extraChargeCents);
    }

    public function testPaidReservationGetsANewExtraCharge(): void
    {
        $plan = ChargeAdjustment::plan(79000, [$this->charge(1, 64000, ChargeStatus::Paid)]);

        $this->assertSame(15000, $plan->extraChargeCents);
        $this->assertNull($plan->pendingChargeId);
    }

    public function testLowerTotalAfterPaymentBecomesARefund(): void
    {
        $plan = ChargeAdjustment::plan(32000, [$this->charge(1, 64000, ChargeStatus::Paid)]);

        $this->assertSame(32000, $plan->refundCents);
    }

    public function testExistingRefundIsNotCreatedTwice(): void
    {
        $charges = [$this->charge(1, 64000, ChargeStatus::Paid), $this->charge(2, 32000, ChargeStatus::RefundPending, ChargeKind::Refund)];

        $this->assertTrue(ChargeAdjustment::plan(32000, $charges)->isEmpty());
    }

    public function testRejectedAndCancelledChargesDoNotCount(): void
    {
        $charges = [$this->charge(1, 64000, ChargeStatus::Cancelled), $this->charge(2, 64000, ChargeStatus::Rejected)];

        $this->assertSame(64000, ChargeAdjustment::plan(64000, $charges)->extraChargeCents);
    }

    private function charge(int $id, int $amountCents, ChargeStatus $status, ChargeKind $kind = ChargeKind::Booking): Charge
    {
        return new Charge($id, 1, $kind, 'Teste', $amountCents, $status, null, null, null, null, null, new DateTimeImmutable('2026-11-10'));
    }
}
