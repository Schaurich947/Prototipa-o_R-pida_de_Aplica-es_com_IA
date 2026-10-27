<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Pricing;
use PHPUnit\Framework\TestCase;

final class PricingTest extends TestCase
{
    public function testThreeExtraHoursCostThreeTimesTheHourlyRate(): void
    {
        $this->assertSame(15000, Pricing::calculateLateCheckoutFee(DomainFactory::room(), 3));
    }

    public function testFeeAboveAvailableHoursIsRejected(): void
    {
        $this->expectException(DomainException::class);

        Pricing::calculateLateCheckoutFee(DomainFactory::room(), 7);
    }

    public function testBillingSummaryAddsBaseAndAdditionalValues(): void
    {
        $billing = Pricing::calculateBillingSummary(DomainFactory::room(), DomainFactory::reservation(lateCheckoutHours: 3));

        $this->assertSame(64000, $billing->baseCents);
        $this->assertSame(15000, $billing->additionalCents);
        $this->assertSame(79000, $billing->totalCents());
    }
}
