<?php

use HotelFlex\Domain\Role;
use HotelFlex\Fiscal\Domain\InvoiceKind;
use HotelFlex\Fiscal\Domain\InvoiceStatus;
use HotelFlex\Fiscal\Issuer\InvoiceXmlBuilder;

/**
 * Simulated DANFSe / DANFE NFC-e: the printable representation of the invoice.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Fiscal\Domain\Invoice $invoice
 * @var HotelFlex\Domain\Hotel|null $hotel
 * @var HotelFlex\Fiscal\Domain\HotelFiscalProfile|null $issuer
 * @var HotelFlex\Domain\User $currentUser
 */
$taxes = $invoice->taxes;
$isServiceInvoice = $invoice->kind === InvoiceKind::Nfse;
$taxRows = [
    ...($isServiceInvoice ? ['ISS' . ($taxes->isIssWithheld ? ' (retido pelo tomador)' : '') => $taxes->issCents] : ['ICMS' => $taxes->icmsCents]),
    'CBS' . ($taxes->isReformInformative ? ' (informativo)' : '') => $taxes->cbsCents,
    'IBS' . ($taxes->isReformInformative ? ' (informativo)' : '') => $taxes->ibsCents,
];
?>
<div class="no-print flex flex-wrap items-center gap-2">
    <a class="btn btn-ghost" href="<?= e(url($currentUser->role === Role::Guest ? '/hospede/reservas' : '/hotel/notas')) ?>">← Voltar</a>
    <button type="button" class="btn btn-primary" data-print>Imprimir / salvar PDF</button>
    <?php if ($invoice->accessKey !== null): ?>
        <a class="btn btn-outline" href="<?= e(url("/notas/{$invoice->id}/xml")) ?>">Baixar XML</a>
    <?php endif; ?>
</div>

<p class="simulation-stamp"><?= e(InvoiceXmlBuilder::SIMULATION_NOTICE) ?></p>

<article class="card">
    <header class="card-header">
        <div>
            <h1 class="text-lg font-semibold"><?= e($invoice->kind->description()) ?> · nº <?= e($invoice->formatNumber()) ?></h1>
            <p class="card-description">Competência <?= e(formatDate($invoice->competenceDate)) ?><?= $invoice->issuedAt !== null ? ' · emitida em ' . e(formatMoment($invoice->issuedAt)) : '' ?></p>
        </div>
        <span class="badge <?= e($invoice->status->badgeClass()) ?>"><?= e($invoice->status->label()) ?></span>
    </header>
    <div class="card-content">
        <?php if ($invoice->status === InvoiceStatus::Cancelled): ?>
            <p class="alert alert-error">Nota CANCELADA. Motivo: <?= e((string) $invoice->cancelReason) ?></p>
        <?php endif; ?>
        <section class="grid gap-4 sm:grid-cols-2">
            <div>
                <h2 class="text-xs font-medium text-muted-foreground uppercase">Prestador</h2>
                <p class="font-medium"><?= e($issuer?->legalName ?? $hotel?->name ?? '') ?></p>
                <p>CNPJ <?= e($issuer?->cnpj->format() ?? 'não cadastrado') ?> · IM <?= e($issuer?->municipalRegistration ?? '-') ?></p>
                <p class="text-muted-foreground"><?= e($issuer?->address ?? '') ?></p>
            </div>
            <div>
                <h2 class="text-xs font-medium text-muted-foreground uppercase">Tomador</h2>
                <p class="font-medium"><?= e($invoice->taker->name) ?></p>
                <p><?= e($invoice->taker->describeDocument()) ?></p>
                <p class="text-muted-foreground"><?= e($invoice->taker->address ?? '') ?></p>
            </div>
        </section>
        <section>
            <h2 class="text-xs font-medium text-muted-foreground uppercase">Discriminação do serviço<?= $issuer !== null ? ' · código ' . e($issuer->serviceCode) : '' ?></h2>
            <p><?= e($invoice->description) ?></p>
        </section>
        <dl class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <div class="rounded-lg border p-2"><dt class="text-xs text-muted-foreground">Valor total</dt><dd class="text-lg font-semibold"><?= e(formatMoney($invoice->amountCents)) ?></dd></div>
            <?php foreach ($taxRows as $label => $cents): ?>
                <div class="rounded-lg border p-2"><dt class="text-xs text-muted-foreground"><?= e($label) ?></dt><dd class="font-medium"><?= e(formatMoney($cents)) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <p class="text-sm">Valor aproximado dos tributos: <strong><?= e(formatMoney($taxes->approximateTotalCents())) ?></strong> (Lei 12.741/2012).</p>
        <?php if (!$taxes->allowsAcquirerCredit && $invoice->taker->isCompany()): ?>
            <p class="text-xs text-muted-foreground">Hospedagem e alimentação: sem crédito de IBS/CBS para o tomador (regime específico, LC 214/2025).</p>
        <?php endif; ?>
        <p class="font-mono text-xs break-all text-muted-foreground">Chave de acesso: <?= e($invoice->accessKey ?? 'aguardando autorização') ?></p>
    </div>
</article>
<p class="simulation-stamp"><?= e(InvoiceXmlBuilder::SIMULATION_NOTICE) ?></p>
