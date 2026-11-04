<?php

declare(strict_types=1);

namespace Comprova\Repository;

use Comprova\Domain\Payment;
use Comprova\Domain\PaymentSource;
use DateTimeImmutable;
use PDO;

final class PaymentRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @param array{beneficiaryId: int, externalId: ?string, source: PaymentSource, amountCents: int, description: ?string, paidAt: DateTimeImmutable, receiptPath: string, receiptOriginalName: string, receiptMime: string, createdBy: ?int} $data */
    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO payments (beneficiary_id, external_id, source, amount_cents, description, paid_at, receipt_path, receipt_original_name, receipt_mime, created_by)
             VALUES (:beneficiary_id, :external_id, :source, :amount_cents, :description, :paid_at, :receipt_path, :receipt_original_name, :receipt_mime, :created_by)',
        );
        $statement->execute([
            'beneficiary_id' => $data['beneficiaryId'],
            'external_id' => $data['externalId'],
            'source' => $data['source']->value,
            'amount_cents' => $data['amountCents'],
            'description' => $data['description'],
            'paid_at' => $data['paidAt']->format('Y-m-d H:i:s'),
            'receipt_path' => $data['receiptPath'],
            'receipt_original_name' => $data['receiptOriginalName'],
            'receipt_mime' => $data['receiptMime'],
            'created_by' => $data['createdBy'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $paymentId): ?Payment
    {
        return $this->fetchOne('SELECT * FROM payments WHERE id = :id', ['id' => $paymentId]);
    }

    public function findByExternalId(string $externalId): ?Payment
    {
        return $this->fetchOne('SELECT * FROM payments WHERE external_id = :external_id', ['external_id' => $externalId]);
    }

    /** @return list<Payment> */
    public function findRecent(int $limit): array
    {
        $statement = $this->connection->prepare('SELECT * FROM payments ORDER BY paid_at DESC, id DESC LIMIT :limit');
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return array_map(Payment::fromRow(...), $statement->fetchAll());
    }

    /** @return array{count: int, totalCents: int} */
    public function summarizeSince(DateTimeImmutable $since): array
    {
        $statement = $this->connection->prepare('SELECT COUNT(*) AS total_count, COALESCE(SUM(amount_cents), 0) AS total_cents FROM payments WHERE paid_at >= :since');
        $statement->execute(['since' => $since->format('Y-m-d H:i:s')]);
        $row = $statement->fetch();

        return ['count' => (int) $row['total_count'], 'totalCents' => (int) $row['total_cents']];
    }

    /** @param array<string, mixed> $params */
    private function fetchOne(string $sql, array $params): ?Payment
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row ? Payment::fromRow($row) : null;
    }
}
