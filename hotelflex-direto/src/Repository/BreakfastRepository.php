<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use DateTimeImmutable;
use HotelFlex\Domain\BreakfastBooking;
use HotelFlex\Domain\BreakfastStatus;
use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Service\StoredReceipt;
use PDO;

/** Status changes are conditional (WHERE status = ...), so concurrent actions never apply twice. */
final class BreakfastRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $connection)
    {
    }

    /** @param array<string, mixed> $data column => value; keys come from BreakfastService, never from user input */
    public function create(array $data): int
    {
        $columns = array_keys($data);
        $statement = $this->connection->prepare(
            'INSERT INTO breakfast_bookings (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')',
        );
        $statement->execute($data);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $bookingId): ?BreakfastBooking
    {
        $statement = $this->connection->prepare('SELECT * FROM breakfast_bookings WHERE id = :id');
        $statement->execute(['id' => $bookingId]);
        $row = $statement->fetch();

        return $row ? BreakfastBooking::fromRow($row) : null;
    }

    /** @return list<BreakfastBooking> */
    public function findByUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM breakfast_bookings WHERE user_id = :user_id ORDER BY breakfast_date DESC, id DESC', ['user_id' => $userId]);
    }

    /** @return list<BreakfastBooking> */
    public function findByHotelAndDate(int $hotelId, DateTimeImmutable $date): array
    {
        return $this->fetchAll(
            'SELECT * FROM breakfast_bookings WHERE hotel_id = :hotel_id AND breakfast_date = :day ORDER BY status, id',
            ['hotel_id' => $hotelId, 'day' => $date->format('Y-m-d')],
        );
    }

    /** Must run inside a transaction: locks the day's rows so two bookings cannot exceed the capacity. */
    public function countSeatsTaken(int $hotelId, DateTimeImmutable $date): int
    {
        $statement = $this->connection->prepare(
            "SELECT COALESCE(SUM(people), 0) FROM breakfast_bookings
             WHERE hotel_id = :hotel_id AND breakfast_date = :day AND status IN ('pending_payment', 'under_review', 'confirmed', 'served') FOR UPDATE",
        );
        $statement->execute(['hotel_id' => $hotelId, 'day' => $date->format('Y-m-d')]);

        return (int) $statement->fetchColumn();
    }

    public function attachReceipt(int $bookingId, StoredReceipt $receipt): bool
    {
        return $this->update(
            "UPDATE breakfast_bookings SET status = 'under_review', receipt_path = :path, receipt_original_name = :name, receipt_mime = :mime, rejection_reason = NULL
             WHERE id = :id AND status = 'pending_payment' AND payment_option = 'pix'",
            ['path' => $receipt->storedName, 'name' => $receipt->originalName, 'mime' => $receipt->mime, 'id' => $bookingId],
        );
    }

    public function review(int $bookingId, bool $isApproved, ?string $reason): bool
    {
        return $isApproved
            ? $this->update("UPDATE breakfast_bookings SET status = 'confirmed', is_paid = 1, paid_method = 'pix_receipt' WHERE id = :id AND status = 'under_review'", ['id' => $bookingId])
            : $this->update("UPDATE breakfast_bookings SET status = 'pending_payment', rejection_reason = :reason WHERE id = :id AND status = 'under_review'", ['reason' => $reason, 'id' => $bookingId]);
    }

    public function markPaidAtDesk(int $bookingId, PaymentMethod $method): bool
    {
        return $this->update(
            "UPDATE breakfast_bookings SET status = 'confirmed', is_paid = 1, paid_method = :method WHERE id = :id AND is_paid = 0 AND status IN ('pending_payment', 'confirmed')",
            ['method' => $method->value, 'id' => $bookingId],
        );
    }

    public function changeStatus(int $bookingId, BreakfastStatus $from, BreakfastStatus $to): bool
    {
        return $this->update('UPDATE breakfast_bookings SET status = :to WHERE id = :id AND status = :from', ['to' => $to->value, 'id' => $bookingId, 'from' => $from->value]);
    }

    /** @return list<BreakfastBooking> Pix bookings without a receipt past their deadline */
    public function findOverdue(DateTimeImmutable $now): array
    {
        return $this->fetchAll("SELECT * FROM breakfast_bookings WHERE status = 'pending_payment' AND due_at < :now", ['now' => $now->format(self::DATE_FORMAT)]);
    }

    public function codeExists(string $code): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM breakfast_bookings WHERE code = :code');
        $statement->execute(['code' => $code]);

        return $statement->fetchColumn() !== false;
    }

    /** @param array<string, mixed> $params */
    private function update(string $sql, array $params): bool
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() === 1;
    }

    /** @param array<string, mixed> $params @return list<BreakfastBooking> */
    private function fetchAll(string $sql, array $params): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map(BreakfastBooking::fromRow(...), $statement->fetchAll());
    }
}
