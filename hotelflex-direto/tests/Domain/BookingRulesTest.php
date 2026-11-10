<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\DomainException;
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
}
