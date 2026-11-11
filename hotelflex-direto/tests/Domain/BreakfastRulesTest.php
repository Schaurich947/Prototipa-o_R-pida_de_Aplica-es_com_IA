<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\BreakfastAvailability;
use HotelFlex\Domain\BreakfastBooking;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\BreakfastStatus;
use HotelFlex\Domain\Hotel;
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

    public function testHotelGuestsTakeSeatsBeforeStandaloneSales(): void
    {
        $availability = new BreakfastAvailability(40, 5, 2, 7);

        $this->assertSame(10, $availability->guestSeats);
        $this->assertSame(23, $availability->freeSeats);
        $this->assertSame(0, (new BreakfastAvailability(10, 6, 2, 0))->freeSeats);
    }

    public function testWalkInIsSoldOnlyAboveTheRegularPrice(): void
    {
        $hotel = static fn (int $walkInCents) => new Hotel(1, 'H', 'SP', 14, 12, 8, 18, 1, 1, 'pix', null, 24, 48, 4500, 6, 10, 40, $walkInCents, 2);

        $this->assertTrue($hotel(5500)->sellsWalkInBreakfast());
        $this->assertFalse($hotel(4500)->sellsWalkInBreakfast());
        $this->assertSame(11000, BreakfastPaymentOption::WalkIn->calculateTotal(5500, 2));
        $this->assertNotContains(BreakfastPaymentOption::WalkIn, BreakfastPaymentOption::bookingOptions());
    }
}
