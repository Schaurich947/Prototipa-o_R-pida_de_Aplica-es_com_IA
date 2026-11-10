<?php

declare(strict_types=1);

namespace HotelFlex\Repository;

use DateTimeImmutable;
use HotelFlex\Domain\Notification;
use PDO;

/** Outbox of messages. Rows are claimed with SKIP LOCKED, so parallel workers never send twice. */
final class NotificationRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $connection)
    {
    }

    public function create(?int $reservationId, int $userId, string $channel, string $recipient, string $subject, string $body): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO notifications (reservation_id, user_id, channel, recipient, subject, body) VALUES (:reservation_id, :user_id, :channel, :recipient, :subject, :body)',
        );
        $statement->execute(['reservation_id' => $reservationId, 'user_id' => $userId, 'channel' => $channel, 'recipient' => $recipient, 'subject' => $subject, 'body' => $body]);
    }

    /** @return list<Notification> */
    public function findByReservation(int $reservationId): array
    {
        $statement = $this->connection->prepare('SELECT * FROM notifications WHERE reservation_id = :id ORDER BY created_at DESC, id DESC');
        $statement->execute(['id' => $reservationId]);

        return array_map(Notification::fromRow(...), $statement->fetchAll());
    }

    /** Must run inside a transaction. @return list<Notification> */
    public function claimDue(DateTimeImmutable $now, int $limit): array
    {
        $statement = $this->connection->prepare(
            "SELECT * FROM notifications WHERE status = 'pending' AND next_attempt_at <= :now ORDER BY id LIMIT :limit FOR UPDATE SKIP LOCKED",
        );
        $statement->bindValue('now', $now->format(self::DATE_FORMAT));
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $claimed = array_map(Notification::fromRow(...), $statement->fetchAll());
        foreach ($claimed as $notification) {
            $this->execute("UPDATE notifications SET status = 'sending', attempts = attempts + 1, next_attempt_at = :now WHERE id = :id", ['now' => $now->format(self::DATE_FORMAT), 'id' => $notification->id]);
        }

        return $claimed;
    }

    public function markSent(int $notificationId, DateTimeImmutable $sentAt): void
    {
        $this->execute("UPDATE notifications SET status = 'sent', sent_at = :sent_at, last_error = NULL WHERE id = :id", ['sent_at' => $sentAt->format(self::DATE_FORMAT), 'id' => $notificationId]);
    }

    public function scheduleRetry(int $notificationId, DateTimeImmutable $nextAttemptAt, string $error): void
    {
        $this->execute("UPDATE notifications SET status = 'pending', next_attempt_at = :next_at, last_error = :error WHERE id = :id", ['next_at' => $nextAttemptAt->format(self::DATE_FORMAT), 'error' => mb_substr($error, 0, 255), 'id' => $notificationId]);
    }

    public function markFailed(int $notificationId, string $error): void
    {
        $this->execute("UPDATE notifications SET status = 'failed', last_error = :error WHERE id = :id", ['error' => mb_substr($error, 0, 255), 'id' => $notificationId]);
    }

    /** A worker that crashed mid-send leaves rows in "sending"; they go back to the queue. */
    public function recoverStuck(DateTimeImmutable $olderThan): void
    {
        $this->execute("UPDATE notifications SET status = 'pending' WHERE status = 'sending' AND next_attempt_at < :older_than", ['older_than' => $olderThan->format(self::DATE_FORMAT)]);
    }

    /** @param array<string, mixed> $params */
    private function execute(string $sql, array $params): void
    {
        $this->connection->prepare($sql)->execute($params);
    }
}
