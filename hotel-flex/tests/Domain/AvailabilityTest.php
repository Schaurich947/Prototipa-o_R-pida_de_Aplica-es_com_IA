<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\RoomStatus;
use PHPUnit\Framework\TestCase;

final class AvailabilityTest extends TestCase
{
    private const TODAY = '2026-10-27';

    public function testRoomCleanedBeforeCheckInForTodaysArrivalIsEarlyVacant(): void
    {
        $this->assertTrue(Availability::isEarlyVacant(DomainFactory::hotel(), DomainFactory::room(), DomainFactory::reservation(), new DateTimeImmutable(self::TODAY)));
    }

    public function testRoomWithArrivalOnAnotherDayIsNotEarlyVacant(): void
    {
        $arrival = DomainFactory::reservation('2026-10-30');

        $this->assertFalse(Availability::isEarlyVacant(DomainFactory::hotel(), DomainFactory::room(), $arrival, new DateTimeImmutable(self::TODAY)));
    }

    public function testRoomStillCleaningIsNotEarlyVacant(): void
    {
        $room = DomainFactory::room(RoomStatus::Cleaning, null);

        $this->assertFalse(Availability::isEarlyVacant(DomainFactory::hotel(), $room, DomainFactory::reservation(), new DateTimeImmutable(self::TODAY)));
    }

    public function testEarlyCheckInGoesBackToHotelOpeningWhenNoOneLeavesThatDay(): void
    {
        $this->assertSame(6, Availability::getMaxEarlyCheckInHours(DomainFactory::hotel(), DomainFactory::reservation(), null));
    }

    public function testEarlyCheckInLeavesCleaningTimeAfterPreviousGuest(): void
    {
        $previous = DomainFactory::reservation('2026-10-25', 2, lateHours: 0, id: 2);

        $this->assertSame(1, Availability::getMaxEarlyCheckInHours(DomainFactory::hotel(), DomainFactory::reservation(), $previous));
    }

    public function testLateCheckoutStopsBeforeNextGuestArrivesEarly(): void
    {
        $next = DomainFactory::reservation('2026-10-29', 1, earlyHours: 2, id: 2);

        // Next guest arrives at 12h (14h - 2h early); minus 1h cleaning leaves no extra hour after 12h.
        $this->assertSame(0, Availability::getMaxLateCheckoutHours(DomainFactory::hotel(), DomainFactory::reservation(), $next));
    }

    public function testLateCheckoutGoesUntilHotelLimitWithoutNextGuest(): void
    {
        $this->assertSame(6, Availability::getMaxLateCheckoutHours(DomainFactory::hotel(), DomainFactory::reservation(), null));
    }

    public function testOverlappingStayIsAConflict(): void
    {
        $other = DomainFactory::reservation('2026-10-28', 2, id: 2);
        $startsAt = new DateTimeImmutable('2026-10-27 14:00');

        $this->assertFalse(Availability::hasConflict(DomainFactory::hotel(), $startsAt, $startsAt->modify('+1 day')->setTime(12, 0), [$other]));
        $this->assertTrue(Availability::hasConflict(DomainFactory::hotel(), $startsAt, $startsAt->modify('+2 days')->setTime(12, 0), [$other]));
    }

    public function testSameDayTurnoverConflictsWhenPreviousGuestLeavesLate(): void
    {
        $previous = DomainFactory::reservation('2026-10-25', 2, lateHours: 2, id: 2);
        $startsAt = new DateTimeImmutable('2026-10-27 14:00');

        $this->assertTrue(Availability::hasConflict(DomainFactory::hotel(), $startsAt, $startsAt->modify('+1 day')->setTime(12, 0), [$previous]));
    }

    public function testHoursAboveLimitAreRejected(): void
    {
        $this->expectException(DomainException::class);

        Availability::assertHoursWithinLimit(4, 3);
    }
}
