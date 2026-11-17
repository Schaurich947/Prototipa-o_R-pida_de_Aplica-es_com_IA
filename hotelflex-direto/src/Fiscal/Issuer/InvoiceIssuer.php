<?php

declare(strict_types=1);

namespace HotelFlex\Fiscal\Issuer;

use HotelFlex\Fiscal\Domain\HotelFiscalProfile;
use HotelFlex\Fiscal\Domain\Invoice;

/** One implementation per channel: simulation now; national NFS-e API or a gateway later. */
interface InvoiceIssuer
{
    public function getName(): string;

    public function issue(Invoice $invoice, HotelFiscalProfile $issuer): IssueResult;

    /** Status of a declaration already sent; null when the authority never received it. Used before any retry. */
    public function findByDpsId(string $dpsId): ?IssueResult;

    public function cancel(Invoice $invoice, string $reason): IssueResult;
}
