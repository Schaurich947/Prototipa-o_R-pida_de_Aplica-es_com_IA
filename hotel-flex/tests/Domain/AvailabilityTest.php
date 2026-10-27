<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use HotelFlex\Domain\Availability;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RoomStatus;
use PHPUnit\Framework\TestCase;

final class AvailabilityTest extends TestCase
{
    public function testRoomCleanedBeforeCheckInIsEarlyVacant(): void
    {
        $this->assertTrue(Availability::isEarlyVacant(DomainFactory::room(), DomainFactory::reservation()));
    }

    public function testRoomCleanedAfterCheckInIsNotEarlyVacant(): void
    {
        $room = DomainFactory::room(cleanedAt: '2026-10-27 15:00');

        $this->assertFalse(Availability::isEarlyVacant($room, DomainFactory::reservation()));
    }

    public function testRoomStillCleaningIsNotEarlyVacant(): void
    {
        $room = DomainFactory::room(RoomStatus::Cleaning, null);

        $this->assertFalse(Availability::isEarlyVacant($room, DomainFactory::reservation()));
    }

    public function testRoomWithoutReservationIsNotEarlyVacant(): void
    {
        $this->assertFalse(Availability::isEarlyVacant(DomainFactory::room(), null));
    }

    public function testMaxLateCheckoutHoursRespectsNextBookingLimit(): void
    {
        $this->assertSame(6, Availability::getMaxLateCheckoutHours(DomainFactory::room()));
        $this->assertSame(3, Availability::getMaxLateCheckoutHours(DomainFactory::room(checkoutLimitHour: 15)));
    }

    public function testHoursAboveLimitAreRejected(): void
    {
        $this->expectException(DomainException::class);

        Availability::assertValidLateCheckoutHours(DomainFactory::room(checkoutLimitHour: 15), 4);
    }

    public function testNegativeHoursAreRejected(): void
    {
        $this->expectException(DomainException::class);

        Availability::assertValidLateCheckoutHours(DomainFactory::room(), -1);
    }

    public function testCheckoutHourAddsExtraHoursToStandardCheckout(): void
    {
        $this->assertSame(15, Availability::getCheckoutHour(3));
    }
}
