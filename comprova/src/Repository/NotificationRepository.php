<?php

declare(strict_types=1);

namespace Comprova\Repository;

use Comprova\Domain\Notification;
use Comprova\Domain\NotificationChannel;
use Comprova\Domain\NotificationStatus;
use DateTimeImmutable;
use PDO;

/** Outbox of deliveries. Status changes are conditional so concurrent workers never send twice. */
final class NotificationRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(int $paymentId, NotificationChannel $channel, string $recipient, NotificationStatus $status): void
    {
        $this->execute(
            'INSERT INTO notifications (payment_id, channel, recipient, status) VALUES (:payment_id, :channel, :recipient, :status)',
            ['payment_id' => $paymentId, 'channel' => $channel->value, 'recipient' => $recipient, 'status' => $status->value],
        );
    }

    /** @param list<int> $paymentIds @return array<int, list<Notification>> grouped by payment id */
    public function findByPaymentIds(array $paymentIds): array
    {
        if ($paymentIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($paymentIds), '?'));
        $statement = $this->connection->prepare("SELECT * FROM notifications WHERE payment_id IN ({$placeholders}) ORDER BY channel");
        $statement->execute(array_values($paymentIds));
        $grouped = [];
        foreach ($statement->fetchAll() as $row) {
            $grouped[(int) $row['payment_id']][] = Notification::fromRow($row);
        }

        return $grouped;
    }

    public function findById(int $notificationId): ?Notification
    {
        $statement = $this->connection->prepare('SELECT * FROM notifications WHERE id = :id');
        $statement->execute(['id' => $notificationId]);
        $row = $statement->fetch();

        return $row ? Notification::fromRow($row) : null;
    }

    /**
     * Locks due rows (SKIP LOCKED lets several workers run in parallel) and marks them as sending.
     * Must run inside a transaction.
     *
     * @return list<Notification>
     */
    public function claimDue(DateTimeImmutable $now, int $limit): array
    {
        $statement = $this->connection->prepare(
            "SELECT * FROM notifications WHERE status = 'pending' AND next_attempt_at <= :now
             ORDER BY next_attempt_at LIMIT :limit FOR UPDATE SKIP LOCKED",
        );
        $statement->bindValue('now', $now->format(self::DATE_FORMAT));
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $claimed = array_map(Notification::fromRow(...), $statement->fetchAll());
        foreach ($claimed as $notification) {
            $this->execute(
                "UPDATE notifications SET status = 'sending', attempts = attempts + 1, next_attempt_at = :now WHERE id = :id",
                ['now' => $now->format(self::DATE_FORMAT), 'id' => $notification->id],
            );
        }

        return $claimed;
    }

    public function markSent(int $notificationId, ?string $providerMessageId, DateTimeImmutable $sentAt): void
    {
        $this->execute(
            "UPDATE notifications SET status = 'sent', sent_at = :sent_at, provider_message_id = :provider_id, last_error = NULL WHERE id = :id",
            ['sent_at' => $sentAt->format(self::DATE_FORMAT), 'provider_id' => $providerMessageId, 'id' => $notificationId],
        );
    }

    public function scheduleRetry(int $notificationId, DateTimeImmutable $nextAttemptAt, string $error): void
    {
        $this->execute(
            "UPDATE notifications SET status = 'pending', next_attempt_at = :next_at, last_error = :error WHERE id = :id",
            ['next_at' => $nextAttemptAt->format(self::DATE_FORMAT), 'error' => mb_substr($error, 0, 255), 'id' => $notificationId],
        );
    }

    public function markFailed(int $notificationId, string $error): void
    {
        $this->execute("UPDATE notifications SET status = 'failed', last_error = :error WHERE id = :id", ['error' => mb_substr($error, 0, 255), 'id' => $notificationId]);
    }

    /** E-mail delivered: the WhatsApp notice of the same payment can go now. */
    public function releaseWaiting(int $paymentId): void
    {
        $this->execute("UPDATE notifications SET status = 'pending', next_attempt_at = NOW() WHERE payment_id = :payment_id AND status = 'waiting'", ['payment_id' => $paymentId]);
    }

    /** E-mail failed for good: the WhatsApp text would be false, so it is not sent. */
    public function skipWaiting(int $paymentId, string $reason): void
    {
        $this->execute("UPDATE notifications SET status = 'skipped', last_error = :reason WHERE payment_id = :payment_id AND status = 'waiting'", ['reason' => $reason, 'payment_id' => $paymentId]);
    }

    /** Operator asked to try again: fresh attempts, and a skipped WhatsApp waits for the e-mail again. */
    public function resetForRetry(int $notificationId): bool
    {
        $statement = $this->connection->prepare(
            "UPDATE notifications SET status = IF(status = 'skipped', 'waiting', 'pending'), attempts = 0, next_attempt_at = NOW(), last_error = NULL
             WHERE id = :id AND status IN ('failed', 'skipped')",
        );
        $statement->execute(['id' => $notificationId]);

        return $statement->rowCount() === 1;
    }

    /** E-mail queued again: the WhatsApp skipped because of it goes back to waiting for it. */
    public function rewaitSkipped(int $paymentId): void
    {
        $this->execute("UPDATE notifications SET status = 'waiting', attempts = 0, last_error = NULL WHERE payment_id = :payment_id AND status = 'skipped'", ['payment_id' => $paymentId]);
    }

    /** A worker that crashed mid-send leaves rows in "sending"; they go back to the queue. */
    public function recoverStuck(DateTimeImmutable $olderThan): int
    {
        $statement = $this->connection->prepare("UPDATE notifications SET status = 'pending' WHERE status = 'sending' AND next_attempt_at < :older_than");
        $statement->execute(['older_than' => $olderThan->format(self::DATE_FORMAT)]);

        return $statement->rowCount();
    }

    /** @return array<string, int> count per status */
    public function countByStatus(): array
    {
        $rows = $this->connection->query('SELECT status, COUNT(*) AS total FROM notifications GROUP BY status')->fetchAll();

        return array_column(array_map(static fn ($row) => ['status' => $row['status'], 'total' => (int) $row['total']], $rows), 'total', 'status');
    }

    /** @param array<string, mixed> $params */
    private function execute(string $sql, array $params): void
    {
        $this->connection->prepare($sql)->execute($params);
    }
}
