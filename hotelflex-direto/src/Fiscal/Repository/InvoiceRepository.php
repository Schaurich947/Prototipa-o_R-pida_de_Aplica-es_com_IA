<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Repository;

use DateTimeImmutable;
use HotelFlex\Fiscal\Domain\Invoice;
use HotelFlex\Fiscal\Domain\InvoiceSource;
use HotelFlex\Fiscal\Domain\TaxBreakdown;
use PDO;

/** Invoice queue. Rows are claimed with SKIP LOCKED, so parallel workers never issue the same invoice twice. */
final class InvoiceRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly PDO $connection)
    {
    }

    /** @param array<string, mixed> $row column => value; keys come from InvoiceRequester, never from user input */
    public function create(array $row): int
    {
        $columns = array_keys($row);
        $statement = $this->connection->prepare(
            'INSERT INTO invoices (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')',
        );
        $statement->execute($row);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $invoiceId): ?Invoice
    {
        return $this->fetchAll('SELECT * FROM invoices WHERE id = :id', ['id' => $invoiceId])[0] ?? null;
    }

    /** @return list<Invoice> newest first */
    public function findBySource(InvoiceSource $source, int $sourceId): array
    {
        return $this->fetchAll('SELECT * FROM invoices WHERE source_type = :source AND source_id = :source_id ORDER BY id DESC', ['source' => $source->value, 'source_id' => $sourceId]);
    }

    /** @return list<Invoice> */
    public function findByHotelAndMonth(int $hotelId, string $month): array
    {
        return $this->fetchAll(
            "SELECT * FROM invoices WHERE hotel_id = :hotel_id AND DATE_FORMAT(competence_date, '%Y-%m') = :month ORDER BY id DESC",
            ['hotel_id' => $hotelId, 'month' => $month],
        );
    }

    /**
     * Must run inside a transaction.
     *
     * @param int|null $hotelId limits the batch to one hotel (reception button); null = every hotel (worker)
     * @return list<Invoice>
     */
    public function claimDue(DateTimeImmutable $now, int $limit, ?int $hotelId = null): array
    {
        $statement = $this->connection->prepare(
            "SELECT * FROM invoices WHERE status = 'pending' AND next_attempt_at <= :now AND (:hotel_id IS NULL OR hotel_id = :same_hotel_id) ORDER BY id LIMIT :limit FOR UPDATE SKIP LOCKED",
        );
        $statement->bindValue('now', $now->format(self::DATE_FORMAT));
        $statement->bindValue('hotel_id', $hotelId, $hotelId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue('same_hotel_id', $hotelId, $hotelId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        $claimed = array_map(Invoice::fromRow(...), $statement->fetchAll());
        foreach ($claimed as $invoice) {
            $this->execute("UPDATE invoices SET status = 'processing', attempts = attempts + 1, next_attempt_at = :now WHERE id = :id", ['now' => $now->format(self::DATE_FORMAT), 'id' => $invoice->id]);
        }

        return $claimed;
    }

    public function assignNumber(int $invoiceId, string $series, int $number, string $dpsId): void
    {
        $this->execute('UPDATE invoices SET series = :series, number = :number, dps_id = :dps_id WHERE id = :id AND number IS NULL', ['series' => $series, 'number' => $number, 'dps_id' => $dpsId, 'id' => $invoiceId]);
    }

    public function updateTaxes(int $invoiceId, TaxBreakdown $taxes): void
    {
        $this->execute(
            'UPDATE invoices SET iss_cents = :iss, iss_withheld = :withheld, icms_cents = :icms, cbs_cents = :cbs, ibs_cents = :ibs, reform_informative = :informative, acquirer_credit = :credit WHERE id = :id',
            ['iss' => $taxes->issCents, 'withheld' => (int) $taxes->isIssWithheld, 'icms' => $taxes->icmsCents, 'cbs' => $taxes->cbsCents, 'ibs' => $taxes->ibsCents, 'informative' => (int) $taxes->isReformInformative, 'credit' => (int) $taxes->allowsAcquirerCredit, 'id' => $invoiceId],
        );
    }

    public function markAuthorized(int $invoiceId, string $accessKey, string $issuer, DateTimeImmutable $issuedAt): bool
    {
        return $this->update(
            "UPDATE invoices SET status = 'authorized', access_key = :access_key, issuer = :issuer, issued_at = :issued_at, last_error = NULL WHERE id = :id AND status = 'processing'",
            ['access_key' => $accessKey, 'issuer' => $issuer, 'issued_at' => $issuedAt->format(self::DATE_FORMAT), 'id' => $invoiceId],
        );
    }

    public function scheduleRetry(int $invoiceId, DateTimeImmutable $nextAttemptAt, string $error): void
    {
        $this->execute("UPDATE invoices SET status = 'pending', next_attempt_at = :next_at, last_error = :error WHERE id = :id AND status = 'processing'", ['next_at' => $nextAttemptAt->format(self::DATE_FORMAT), 'error' => mb_substr($error, 0, 255), 'id' => $invoiceId]);
    }

    public function markRejected(int $invoiceId, string $error): void
    {
        $this->execute("UPDATE invoices SET status = 'rejected', last_error = :error WHERE id = :id AND status = 'processing'", ['error' => mb_substr($error, 0, 255), 'id' => $invoiceId]);
    }

    public function markCancelled(int $invoiceId, string $reason, DateTimeImmutable $now): bool
    {
        return $this->update("UPDATE invoices SET status = 'cancelled', cancel_reason = :reason, cancelled_at = :now WHERE id = :id AND status = 'authorized'", ['reason' => $reason, 'now' => $now->format(self::DATE_FORMAT), 'id' => $invoiceId]);
    }

    /** After the hotel fixes its data, a rejected invoice goes back to the queue (keeping its number and DPS id). */
    public function requeue(int $invoiceId, DateTimeImmutable $now): bool
    {
        return $this->update("UPDATE invoices SET status = 'pending', attempts = 0, next_attempt_at = :now WHERE id = :id AND status = 'rejected'", ['now' => $now->format(self::DATE_FORMAT), 'id' => $invoiceId]);
    }

    /** A worker that crashed mid-issue leaves rows in "processing"; they go back to the queue and the DPS id prevents duplicates. */
    public function recoverStuck(DateTimeImmutable $olderThan): void
    {
        $this->execute("UPDATE invoices SET status = 'pending' WHERE status = 'processing' AND next_attempt_at < :older_than", ['older_than' => $olderThan->format(self::DATE_FORMAT)]);
    }

    /**
     * @param array<string, mixed> $params
     * @return list<Invoice>
     */
    private function fetchAll(string $sql, array $params): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map(Invoice::fromRow(...), $statement->fetchAll());
    }

    /** @param array<string, mixed> $params */
    private function update(string $sql, array $params): bool
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() === 1;
    }

    /** @param array<string, mixed> $params */
    private function execute(string $sql, array $params): void
    {
        $this->connection->prepare($sql)->execute($params);
    }
}
