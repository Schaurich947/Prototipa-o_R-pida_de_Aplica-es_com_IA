<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Fiscal\Domain\Invoice;
use HotelFlex\Fiscal\Domain\InvoiceStatus;
use HotelFlex\Fiscal\Domain\LedgerNature;
use HotelFlex\Fiscal\Domain\TaxType;
use HotelFlex\Fiscal\Repository\TaxLedgerRepository;

/** Pre-control for the hotel and its accountant: an estimate, not the official tax assessment. */
final class MonthlyTaxSummary
{
    public function __construct(private readonly TaxLedgerRepository $ledger)
    {
    }

    /**
     * @param list<Invoice> $invoices invoices of the month
     * @return array{invoiceCount: int, invoicedCents: int, pendingCount: int, rejectedCount: int, lines: list<array{tax: TaxType, nature: LedgerNature, cents: int}>}
     */
    public function summarize(int $hotelId, string $month, array $invoices): array
    {
        $authorized = array_filter($invoices, static fn (Invoice $invoice) => $invoice->status === InvoiceStatus::Authorized);
        $lines = array_map(
            static fn (array $row) => ['tax' => TaxType::from($row['tax']), 'nature' => LedgerNature::from($row['nature']), 'cents' => $row['cents']],
            $this->ledger->summarizeMonth($hotelId, $month),
        );

        return [
            'invoiceCount' => count($authorized),
            'invoicedCents' => array_sum(array_map(static fn (Invoice $invoice) => $invoice->amountCents, $authorized)),
            'pendingCount' => count(array_filter($invoices, static fn (Invoice $invoice) => in_array($invoice->status, [InvoiceStatus::Pending, InvoiceStatus::Processing], true))),
            'rejectedCount' => count(array_filter($invoices, static fn (Invoice $invoice) => $invoice->status === InvoiceStatus::Rejected)),
            'lines' => array_values(array_filter($lines, static fn (array $line) => $line['cents'] !== 0)),
        ];
    }
}
