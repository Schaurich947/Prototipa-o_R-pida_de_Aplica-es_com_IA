<?php

declare(strict_types=1);

namespace HotelFlex\Service;

use DateTimeImmutable;
use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Availability;
use HotelFlex\Domain\CancellationPolicy;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\DomainException;
use HotelFlex\Domain\Reservation;
use HotelFlex\Domain\ReservationStatus;
use HotelFlex\Domain\Room;
use HotelFlex\Domain\User;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Repository\RoomRepository;
use HotelFlex\Repository\ServiceRequestRepository;

/** Guest self-service: book, change dates and cancel. */
final class ReservationService
{
    public const MAX_NIGHTS = 30;

    public function __construct(
        private readonly ReservationRepository $reservations,
        private readonly RoomRepository $rooms,
        private readonly ServiceRequestRepository $requests,
        private readonly AccessGuard $guard,
        private readonly StayViewFactory $stays,
        private readonly ChargeService $charges,
        private readonly Notifier $notifier,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    public function book(User $guest, int $roomId, DateTimeImmutable $checkInDate, int $nights): int
    {
        $this->assertValidDates($checkInDate, $nights);
        $room = $this->rooms->findById($roomId) ?? throw new DomainException('Quarto não encontrado.');

        return $this->transaction->run(function () use ($guest, $room, $checkInDate, $nights): int {
            $this->rooms->lockForUpdate($room->id);
            $this->assertRoomIsFree($room, $checkInDate, $nights, null);
            $reservationId = $this->reservations->create($this->generateCode(), $guest->id, $room->id, $checkInDate, $nights);
            $stay = $this->stays->build($this->reservations->findById($reservationId) ?? throw new DomainException('Reserva não encontrada.'));
            $charge = $this->charges->createBookingCharge($stay);
            $this->notifier->notifyGuest($stay, ReservationMessages::created($stay, $charge));
            $this->notifier->notifyHotelStaff($stay, ReservationMessages::newReservationForHotel($stay));

            return $reservationId;
        });
    }

    public function changeDates(User $actor, int $reservationId, DateTimeImmutable $checkInDate, int $nights): void
    {
        $reservation = $this->getEditableReservation($actor, $reservationId);
        $this->assertValidDates($checkInDate, $nights);
        $room = $this->rooms->findById($reservation->roomId) ?? throw new DomainException('Quarto não encontrado.');

        $this->transaction->run(function () use ($reservation, $room, $checkInDate, $nights): void {
            $this->rooms->lockForUpdate($room->id);
            $this->assertRoomIsFree($room, $checkInDate, $nights, $reservation->id);
            $this->reservations->updateDates($reservation->id, $checkInDate, $nights);
            $this->requests->cancelPendingForReservation($reservation->id, $this->clock->now());
            $updated = $this->reservations->findById($reservation->id) ?? $reservation;
            $this->charges->adjustToTotal($updated, 'Diferença por alteração de datas');
            $this->notifier->notifyGuest($this->stays->build($updated), ReservationMessages::datesChanged($this->stays->build($updated)));
        });
    }

    public function cancel(User $actor, int $reservationId): void
    {
        $reservation = $this->getEditableReservation($actor, $reservationId);
        $stay = $this->stays->build($reservation);
        $isRefundable = CancellationPolicy::isRefundable($stay->hotel, $reservation, $this->clock->now());
        $this->transaction->run(function () use ($reservation, $stay, $isRefundable): void {
            if (!$this->reservations->updateStatus($reservation->id, $reservation->status, ReservationStatus::Cancelled)) {
                throw new DomainException('A reserva não pode mais ser cancelada.');
            }
            $this->requests->cancelPendingForReservation($reservation->id, $this->clock->now());
            $refundCents = $this->charges->settleCancellation($stay, $isRefundable);
            $this->notifier->notifyGuest($stay, ReservationMessages::cancelled($stay, $refundCents));
            $this->notifier->notifyHotelStaff($stay, ReservationMessages::cancelled($stay, $refundCents));
        });
    }

    private function getEditableReservation(User $actor, int $reservationId): Reservation
    {
        $reservation = $this->guard->getOwnReservation($actor, $reservationId);
        if (!$reservation->isEditableBy($this->clock->now())) {
            throw new DomainException('Só é possível alterar ou cancelar reservas que ainda não começaram.');
        }

        return $reservation;
    }

    private function assertValidDates(DateTimeImmutable $checkInDate, int $nights): void
    {
        if ($checkInDate < $this->clock->now()->setTime(0, 0)) {
            throw new DomainException('A data de entrada não pode estar no passado.');
        }
        if ($nights < 1 || $nights > self::MAX_NIGHTS) {
            throw new DomainException('Escolha entre 1 e ' . self::MAX_NIGHTS . ' diárias.');
        }
    }

    private function assertRoomIsFree(Room $room, DateTimeImmutable $checkInDate, int $nights, ?int $excludeId): void
    {
        $hotel = $this->stays->getHotel($room->hotelId);
        $checkOutDate = $checkInDate->modify("+{$nights} days");
        $neighbours = $this->reservations->findStaysNear($room->id, $checkInDate, $checkOutDate, $excludeId);
        $startsAt = $checkInDate->setTime($hotel->checkInHour, 0);
        $endsAt = $checkOutDate->setTime($hotel->checkOutHour, 0);
        if (Availability::hasConflict($hotel, $startsAt, $endsAt, $neighbours)) {
            throw new DomainException('O quarto não está disponível nessas datas.');
        }
    }

    private function generateCode(): string
    {
        do {
            $code = 'HF-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($this->reservations->codeExists($code));

        return $code;
    }
}
