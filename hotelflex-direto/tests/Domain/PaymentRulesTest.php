<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Domain;

use DateTimeImmutable;
use HotelFlex\Domain\CancellationPolicy;
use HotelFlex\Domain\Charge;
use HotelFlex\Domain\ChargeKind;
use HotelFlex\Domain\ChargeStatus;
use HotelFlex\Domain\PhoneNumber;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\Role;
use HotelFlex\Domain\User;
use PHPUnit\Framework\TestCase;

final class PaymentRulesTest extends TestCase
{
    public function testCancellationIsFreeUntilTheHotelLimitBeforeCheckIn(): void
    {
        $reservation = DomainFactory::reservation('2026-11-20');

        $this->assertSame('2026-11-18 14:00', CancellationPolicy::getFreeCancellationDeadline(DomainFactory::hotel(), $reservation)->format('Y-m-d H:i'));
        $this->assertTrue(CancellationPolicy::isRefundable(DomainFactory::hotel(), $reservation, new DateTimeImmutable('2026-11-18 13:59')));
        $this->assertFalse(CancellationPolicy::isRefundable(DomainFactory::hotel(), $reservation, new DateTimeImmutable('2026-11-18 14:01')));
    }

    public function testUnpaidReservationHoldsTheRoomAndCanStillBeChanged(): void
    {
        $reservation = DomainFactory::reservation('2026-11-20', status: ReservationStatus::PendingPayment);

        $this->assertTrue($reservation->status->isActive());
        $this->assertTrue($reservation->isEditableBy(new DateTimeImmutable('2026-11-10')));
        $this->assertFalse(ReservationStatus::Expired->isActive());
    }

    public function testOnlyPendingChargesAcceptAReceipt(): void
    {
        $this->assertTrue($this->charge(ChargeStatus::Pending)->acceptsReceipt());
        $this->assertFalse($this->charge(ChargeStatus::UnderReview)->acceptsReceipt());
        $this->assertFalse($this->charge(ChargeStatus::Paid)->acceptsReceipt());
    }

    public function testWhatsAppNeedsPhoneAndConsent(): void
    {
        $phone = PhoneNumber::fromBrazilianInput('(49) 99127-7070');

        $this->assertTrue((new User(1, 'Ana', 'a@a.com', Role::Guest, null, $phone, new DateTimeImmutable()))->canReceiveWhatsApp());
        $this->assertFalse((new User(1, 'Ana', 'a@a.com', Role::Guest, null, $phone, null))->canReceiveWhatsApp());
        $this->assertFalse((new User(1, 'Ana', 'a@a.com', Role::Guest, null, null, new DateTimeImmutable()))->canReceiveWhatsApp());
    }

    private function charge(ChargeStatus $status): Charge
    {
        return new Charge(1, 1, ChargeKind::Booking, 'Diárias', 64000, $status, null, null, null, null, null, new DateTimeImmutable());
    }
}
