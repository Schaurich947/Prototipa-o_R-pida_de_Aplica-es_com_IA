<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Service;

use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Clock;
use HotelFlex\Domain\RetryPolicy;
use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\Invoice;
use HotelFlex\Fiscal\Domain\TaxCalculator;
use HotelFlex\Fiscal\Issuer\InvoiceIssuer;
use HotelFlex\Fiscal\Issuer\IssueResult;
use HotelFlex\Fiscal\Repository\HotelFiscalProfileRepository;
use HotelFlex\Fiscal\Repository\InvoiceRepository;
use HotelFlex\Fiscal\Repository\TaxLedgerRepository;
use HotelFlex\Fiscal\Repository\TaxRuleRepository;
use Throwable;

/** Issues queued invoices: numbers them, calculates taxes with the rules in force, sends them and records the taxes. */
final class InvoiceProcessor
{
    private const STUCK_AFTER_MINUTES = 10;

    public function __construct(
        private readonly InvoiceRepository $invoices,
        private readonly HotelFiscalProfileRepository $fiscalProfiles,
        private readonly TaxRuleRepository $taxRules,
        private readonly TaxLedgerRepository $ledger,
        private readonly InvoiceIssuer $issuer,
        private readonly TransactionRunner $transaction,
        private readonly Clock $clock,
    ) {
    }

    /**
     * @param int|null $hotelId only this hotel's queue (reception button); null = all hotels (worker)
     * @return array{authorized: int, rejected: int, retrying: int}
     */
    public function processDue(?int $hotelId = null, int $limit = 20): array
    {
        $now = $this->clock->now();
        $this->invoices->recoverStuck($now->modify('-' . self::STUCK_AFTER_MINUTES . ' minutes'));
        $claimed = $this->transaction->run(fn () => $this->invoices->claimDue($now, $limit, $hotelId));
        $summary = ['authorized' => 0, 'rejected' => 0, 'retrying' => 0];
        foreach ($claimed as $invoice) {
            $summary[$this->process($invoice)]++;
        }

        return $summary;
    }

    /** @return 'authorized'|'rejected'|'retrying' */
    private function process(Invoice $invoice): string
    {
        $issuerProfile = $this->fiscalProfiles->findByHotel($invoice->hotelId);
        $missingData = $issuerProfile === null ? 'Cadastre os dados fiscais do hotel antes de emitir.' : $issuerProfile->getMissingData($invoice->kind);
        if ($missingData !== null) {
            $this->invoices->markRejected($invoice->id, $missingData);

            return 'rejected';
        }
        $prepared = $this->prepare($invoice, $issuerProfile);

        return $this->handleResult($prepared, $this->send($prepared, $issuerProfile));
    }

    /** Number and DPS id are assigned once; a retry keeps them, so the authority can tell it is the same invoice. */
    private function prepare(Invoice $invoice, HotelFiscalProfile $issuerProfile): Invoice
    {
        return $this->transaction->run(function () use ($invoice, $issuerProfile): Invoice {
            if ($invoice->number === null) {
                $number = $this->fiscalProfiles->reserveNextNumber($invoice->hotelId);
                $this->invoices->assignNumber($invoice->id, $issuerProfile->series, $number, DpsId::build($issuerProfile, $number));
            }
            $isIssWithheld = $issuerProfile->issWithholding->isWithheldBy($invoice->taker, $issuerProfile->municipalityCode);
            $rules = $this->taxRules->findValid($invoice->serviceType, $invoice->competenceDate);
            $this->invoices->updateTaxes($invoice->id, TaxCalculator::calculate($invoice->serviceType, $invoice->amountCents, $issuerProfile, $isIssWithheld, $rules));

            return $this->invoices->findById($invoice->id) ?? $invoice;
        });
    }

    /** Asks first whether the declaration was already received: after a timeout, the invoice may have been authorized. */
    private function send(Invoice $invoice, HotelFiscalProfile $issuerProfile): IssueResult
    {
        try {
            return $this->issuer->findByDpsId((string) $invoice->dpsId) ?? $this->issuer->issue($invoice, $issuerProfile);
        } catch (Throwable $error) {
            error_log(sprintf('[hotelflex] nota %d: emissor indisponível: %s', $invoice->id, $error->getMessage()));

            return IssueResult::unavailable('Emissor indisponível. Nova tentativa automática.');
        }
    }

    /** @return 'authorized'|'rejected'|'retrying' */
    private function handleResult(Invoice $invoice, IssueResult $result): string
    {
        if ($result->isAuthorized) {
            $this->transaction->run(function () use ($invoice, $result): void {
                if ($this->invoices->markAuthorized($invoice->id, (string) $result->accessKey, $this->issuer->getName(), $this->clock->now())) {
                    $this->ledger->record($invoice, 1, $this->clock->now());
                }
            });

            return 'authorized';
        }
        if ($result->isRetryable && RetryPolicy::hasAttemptsLeft($invoice->attempts)) {
            $this->invoices->scheduleRetry($invoice->id, RetryPolicy::nextAttemptAt($this->clock->now(), $invoice->attempts), (string) $result->message);

            return 'retrying';
        }
        $this->invoices->markRejected($invoice->id, (string) $result->message);

        return 'rejected';
    }
}
