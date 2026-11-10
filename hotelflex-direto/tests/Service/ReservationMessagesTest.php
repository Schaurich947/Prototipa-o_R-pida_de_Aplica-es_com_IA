<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Service;

use DateTimeImmutable;
use HotelFlex\Domain\Charge;
use HotelFlex\Domain\ChargeKind;
use HotelFlex\Domain\ChargeStatus;
use HotelFlex\Service\ReservationMessages;
use HotelFlex\Service\StayView;
use HotelFlex\Tests\Domain\DomainFactory;
use PHPUnit\Framework\TestCase;

final class ReservationMessagesTest extends TestCase
{
    public function testCreatedMessageTellsHowToPayAndTheDeadline(): void
    {
        $charge = new Charge(1, 1, ChargeKind::Booking, 'Diárias da reserva', 64000, ChargeStatus::Pending, new DateTimeImmutable('2026-11-11 15:00'), null, null, null, null, new DateTimeImmutable());
        $message = ReservationMessages::created($this->stay(), [$charge]);

        $this->assertStringContainsString('HF-1', $message->subject);
        $this->assertStringContainsString('R$ 640,00', $message->body);
        $this->assertStringContainsString('pix@hotel.test', $message->body);
        $this->assertStringContainsString('11/11/2026 às 15:00', $message->body);
    }

    public function testRejectionCarriesTheReason(): void
    {
        $charge = new Charge(1, 1, ChargeKind::Booking, 'Diárias da reserva', 64000, ChargeStatus::Pending, null, null, null, null, null, new DateTimeImmutable());

        $this->assertStringContainsString('Motivo: Valor diferente', ReservationMessages::paymentRejected($this->stay(), $charge, 'Valor diferente')->body);
    }

    private function stay(): StayView
    {
        return new StayView(DomainFactory::reservation(), DomainFactory::room(), DomainFactory::hotel(), 6, 6, 'Ana Souza');
    }
}
