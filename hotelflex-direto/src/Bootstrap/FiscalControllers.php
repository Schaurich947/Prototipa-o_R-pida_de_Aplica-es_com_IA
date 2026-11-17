<?php

declare(strict_types=1);

namespace HotelFlex\Bootstrap;

use HotelFlex\Domain\Clock;
use HotelFlex\Http\Auth;
use HotelFlex\Http\ContextSelector;
use HotelFlex\Http\Controller\ActionRunner;
use HotelFlex\Http\Controller\BillingProfileController;
use HotelFlex\Http\Controller\HotelFiscalController;
use HotelFlex\Http\Controller\InvoiceController;
use HotelFlex\Http\Session;
use HotelFlex\Repository\HotelRepository;
use HotelFlex\View\View;

/** Builds the fiscal module's screens on demand. */
final class FiscalControllers
{
    public function __construct(
        private readonly FiscalContainer $fiscal,
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly ContextSelector $context,
        private readonly HotelRepository $hotels,
        private readonly ActionRunner $actions,
        private readonly View $view,
        private readonly Clock $clock,
    ) {
    }

    public function billingProfiles(): BillingProfileController
    {
        return new BillingProfileController($this->auth, $this->session, $this->fiscal->billingProfileService(), $this->actions, $this->view);
    }

    public function hotelFiscal(): HotelFiscalController
    {
        return new HotelFiscalController($this->auth, $this->session, $this->context, $this->fiscal->hotelFiscalService(), $this->actions, $this->view);
    }

    public function invoices(): InvoiceController
    {
        return new InvoiceController(
            $this->auth, $this->context, $this->fiscal->invoiceService(), $this->fiscal->invoiceProcessor(), $this->fiscal->monthlyTaxSummary(),
            $this->fiscal->hotelFiscalService(), $this->hotels, $this->actions, $this->view, $this->clock,
        );
    }
}
