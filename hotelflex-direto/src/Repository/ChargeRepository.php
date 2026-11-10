<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use DateTimeImmutable;
use HotelFlex\Domain\Charge;
use HotelFlex\Domain\ChargeKind;
use HotelFlex\Domain\ChargeStatus;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Service\StoredReceipt;
use PDO;

/** Status changes are conditional (WHERE status = ...), so two people can never review the same receipt twice. */
final class ChargeRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(int $reservationId, ChargeKind $kind, string $description, int $amountCents, ChargeStatus $status, ?DateTimeImmutable $dueAt, DateTimeImmutable $now, bool $payAtDesk = false): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO charges (reservation_id, kind, description, amount_cents, status, due_at, created_at, pay_at_desk)
             VALUES (:reservation_id, :kind, :description, :amount_cents, :status, :due_at, :created_at, :pay_at_desk)',
        );
        $statement->execute([
            'reservation_id' => $reservationId,
            'kind' => $kind->value,
            'description' => $description,
            'amount_cents' => $amountCents,
            'status' => $status->value,
            'due_at' => $dueAt?->format(self::DATE_FORMAT),
            'created_at' => $now->format(self::DATE_FORMAT),
            'pay_at_desk' => $payAtDesk ? 1 : 0,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $chargeId): ?Charge
    {
        $statement = $this->connection->prepare('SELECT * FROM charges WHERE id = :id');
        $statement->execute(['id' => $chargeId]);
        $row = $statement->fetch();

        return $row ? Charge::fromRow($row) : null;
    }

    /** @return list<Charge> */
    public function findByReservation(int $reservationId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM charges WHERE reservation_id = :reservation_id ORDER BY id');
        $statement->execute(['reservation_id' => $reservationId]);

        return array_map(Charge::fromRow(...), $statement->fetchAll());
    }

    /** @return list<Charge> receipts waiting for the hotel's review */
    public function findUnderReviewByHotel(int $hotelId): array
    {
        $statement = $this->connection->prepare(
            "SELECT charges.* FROM charges
             JOIN reservations res ON res.id = charges.reservation_id
             JOIN rooms ON rooms.id = res.room_id
             WHERE rooms.hotel_id = :hotel_id AND charges.status = 'under_review'
             ORDER BY charges.updated_at",
        );
        $statement->execute(['hotel_id' => $hotelId]);

        return array_map(Charge::fromRow(...), $statement->fetchAll());
    }

    /** @return list<Charge> unpaid booking charges past their deadline (no receipt sent) */
    public function findOverdueBookingCharges(DateTimeImmutable $now): array
    {
        $statement = $this->connection->prepare(
            "SELECT charges.* FROM charges JOIN reservations res ON res.id = charges.reservation_id
             WHERE charges.kind = 'booking' AND charges.status = 'pending' AND charges.due_at < :now AND res.status = 'pending_payment'",
        );
        $statement->execute(['now' => $now->format(self::DATE_FORMAT)]);

        return array_map(Charge::fromRow(...), $statement->fetchAll());
    }

    public function attachReceipt(int $chargeId, StoredReceipt $receipt): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE charges SET status = 'under_review', receipt_path = :path, receipt_original_name = :name, receipt_mime = :mime, rejection_reason = NULL
             WHERE id = :id AND status = 'pending'",
        );
        $statement->execute(['path' => $receipt->storedName, 'name' => $receipt->originalName, 'mime' => $receipt->mime, 'id' => $chargeId]);

        return $statement->rowCount() === 1;
    }

    public function markReviewed(int $chargeId, bool $isApproved, ?string $reason, DateTimeImmutable $now): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE charges SET status = :status, rejection_reason = :reason, reviewed_at = :reviewed_at, paid_method = :method WHERE id = :id AND status = 'under_review'",
        );
        $statement->execute([
            'status' => ($isApproved ? ChargeStatus::Paid : ChargeStatus::Pending)->value,
            'method' => $isApproved ? PaymentMethod::PixReceipt->value : null,
            'reason' => $reason,
            'reviewed_at' => $now->format(self::DATE_FORMAT),
            'id' => $chargeId,
        ]);

        return $statement->rowCount() === 1;
    }

    /** Payment taken in person by the reception (cash, card terminal or Pix at the desk). */
    public function markPaidAtDesk(int $chargeId, PaymentMethod $method, int $receivedBy, DateTimeImmutable $now): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE charges SET status = 'paid', paid_method = :method, received_by = :received_by, reviewed_at = :now, rejection_reason = NULL
             WHERE id = :id AND status = 'pending' AND kind <> 'refund'",
        );
        $statement->execute(['method' => $method->value, 'received_by' => $receivedBy, 'now' => $now->format(self::DATE_FORMAT), 'id' => $chargeId]);

        return $statement->rowCount() === 1;
    }

    /** @return list<Charge> unpaid charges of active reservations, to be received at the desk */
    public function findOpenByHotel(int $hotelId): array
    {
        $statement = $this->connection->prepare(
            "SELECT charges.* FROM charges
             JOIN reservations res ON res.id = charges.reservation_id
             JOIN rooms ON rooms.id = res.room_id
             WHERE rooms.hotel_id = :hotel_id AND charges.status = 'pending' AND charges.kind <> 'refund'
               AND res.status IN ('pending_payment', 'confirmed', 'checked_in')
             ORDER BY charges.due_at",
        );
        $statement->execute(['hotel_id' => $hotelId]);

        return array_map(Charge::fromRow(...), $statement->fetchAll());
    }

    /** What the guest still owes (unpaid or waiting for review). */
    public function sumOpen(int $reservationId): int
    {
        $statement = $this->connection->prepare(
            "SELECT COALESCE(SUM(amount_cents), 0) FROM charges WHERE reservation_id = :id AND status IN ('pending', 'under_review') AND kind <> 'refund'",
        );
        $statement->execute(['id' => $reservationId]);

        return (int) $statement->fetchColumn();
    }

    public function updatePendingAmount(int $chargeId, int $amountCents, ?string $description = null): void
    {
        $status = $amountCents > 0 ? 'pending' : 'cancelled';
        $statement = $this->connection->prepare(
            "UPDATE charges SET amount_cents = :amount, status = :status, description = COALESCE(:description, description) WHERE id = :id AND status = 'pending'",
        );
        $statement->execute(['amount' => $amountCents, 'status' => $status, 'description' => $description, 'id' => $chargeId]);
    }

    /** Unpaid charges are dropped when the reservation is cancelled or expires. */
    public function cancelOpenForReservation(int $reservationId): void
    {
        $statement = $this->connection->prepare("UPDATE charges SET status = 'cancelled' WHERE reservation_id = :id AND status IN ('pending', 'under_review')");
        $statement->execute(['id' => $reservationId]);
    }

    public function markRefunded(int $chargeId): bool
    {
        $statement = $this->connection->prepare("UPDATE charges SET status = 'refunded' WHERE id = :id AND status = 'refund_pending'");
        $statement->execute(['id' => $chargeId]);

        return $statement->rowCount() === 1;
    }

    public function sumPaid(int $reservationId): int
    {
        $statement = $this->connection->prepare("SELECT COALESCE(SUM(amount_cents), 0) FROM charges WHERE reservation_id = :id AND status = 'paid'");
        $statement->execute(['id' => $reservationId]);

        return (int) $statement->fetchColumn();
    }
}
