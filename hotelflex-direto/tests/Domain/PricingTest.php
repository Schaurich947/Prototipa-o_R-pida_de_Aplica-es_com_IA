<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use HotelFlex\Domain\Pricing;
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
}
