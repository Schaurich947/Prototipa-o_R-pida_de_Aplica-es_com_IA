<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Charge;
use HotelFlex\Domain\ChargeAdjustment;
use HotelFlex\Domain\ChargeKind;
use HotelFlex\Domain\ChargeStatus;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\PaymentPlan;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\Role;
use HotelFlex\Domain\User;
use HotelFlex\Repository\ChargeRepository;
use HotelFlex\Repository\ReservationRepository;

/** Pix payments straight to the hotel: charge, receipt, review, adjustments and refunds. */
final class ChargeService
{
    private const MAX_REASON_LENGTH = 255;

    public function __construct(
        private readonly ChargeRepository $charges,
        private readonly ReservationRepository $reservations,
        private readonly AccessGuard $guard,
        private readonly StayViewFactory $stays,
        private readonly ReceiptStorage $receipts,
        private readonly Notifier $notifier,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    /**
     * Paying the first charge (full amount or deposit) confirms the reservation;
     * with a deposit, the rest is due by check-in time.
     *
     * @return list<Charge>
     */
    public function createBookingCharges(StayView $stay, PaymentPlan $plan): array
    {
        $now = $this->clock->now();
        $parts = $plan->splitAmount($stay->billing->totalCents());
        $descriptions = $plan === PaymentPlan::Full
            ? ['Diárias da reserva']
            : ['Sinal de ' . PaymentPlan::DEPOSIT_PERCENT . '% das diárias', $plan->isRestPaidAtDesk() ? 'Restante das diárias (pagar no hotel)' : 'Restante das diárias'];
        $firstDueAt = $now->modify("+{$stay->hotel->paymentDeadlineHours} hours");
        if (!$plan->isAvailable($firstDueAt, $stay->startsAt())) {
            throw new DomainException('Esta forma de pagamento não está disponível para uma entrada tão próxima. Escolha outra.');
        }
        $dueDates = [$firstDueAt, $plan->getRestDueAt($stay->startsAt())];

        $charges = [];
        foreach ($parts as $index => $amountCents) {
            $payAtDesk = $index === 1 && $plan->isRestPaidAtDesk();
            $chargeId = $this->charges->create($stay->reservation->id, ChargeKind::Booking, $descriptions[$index], $amountCents, ChargeStatus::Pending, $dueDates[$index], $now, $payAtDesk);
            $charges[] = $this->charges->findById($chargeId) ?? throw new DomainException('Cobrança não encontrada.');
        }

        return $charges;
    }

    /** @param array<string, mixed> $uploadedFile one entry of $_FILES */
    public function submitReceipt(User $actor, int $chargeId, array $uploadedFile): void
    {
        $charge = $this->charges->findById($chargeId) ?? throw new DomainException('Cobrança não encontrada.');
        $stay = $this->stays->build($this->guard->getOwnReservation($actor, $charge->reservationId));
        if (!$charge->acceptsReceipt() || !$stay->reservation->status->isActive()) {
            throw new DomainException('Esta cobrança não aguarda comprovante.');
        }
        $receipt = $this->receipts->storeUpload($uploadedFile);
        if (!$this->charges->attachReceipt($charge->id, $receipt)) {
            $this->receipts->delete($receipt->storedName);
            throw new DomainException('Esta cobrança não aguarda mais comprovante.');
        }
        $this->notifier->notifyHotelStaff($stay, ReservationMessages::receiptReceived($stay, $charge));
    }

    /** Approving the booking charge confirms the reservation; rejecting asks for a new receipt. */
    public function reviewReceipt(User $actor, int $chargeId, bool $isApproved, string $reason): void
    {
        $charge = $this->charges->findById($chargeId) ?? throw new DomainException('Cobrança não encontrada.');
        $reservation = $this->guard->getManagedReservation($actor, $charge->reservationId);
        $reason = mb_substr(trim($reason), 0, self::MAX_REASON_LENGTH);
        if (!$isApproved && mb_strlen($reason) < 3) {
            throw new DomainException('Informe o motivo da recusa para o hóspede.');
        }
        $this->transaction->run(function () use ($charge, $reservation, $isApproved, $reason): void {
            if (!$this->charges->markReviewed($charge->id, $isApproved, $isApproved ? null : $reason, $this->clock->now())) {
                throw new DomainException('Este comprovante já foi conferido.');
            }
            $isConfirming = $isApproved && $charge->kind === ChargeKind::Booking && $reservation->status === ReservationStatus::PendingPayment;
            if ($isConfirming && !$this->reservations->updateStatus($reservation->id, ReservationStatus::PendingPayment, ReservationStatus::Confirmed)) {
                throw new DomainException('A reserva mudou de situação. Atualize a página.');
            }
            $stay = $this->stays->build($this->reservations->findById($reservation->id) ?? $reservation);
            $this->notifier->notifyGuest($stay, $isApproved
                ? ReservationMessages::paymentApproved($stay, $charge, $isConfirming)
                : ReservationMessages::paymentRejected($stay, $charge, $reason));
        });
    }

    /** The reception takes the payment in person; paying the booking this way also confirms the reservation. */
    public function receiveAtDesk(User $actor, int $chargeId, PaymentMethod $method): void
    {
        $charge = $this->charges->findById($chargeId) ?? throw new DomainException('Cobrança não encontrada.');
        $reservation = $this->guard->getManagedReservation($actor, $charge->reservationId);
        if (!in_array($method, PaymentMethod::deskMethods(), true)) {
            throw new DomainException('Escolha como o pagamento foi recebido no balcão.');
        }
        $this->transaction->run(function () use ($actor, $charge, $reservation, $method): void {
            if (!$this->charges->markPaidAtDesk($charge->id, $method, $actor->id, $this->clock->now())) {
                throw new DomainException('Esta cobrança já foi paga ou não está mais em aberto.');
            }
            $isConfirming = $charge->kind === ChargeKind::Booking && $reservation->status === ReservationStatus::PendingPayment;
            if ($isConfirming) {
                $this->reservations->updateStatus($reservation->id, ReservationStatus::PendingPayment, ReservationStatus::Confirmed);
            }
            $stay = $this->stays->build($this->reservations->findById($reservation->id) ?? $reservation);
            $this->notifier->notifyGuest($stay, ReservationMessages::paymentReceivedAtDesk($stay, $charge, $method, $isConfirming));
        });
    }

    /** Called after any change of hours or dates, so the charges always match the new total. */
    public function adjustToTotal(Reservation $reservation, string $extraDescription): void
    {
        $stay = $this->stays->build($reservation);
        $adjustment = ChargeAdjustment::plan($stay->billing->totalCents(), $this->charges->findByReservation($reservation->id));
        if ($adjustment->isEmpty()) {
            return;
        }
        $now = $this->clock->now();
        if ($adjustment->pendingChargeId !== null) {
            // An unpaid extra charge now covers the new difference, so it takes the new description.
            $pending = $this->charges->findById($adjustment->pendingChargeId);
            $description = $pending?->kind === ChargeKind::Extra ? $extraDescription : null;
            $this->charges->updatePendingAmount($adjustment->pendingChargeId, (int) $adjustment->newPendingAmountCents, $description);
        }
        if ($adjustment->extraChargeCents > 0) {
            $this->charges->create($reservation->id, ChargeKind::Extra, $extraDescription, $adjustment->extraChargeCents, ChargeStatus::Pending, $now->modify('+1 day'), $now);
            $this->notifier->notifyGuest($stay, ReservationMessages::newCharge($stay, $adjustment->extraChargeCents, $extraDescription));
        }
        if ($adjustment->refundCents > 0) {
            $this->createRefund($stay, $adjustment->refundCents, 'Diferença a devolver');
        }
    }

    /** @return int refunded amount in cents (0 when outside the free cancellation window) */
    public function settleCancellation(StayView $stay, bool $isRefundable): int
    {
        $this->charges->cancelOpenForReservation($stay->reservation->id);
        $paidCents = $this->charges->sumPaid($stay->reservation->id);
        if (!$isRefundable || $paidCents === 0) {
            return 0;
        }
        $this->createRefund($stay, $paidCents, 'Devolução por cancelamento', false);

        return $paidCents;
    }

    /** Guests see their own receipts; staff see their hotel's; admins see all. */
    public function getChargeForViewer(User $actor, int $chargeId): Charge
    {
        $charge = $this->charges->findById($chargeId) ?? throw new DomainException('Cobrança não encontrada.');
        $actor->role === Role::Staff
            ? $this->guard->getManagedReservation($actor, $charge->reservationId)
            : $this->guard->getOwnReservation($actor, $charge->reservationId);

        return $charge;
    }

    public function markRefunded(User $actor, int $chargeId): void
    {
        $charge = $this->charges->findById($chargeId) ?? throw new DomainException('Cobrança não encontrada.');
        $this->guard->getManagedReservation($actor, $charge->reservationId);
        if (!$this->charges->markRefunded($charge->id)) {
            throw new DomainException('Este crédito já foi devolvido.');
        }
    }

    /** Unpaid reservations past the deadline free the room. Runs with the message worker and on the hotel panel. */
    public function expireOverdue(): int
    {
        $expired = 0;
        foreach ($this->charges->findOverdueBookingCharges($this->clock->now()) as $charge) {
            $reservation = $this->reservations->findById($charge->reservationId);
            if ($reservation !== null && $this->expire($reservation)) {
                $expired++;
            }
        }

        return $expired;
    }

    private function expire(Reservation $reservation): bool
    {
        return $this->transaction->run(function () use ($reservation): bool {
            if (!$this->reservations->updateStatus($reservation->id, ReservationStatus::PendingPayment, ReservationStatus::Expired)) {
                return false;
            }
            $this->charges->cancelOpenForReservation($reservation->id);
            $this->notifier->notifyGuest($this->stays->build($reservation), ReservationMessages::expired($this->stays->build($reservation)));

            return true;
        });
    }

    private function createRefund(StayView $stay, int $amountCents, string $description, bool $notify = true): void
    {
        $this->charges->create($stay->reservation->id, ChargeKind::Refund, $description, $amountCents, ChargeStatus::RefundPending, null, $this->clock->now());
        if ($notify) {
            $this->notifier->notifyGuest($stay, ReservationMessages::refundCredit($stay, $amountCents));
        }
    }
}
