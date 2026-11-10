<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\BreakfastBooking;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\BreakfastStatus;
use PHPUnit\Framework\TestCase;

final class BreakfastRulesTest extends TestCase
{
    public function testPixAheadGetsTheSameDiscountAsFullBookingPayment(): void
    {
        $this->assertSame(8550, BreakfastPaymentOption::Pix->calculateTotal(4500, 2));
        $this->assertSame(9000, BreakfastPaymentOption::Desk->calculateTotal(4500, 2));
    }

    public function testOnlyActiveBookingsTakeSeats(): void
    {
        $this->assertTrue(BreakfastStatus::PendingPayment->holdsSeat());
        $this->assertTrue(BreakfastStatus::Served->holdsSeat());
        $this->assertFalse(BreakfastStatus::Cancelled->holdsSeat());
        $this->assertFalse(BreakfastStatus::Expired->holdsSeat());
    }

    public function testPixBookingWaitsForReceiptAndDeskBookingIsPaidInPerson(): void
    {
        $pix = $this->booking(BreakfastPaymentOption::Pix, BreakfastStatus::PendingPayment);
        $desk = $this->booking(BreakfastPaymentOption::Desk, BreakfastStatus::Confirmed);

        $this->assertTrue($pix->acceptsReceipt());
        $this->assertFalse($desk->acceptsReceipt());
        $this->assertTrue($desk->canBeReceivedAtDesk());
    }

    public function testCanBeCancelledOnlyBeforeBreakfastStarts(): void
    {
        $booking = $this->booking(BreakfastPaymentOption::Desk, BreakfastStatus::Confirmed);
        $hotel = DomainFactory::hotel();

        $this->assertTrue($booking->isCancellableBy(new DateTimeImmutable('2026-11-12 05:59'), $hotel));
        $this->assertFalse($booking->isCancellableBy(new DateTimeImmutable('2026-11-12 06:00'), $hotel));
    }

    private function booking(BreakfastPaymentOption $option, BreakfastStatus $status): BreakfastBooking
    {
        return new BreakfastBooking(1, 'CF-1', 1, 1, new DateTimeImmutable('2026-11-12'), 2, 4500, 0, 9000, $option, $status, false, null, null, null, null, null, null);
    }
}
