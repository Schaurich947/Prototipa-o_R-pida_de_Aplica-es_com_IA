<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\BreakfastAvailability;
use HotelFlex\Domain\BreakfastBooking;
use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\BreakfastStatus;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Hotel;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Domain\Role;
use HotelFlex\Domain\User;
use HotelFlex\Repository\BreakfastRepository;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\ReservationRepository;

/** Breakfast sold to anyone, guests or not, limited by the hotel's daily capacity. */
final class BreakfastService
{
    public const MAX_PEOPLE = 10;

    public function __construct(
        private readonly BreakfastRepository $bookings,
        private readonly BreakfastRowBuilder $rows,
        private readonly HotelRepository $hotels,
        private readonly ReservationRepository $reservations,
        private readonly ReceiptStorage $receipts,
        private readonly Notifier $notifier,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    public function book(User $customer, int $hotelId, DateTimeImmutable $date, int $people, BreakfastPaymentOption $option): int
    {
        $hotel = $this->getHotelSellingBreakfast($hotelId);
        $this->assertBookable($hotel, $date, $people);
        if (!in_array($option, BreakfastPaymentOption::bookingOptions(), true)) {
            throw new DomainException('Escolha a forma de pagamento.');
        }

        return $this->transaction->run(function () use ($customer, $hotel, $date, $people, $option): int {
            $this->assertSeatsLeft($this->getAvailability($hotel, $date), $people);
            $bookingId = $this->bookings->create($this->rows->build($customer, $hotel, $date, $people, $option));
            $booking = $this->getBooking($bookingId);
            $this->notifier->notifyUser($customer->id, BreakfastMessages::created($booking, $hotel));
            $this->notifier->notifyHotelStaffById($hotel->id, BreakfastMessages::forStaff($booking, $hotel, $customer->name));

            return $bookingId;
        });
    }

    /** @param array<string, mixed> $uploadedFile */
    public function submitReceipt(User $actor, int $bookingId, array $uploadedFile): void
    {
        $booking = $this->getOwnBooking($actor, $bookingId);
        if (!$booking->acceptsReceipt()) {
            throw new DomainException('Este café não aguarda comprovante.');
        }
        $receipt = $this->receipts->storeUpload($uploadedFile);
        if (!$this->bookings->attachReceipt($booking->id, $receipt)) {
            $this->receipts->delete($receipt->storedName);
            throw new DomainException('Este café não aguarda mais comprovante.');
        }
        $hotel = $this->getHotelSellingBreakfast($booking->hotelId);
        $this->notifier->notifyHotelStaffById($hotel->id, new Message("Café da manhã {$booking->code} · comprovante para conferir", "{$actor->name} enviou o comprovante Pix do café de {$booking->date->format('d/m/Y')}. Confira em Café da manhã."));
    }

    public function review(User $actor, int $bookingId, bool $isApproved, string $reason): void
    {
        $booking = $this->getManagedBooking($actor, $bookingId);
        $reason = mb_substr(trim($reason), 0, 255);
        if (!$isApproved && mb_strlen($reason) < 3) {
            throw new DomainException('Informe o motivo da recusa para o cliente.');
        }
        if (!$this->bookings->review($booking->id, $isApproved, $isApproved ? null : $reason)) {
            throw new DomainException('Este comprovante já foi conferido.');
        }
        $hotel = $this->getHotelSellingBreakfast($booking->hotelId);
        $this->notifier->notifyUser($booking->userId, $isApproved ? BreakfastMessages::confirmed($booking, $hotel) : BreakfastMessages::rejected($booking, $hotel, $reason));
    }

    public function receiveAtDesk(User $actor, int $bookingId, PaymentMethod $method): void
    {
        $booking = $this->getManagedBooking($actor, $bookingId);
        if (!in_array($method, PaymentMethod::deskMethods(), true) || !$this->bookings->markPaidAtDesk($booking->id, $method)) {
            throw new DomainException('Este café já foi pago ou não está mais em aberto.');
        }
    }

    public function markServed(User $actor, int $bookingId): void
    {
        $booking = $this->getManagedBooking($actor, $bookingId);
        if (!$booking->isPaid) {
            throw new DomainException('Registre o pagamento antes de marcar como atendido.');
        }
        if (!$this->bookings->changeStatus($booking->id, BreakfastStatus::Confirmed, BreakfastStatus::Served)) {
            throw new DomainException('Este café não está confirmado.');
        }
    }

    public function cancel(User $actor, int $bookingId): void
    {
        $booking = $this->getOwnBooking($actor, $bookingId);
        $hotel = $this->getHotelSellingBreakfast($booking->hotelId);
        if (!$booking->isCancellableBy($this->clock->now(), $hotel) || !$this->bookings->changeStatus($booking->id, $booking->status, BreakfastStatus::Cancelled)) {
            throw new DomainException('Este café não pode mais ser cancelado.');
        }
        $this->notifier->notifyUser($booking->userId, BreakfastMessages::cancelled($booking, $hotel));
        $this->notifier->notifyHotelStaffById($hotel->id, BreakfastMessages::cancelled($booking, $hotel));
    }

    public function expireOverdue(): int
    {
        $expired = 0;
        foreach ($this->bookings->findOverdue($this->clock->now()) as $booking) {
            if ($this->bookings->changeStatus($booking->id, BreakfastStatus::PendingPayment, BreakfastStatus::Expired)) {
                $this->notifier->notifyUser($booking->userId, BreakfastMessages::expired($booking, $this->getHotelSellingBreakfast($booking->hotelId)));
                $expired++;
            }
        }

        return $expired;
    }

    /** Sale at the counter on the day, at the higher walk-in price, only while seats are left. */
    public function sellWalkIn(User $actor, int $hotelId, int $people, PaymentMethod $method, string $customerName): void
    {
        $hotel = $this->getHotelSellingBreakfast($hotelId);
        if (!$actor->canManageHotel($hotel->id) || !$hotel->sellsWalkInBreakfast()) {
            throw new DomainException('Este hotel não vende café da manhã na hora.');
        }
        $today = $this->clock->now()->setTime(0, 0);
        $this->assertBookable($hotel, $today, $people);
        if (!in_array($method, PaymentMethod::deskMethods(), true)) {
            throw new DomainException('Escolha como foi pago.');
        }
        $this->transaction->run(function () use ($actor, $hotel, $today, $people, $method, $customerName): void {
            $this->assertSeatsLeft($this->getAvailability($hotel, $today), $people);
            $row = $this->rows->build($actor, $hotel, $today, $people, BreakfastPaymentOption::WalkIn);
            $this->bookings->create([...$row, 'status' => BreakfastStatus::Served->value, 'is_paid' => 1, 'paid_method' => $method->value, 'walk_in_name' => mb_substr(trim($customerName), 0, 120) ?: null]);
        });
    }

    /** Hotel guests first (estimated seats per occupied room), then standalone bookings. */
    public function getAvailability(Hotel $hotel, DateTimeImmutable $date): BreakfastAvailability
    {
        return $this->transaction->run(fn () => new BreakfastAvailability(
            $hotel->breakfastDailyCapacity,
            $this->reservations->countRoomsWithGuestsOn($hotel->id, $date),
            $hotel->breakfastSeatsPerRoom,
            $this->bookings->countSeatsTaken($hotel->id, $date),
        ));
    }

    private function assertSeatsLeft(BreakfastAvailability $availability, int $people): void
    {
        if ($people > $availability->freeSeats) {
            throw new DomainException($availability->freeSeats > 0 ? "Restam só {$availability->freeSeats} lugar(es) nesse dia." : 'Não há mais lugares nesse dia.');
        }
    }

    /** Guests see their own bookings; admins see all. */
    public function getOwnBooking(User $actor, int $bookingId): BreakfastBooking
    {
        $booking = $this->bookings->findById($bookingId);
        if ($booking === null || ($actor->role !== Role::Admin && $booking->userId !== $actor->id)) {
            throw new DomainException('Registro não encontrado.');
        }

        return $booking;
    }

    public function getManagedBooking(User $actor, int $bookingId): BreakfastBooking
    {
        $booking = $this->bookings->findById($bookingId);
        if ($booking === null || !$actor->canManageHotel($booking->hotelId)) {
            throw new DomainException('Registro não encontrado.');
        }

        return $booking;
    }

    public function getHotelSellingBreakfast(int $hotelId): Hotel
    {
        $hotel = $this->hotels->findById($hotelId);
        if ($hotel === null || !$hotel->sellsBreakfast()) {
            throw new DomainException('Este hotel não vende café da manhã avulso.');
        }

        return $hotel;
    }

    private function assertBookable(Hotel $hotel, DateTimeImmutable $date, int $people): void
    {
        if ($people < 1 || $people > self::MAX_PEOPLE) {
            throw new DomainException('Escolha de 1 a ' . self::MAX_PEOPLE . ' pessoas.');
        }
        if ($this->clock->now() >= $date->setTime($hotel->breakfastEndHour, 0)) {
            throw new DomainException('O café desse dia já terminou. Escolha outra data.');
        }
    }

    private function getBooking(int $bookingId): BreakfastBooking
    {
        return $this->bookings->findById($bookingId) ?? throw new DomainException('Café não encontrado.');
    }
}
