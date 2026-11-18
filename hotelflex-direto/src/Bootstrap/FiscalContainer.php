<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Config\Env;
use HotelFlex\Database\TransactionRunner;
use HotelFlex\Domain\Clock;
use HotelFlex\Fiscal\Issuer\InvoiceIssuer;
use HotelFlex\Fiscal\Issuer\IssuerFactory;
use HotelFlex\Fiscal\Lookup\BrasilApiCompanyLookup;
use HotelFlex\Fiscal\Lookup\CachedCompanyLookup;
use HotelFlex\Fiscal\Lookup\CompanyLookup;
use HotelFlex\Fiscal\Lookup\SimulatedCompanyLookup;
use HotelFlex\Fiscal\Pdf\InvoicePdfRenderer;
use HotelFlex\Fiscal\Repository\BillingProfileRepository;
use HotelFlex\Fiscal\Repository\CompanyLookupCache;
use HotelFlex\Fiscal\Repository\HotelFiscalProfileRepository;
use HotelFlex\Fiscal\Repository\InvoiceRepository;
use HotelFlex\Fiscal\Repository\TaxLedgerRepository;
use HotelFlex\Fiscal\Repository\TaxRuleRepository;
use HotelFlex\Fiscal\Service\BillingProfileInputParser;
use HotelFlex\Fiscal\Service\BillingProfileService;
use HotelFlex\Fiscal\Service\HotelFiscalService;
use HotelFlex\Fiscal\Service\InvoicePdfService;
use HotelFlex\Fiscal\Service\InvoiceProcessor;
use HotelFlex\Fiscal\Service\InvoiceRequester;
use HotelFlex\Fiscal\Service\InvoiceService;
use HotelFlex\Fiscal\Service\MonthlyTaxSummary;
use HotelFlex\Repository\BreakfastRepository;
use HotelFlex\Repository\ChargeRepository;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\Repository\ReservationRepository;
use HotelFlex\Service\StayViewFactory;
use HotelFlex\View\View;
use PDO;

/** Wiring of the fiscal module, kept apart so it can grow (or leave) on its own. */
final class FiscalContainer
{
    private readonly InvoiceRepository $invoices;
    private readonly HotelFiscalProfileRepository $fiscalProfiles;
    private readonly BillingProfileRepository $billingProfiles;
    private readonly TaxLedgerRepository $ledger;
    private ?InvoiceIssuer $issuer = null;

    public function __construct(
        private readonly PDO $connection,
        private readonly Env $env,
        private readonly Clock $clock,
        private readonly TransactionRunner $transaction,
        private readonly HotelRepository $hotels,
        private readonly ReservationRepository $reservations,
        private readonly ChargeRepository $charges,
        private readonly StayViewFactory $stays,
        private readonly string $projectRoot,
    ) {
        $this->invoices = new InvoiceRepository($connection);
        $this->fiscalProfiles = new HotelFiscalProfileRepository($connection);
        $this->billingProfiles = new BillingProfileRepository($connection);
        $this->ledger = new TaxLedgerRepository($connection);
    }

    public function invoiceRequester(): InvoiceRequester
    {
        return new InvoiceRequester($this->invoices, $this->billingProfiles, $this->reservations, $this->charges, $this->stays, $this->clock);
    }

    /** Also used by the CLI worker. */
    public function invoiceProcessor(): InvoiceProcessor
    {
        return new InvoiceProcessor($this->invoices, $this->fiscalProfiles, new TaxRuleRepository($this->connection), $this->ledger, $this->issuer(), $this->transaction, $this->clock);
    }

    public function invoiceService(): InvoiceService
    {
        return new InvoiceService($this->invoices, $this->ledger, $this->issuer(), $this->transaction, $this->clock);
    }

    public function billingProfileService(): BillingProfileService
    {
        return new BillingProfileService($this->billingProfiles, $this->companyLookup(), new BillingProfileInputParser(), $this->clock);
    }

    public function hotelFiscalService(): HotelFiscalService
    {
        return new HotelFiscalService($this->fiscalProfiles, $this->hotels);
    }

    public function invoicePdfService(View $view): InvoicePdfService
    {
        return new InvoicePdfService($this->invoiceService(), $this->fiscalProfiles, $this->hotels, new BreakfastRepository($this->connection), new InvoicePdfRenderer($view, $this->projectRoot));
    }

    /** Public base URL for links printed in documents (QR codes); empty = the current host. */
    public function getAppUrl(): string
    {
        return $this->env->get('APP_URL', '');
    }

    public function monthlyTaxSummary(): MonthlyTaxSummary
    {
        return new MonthlyTaxSummary($this->ledger);
    }

    /** Tests replace the issuer to simulate an authority outage. */
    public function useIssuer(InvoiceIssuer $issuer): void
    {
        $this->issuer = $issuer;
    }

    private function issuer(): InvoiceIssuer
    {
        return $this->issuer ??= (new IssuerFactory($this->env, $this->projectRoot . '/storage/fiscal'))->create();
    }

    /** CNPJ_LOOKUP_DRIVER: "simulacao" (default, offline) or "brasilapi" (Receita Federal open data). */
    private function companyLookup(): CompanyLookup
    {
        $inner = $this->env->get('CNPJ_LOOKUP_DRIVER', SimulatedCompanyLookup::SOURCE) === 'brasilapi'
            ? new BrasilApiCompanyLookup($this->clock)
            : new SimulatedCompanyLookup($this->clock);

        return new CachedCompanyLookup($inner, new CompanyLookupCache($this->connection), $this->clock);
    }
}
