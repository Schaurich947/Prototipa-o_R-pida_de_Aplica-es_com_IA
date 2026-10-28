<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    public function testFlexibleHoursMoveTheStayBoundaries(): void
    {
        $reservation = DomainFactory::reservation(earlyHours: 3, lateHours: 2);

        $this->assertSame('2026-10-27 11:00', $reservation->startsAt(DomainFactory::hotel())->format('Y-m-d H:i'));
        $this->assertSame('2026-10-29 14:00', $reservation->endsAt(DomainFactory::hotel())->format('Y-m-d H:i'));
    }

    public function testOnlyFutureConfirmedReservationsAreEditable(): void
    {
        $today = new DateTimeImmutable('2026-10-27 09:00');

        $this->assertTrue(DomainFactory::reservation('2026-10-27')->isEditableBy($today));
        $this->assertFalse(DomainFactory::reservation('2026-10-26')->isEditableBy($today));
        $this->assertFalse(DomainFactory::reservation(status: ReservationStatus::CheckedIn)->isEditableBy($today));
    }
}
