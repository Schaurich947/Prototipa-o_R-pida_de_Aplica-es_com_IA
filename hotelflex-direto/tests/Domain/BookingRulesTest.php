<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\Charge;
use HotelFlex\Domain\ChargeKind;
use HotelFlex\Domain\ChargeStatus;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Domain\StayDates;
use PHPUnit\Framework\TestCase;

final class BookingRulesTest extends TestCase
{
    public function testNightsComeFromTheDateRange(): void
    {
        $this->assertSame(1, (new StayDates(new DateTimeImmutable('2026-11-11'), new DateTimeImmutable('2026-11-12')))->nights);
        $this->assertSame(3, (new StayDates(new DateTimeImmutable('2026-11-29'), new DateTimeImmutable('2026-12-02')))->nights);
    }

    public function testCheckOutMustBeAfterCheckIn(): void
    {
        $this->expectException(DomainException::class);

        (new StayDates(new DateTimeImmutable('2026-11-12'), new DateTimeImmutable('2026-11-12')))->assertValid(new DateTimeImmutable('2026-11-10'));
    }

    public function testCheckInCannotBeInThePast(): void
    {
        $this->expectException(DomainException::class);

        (new StayDates(new DateTimeImmutable('2026-11-09'), new DateTimeImmutable('2026-11-12')))->assertValid(new DateTimeImmutable('2026-11-10'));
    }

    public function testFullPaymentIsASingleCharge(): void
    {
        $this->assertSame([64000], PaymentPlan::Full->splitAmount(64000));
    }

    public function testDepositPartsAlwaysAddUpToTheTotal(): void
    {
        $this->assertSame([32000, 32000], PaymentPlan::Deposit->splitAmount(64000));
        $this->assertSame([16000, 16001], PaymentPlan::Deposit->splitAmount(32001));
    }

    public function testDeskPlanKeepsThePixDepositAndMovesTheRestToTheDesk(): void
    {
        $this->assertSame([32000, 32000], PaymentPlan::DepositPlusDesk->splitAmount(64000));
        $this->assertTrue(PaymentPlan::DepositPlusDesk->isRestPaidAtDesk());
        $this->assertFalse(PaymentPlan::Deposit->isRestPaidAtDesk());
    }

    public function testDeskChargeAsksNoReceiptButCanBeReceivedInPerson(): void
    {
        $deskCharge = new Charge(1, 1, ChargeKind::Booking, 'Restante', 32000, ChargeStatus::Pending, null, null, null, null, null, new DateTimeImmutable(), true);

        $this->assertFalse($deskCharge->acceptsReceipt());
        $this->assertTrue($deskCharge->canBeReceivedAtDesk());
    }

    public function testRefundsAreNeverReceivedAtTheDesk(): void
    {
        $refund = new Charge(1, 1, ChargeKind::Refund, 'Devolução', 32000, ChargeStatus::Pending, null, null, null, null, null, new DateTimeImmutable());

        $this->assertFalse($refund->canBeReceivedAtDesk());
    }

    public function testCardsAreOnlyTakenAtTheDeskTerminal(): void
    {
        $this->assertNotContains(PaymentMethod::PixReceipt, PaymentMethod::deskMethods());
        $this->assertContains(PaymentMethod::Cash, PaymentMethod::deskMethods());
        $this->assertContains(PaymentMethod::CreditCard, PaymentMethod::deskMethods());
    }

    public function testPayingEarlierGivesABiggerDiscount(): void
    {
        $this->assertSame(5, PaymentPlan::Full->discountPercent());
        $this->assertSame(2, PaymentPlan::Deposit->discountPercent());
        $this->assertSame(0, PaymentPlan::DepositPlusDesk->discountPercent());
        $this->assertSame(3200, PaymentPlan::calculateDiscount(64000, 5));
    }

    public function testTwoStepPixRestIsDueADayBeforeCheckIn(): void
    {
        $checkInAt = new DateTimeImmutable('2026-11-13 15:00');

        $this->assertSame('2026-11-12 15:00', PaymentPlan::Deposit->getRestDueAt($checkInAt)?->format('Y-m-d H:i'));
        $this->assertSame('2026-11-13 15:00', PaymentPlan::DepositPlusDesk->getRestDueAt($checkInAt)?->format('Y-m-d H:i'));
        $this->assertNull(PaymentPlan::Full->getRestDueAt($checkInAt));
    }

    public function testTwoStepPixIsUnavailableWhenCheckInIsTooClose(): void
    {
        $checkInAt = new DateTimeImmutable('2026-11-11 14:00');
        $firstDueAt = new DateTimeImmutable('2026-11-11 08:00');

        $this->assertFalse(PaymentPlan::Deposit->isAvailable($firstDueAt, $checkInAt));
        $this->assertTrue(PaymentPlan::Full->isAvailable($firstDueAt, $checkInAt));
        $this->assertTrue(PaymentPlan::DepositPlusDesk->isAvailable($firstDueAt, $checkInAt));
    }
}
