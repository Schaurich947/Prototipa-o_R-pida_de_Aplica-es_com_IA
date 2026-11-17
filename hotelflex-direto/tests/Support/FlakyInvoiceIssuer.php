<?php

declare(strict_types=1);

namespace HotelFlex\Tests\Support;

use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\Invoice;
use HotelFlex\Fiscal\Issuer\InvoiceIssuer;
use HotelFlex\Fiscal\Issuer\IssueResult;
use RuntimeException;

/**
 * Wraps the simulated authority to reproduce real failures:
 * "down" fails before receiving the invoice; "lost response" authorizes it but the answer never arrives.
 */
final class FlakyInvoiceIssuer implements InvoiceIssuer
{
    public int $issueCalls = 0;

    public function __construct(
        private readonly InvoiceIssuer $authority,
        private int $outagesLeft = 0,
        private int $lostResponsesLeft = 0,
    ) {
    }

    public function getName(): string
    {
        return $this->authority->getName();
    }

    public function issue(Invoice $invoice, HotelFiscalProfile $issuer): IssueResult
    {
        $this->issueCalls++;
        if ($this->outagesLeft > 0) {
            $this->outagesLeft--;
            throw new RuntimeException('Simulação: emissor fora do ar.');
        }
        $result = $this->authority->issue($invoice, $issuer);
        if ($this->lostResponsesLeft > 0) {
            $this->lostResponsesLeft--;
            throw new RuntimeException('Simulação: tempo esgotado depois de a nota ser autorizada.');
        }

        return $result;
    }

    public function findByDpsId(string $dpsId): ?IssueResult
    {
        return $this->authority->findByDpsId($dpsId);
    }

    public function cancel(Invoice $invoice, string $reason): IssueResult
    {
        return $this->authority->cancel($invoice, $reason);
    }
}
