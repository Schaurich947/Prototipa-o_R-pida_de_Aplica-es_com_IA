<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Repository;

use DateTimeImmutable;
use HotelFlex\Fiscal\Domain\Invoice;
use PDO;

/** Insert-only tax ledger: corrections are new rows (reversals), never edits. */
final class TaxLedgerRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @param int $sign 1 when the invoice is authorized, -1 when it is cancelled */
    public function record(Invoice $invoice, int $sign, DateTimeImmutable $now): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO tax_ledger (hotel_id, invoice_id, tax, nature, amount_cents, competence_month, created_at) VALUES (:hotel_id, :invoice_id, :tax, :nature, :amount, :month, :now)',
        );
        foreach ($invoice->taxes->toLedgerLines() as $line) {
            $statement->execute([
                'hotel_id' => $invoice->hotelId, 'invoice_id' => $invoice->id, 'tax' => $line['tax']->value, 'nature' => $line['nature']->value,
                'amount' => $sign * $line['cents'], 'month' => $invoice->competenceDate->format('Y-m'), 'now' => $now->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /** @return list<array{tax: string, nature: string, cents: int}> */
    public function summarizeMonth(int $hotelId, string $month): array
    {
        $statement = $this->connection->prepare(
            'SELECT tax, nature, SUM(amount_cents) AS cents FROM tax_ledger WHERE hotel_id = :hotel_id AND competence_month = :month GROUP BY tax, nature ORDER BY tax, nature',
        );
        $statement->execute(['hotel_id' => $hotelId, 'month' => $month]);

        return array_map(static fn (array $row) => ['tax' => (string) $row['tax'], 'nature' => (string) $row['nature'], 'cents' => (int) $row['cents']], $statement->fetchAll());
    }
}
