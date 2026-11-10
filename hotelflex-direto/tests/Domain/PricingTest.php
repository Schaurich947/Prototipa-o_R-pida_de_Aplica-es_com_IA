<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Domain\Pricing;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class PricingTest extends TestCase
{
    public function testEarlyCheckInChargesOnlyHoursBeyondTolerance(): void
    {
        $this->assertSame(10000, Pricing::calculateEarlyCheckInFee(DomainFactory::hotel(earlyFreeHours: 1), DomainFactory::room(), 3));
    }

    public function testEarlyCheckInWithinToleranceIsFree(): void
    {
        $this->assertSame(0, Pricing::calculateEarlyCheckInFee(DomainFactory::hotel(earlyFreeHours: 1), DomainFactory::room(), 1));
    }

    public function testLateCheckoutChargesEveryHour(): void
    {
        $this->assertSame(15000, Pricing::calculateLateCheckoutFee(DomainFactory::room(), 3));
    }

    public function testBillingSummaryAddsBaseEarlyAndLateValues(): void
    {
        $reservation = DomainFactory::reservation(earlyHours: 2, lateHours: 3);
        $billing = Pricing::calculateBillingSummary(DomainFactory::hotel(), DomainFactory::room(), $reservation);

        $this->assertSame(64000, $billing->baseCents);
        $this->assertSame(5000, $billing->earlyCheckInCents);
        $this->assertSame(15000, $billing->lateCheckoutCents);
        $this->assertSame(84000, $billing->totalCents());
    }

    public function testDiscountAppliesToTheNightsOnlyNotToExtraHours(): void
    {
        $reservation = new Reservation(1, 'HF-1', 10, 1, new DateTimeImmutable('2026-10-27'), 2, 0, 3, ReservationStatus::Confirmed, PaymentPlan::Full, 5);
        $billing = Pricing::calculateBillingSummary(DomainFactory::hotel(), DomainFactory::room(), $reservation);

        $this->assertSame(3200, $billing->discountCents);
        $this->assertSame(64000 - 3200 + 15000, $billing->totalCents());
    }
}
