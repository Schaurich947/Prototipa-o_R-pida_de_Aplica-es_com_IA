<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Domain\BreakfastBooking;
use HotelFlex\Domain\Clock;
use HotelFlex\Fiscal\Domain\InvoiceSource;
use HotelFlex\Fiscal\Domain\InvoiceStatus;
use HotelFlex\Fiscal\Domain\InvoiceTaker;
use HotelFlex\Fiscal\Domain\ServiceType;
use HotelFlex\Fiscal\Repository\BillingProfileRepository;
use HotelFlex\Fiscal\Repository\InvoiceRepository;
use HotelFlex\Repository\ChargeRepository;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Service\StayViewFactory;

/**
 * Puts an invoice in the issue queue when the service is delivered: at check-out for lodging (with the final amount),
 * when breakfast is served for food. Advance payments (deposit, Pix) do not issue invoices by themselves.
 */
final class InvoiceRequester
{
    public function __construct(
        private readonly InvoiceRepository $invoices,
        private readonly BillingProfileRepository $profiles,
        private readonly ReservationRepository $reservations,
        private readonly ChargeRepository $charges,
        private readonly StayViewFactory $stays,
        private readonly Clock $clock,
    ) {
    }

    /** @return int|null the queued invoice, or null when nothing was paid or an invoice already exists */
    public function requestForReservation(int $reservationId): ?int
    {
        $reservation = $this->reservations->findById($reservationId);
        $amountCents = $this->charges->sumPaid($reservationId) - $this->charges->sumRefunds($reservationId);
        if ($reservation === null || $amountCents <= 0 || $this->hasActiveInvoice(InvoiceSource::Reservation, $reservationId)) {
            return null;
        }
        $stay = $this->stays->build($reservation);
        $discount = $reservation->discountPercent > 0 ? " Inclui {$reservation->discountPercent}% de desconto pela forma de pagamento." : '';
        $description = sprintf(
            'Hospedagem de %s, quarto %s (%s), de %s a %s, %d diária(s). Reserva %s.%s',
            $stay->guestName, $stay->room->number, $stay->room->category, $stay->startsAt()->format('d/m/Y H:i'), $stay->endsAt()->format('d/m/Y H:i'), $reservation->nights, $reservation->code, $discount,
        );

        return $this->create(ServiceType::Lodging, InvoiceSource::Reservation, $reservationId, $stay->hotel->id, $reservation->userId, $this->resolveTaker($reservation->billingProfileId), $description, $amountCents);
    }

    /** @param InvoiceTaker|null $counterTaker CPF or CNPJ typed by the reception for a walk-in sale */
    public function requestForBreakfast(BreakfastBooking $booking, ?InvoiceTaker $counterTaker = null): ?int
    {
        if (!$booking->isPaid || $this->hasActiveInvoice(InvoiceSource::Breakfast, $booking->id)) {
            return null;
        }
        $description = sprintf('Café da manhã para %d pessoa(s) em %s. Pedido %s.', $booking->people, $booking->date->format('d/m/Y'), $booking->code);
        $taker = $counterTaker ?? $this->resolveTaker($booking->billingProfileId);

        return $this->create(ServiceType::Food, InvoiceSource::Breakfast, $booking->id, $booking->hotelId, $booking->userId, $taker, $description, $booking->totalCents);
    }

    private function resolveTaker(?int $billingProfileId): InvoiceTaker
    {
        $profile = $billingProfileId !== null ? $this->profiles->findById($billingProfileId) : null;

        return $profile !== null ? InvoiceTaker::fromProfile($profile) : InvoiceTaker::missing();
    }

    private function hasActiveInvoice(InvoiceSource $source, int $sourceId): bool
    {
        foreach ($this->invoices->findBySource($source, $sourceId) as $invoice) {
            if ($invoice->status->isActive()) {
                return true;
            }
        }

        return false;
    }

    private function create(ServiceType $type, InvoiceSource $source, int $sourceId, int $hotelId, int $userId, InvoiceTaker $taker, string $description, int $amountCents): int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');

        return $this->invoices->create([
            'hotel_id' => $hotelId, 'user_id' => $userId, 'kind' => $type->invoiceKind()->value, 'service_type' => $type->value,
            'source_type' => $source->value, 'source_id' => $sourceId, 'status' => InvoiceStatus::Pending->value,
            'taker_name' => $taker->name, 'taker_document_type' => $taker->document?->type->value, 'taker_document' => $taker->document?->value,
            'taker_email' => $taker->email, 'taker_address' => $taker->address, 'taker_municipality_code' => $taker->municipalityCode,
            'taker_is_simples' => $taker->isSimples === null ? null : (int) $taker->isSimples, 'taker_state_registration' => $taker->stateRegistration,
            'description' => mb_substr($description, 0, 500), 'amount_cents' => $amountCents,
            'competence_date' => $this->clock->now()->format('Y-m-d'), 'next_attempt_at' => $now, 'created_at' => $now,
        ]);
    }
}
