<?php

use HotelFlex\Fiscal\Domain\InvoiceStatus;

/**
 * DANFE NFC-e on 80 mm paper, following the layout of the NFC-e auxiliary document, marked as a simulation.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Fiscal\Pdf\InvoicePdfData $pdf
 */
$invoice = $pdf->invoice;
$issuer = $pdf->issuer;
$taker = $invoice->taker;
$money = static fn (int $cents): string => number_format($cents / 100, 2, ',', '.');
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><?= $view->renderDocument('document-style') ?>
<style>
    @page { margin: 8px; }
    body { font-size: 6.5pt; }
    .rule { border-top: 1px dashed #000; margin: 4px 0; }
    .items td { padding: 1px 2px; border: 0; }
    .watermark { font-size: 26pt; top: 30%; }
</style></head>
<body>
<div class="watermark">SIMULAÇÃO<?= $invoice->status === InvoiceStatus::Cancelled ? ' CANCELADA' : '' ?></div>
<div class="center">
    <strong><?= e($issuer->legalName) ?></strong><br>
    CNPJ: <?= e($issuer->cnpj->format()) ?> · IE: <?= e((string) $issuer->stateRegistration) ?><br>
    <?= e($issuer->address) ?>
</div>
<div class="rule"></div>
<div class="center"><strong>Documento Auxiliar da Nota Fiscal de Consumidor Eletrônica</strong></div>
<div class="test-banner">EMITIDA EM AMBIENTE DE SIMULAÇÃO (HOMOLOGAÇÃO) – SEM VALOR FISCAL</div>

<table class="items">
    <tr><td><strong>Código</strong></td><td><strong>Descrição</strong></td><td class="right"><strong>Qtde</strong></td><td><strong>UN</strong></td><td class="right"><strong>Vl Unit</strong></td><td class="right"><strong>Vl Total</strong></td></tr>
    <?php foreach ($pdf->lines as $line): ?>
        <tr><td><?= e($line->code) ?></td><td><?= e($line->description) ?></td><td class="right"><?= e($line->quantity) ?></td><td>UN</td><td class="right"><?= e($money($line->unitPriceCents)) ?></td><td class="right"><?= e($money($line->totalCents)) ?></td></tr>
    <?php endforeach; ?>
</table>
<div class="rule"></div>
<table class="items">
    <tr><td>Qtde. total de itens</td><td class="right"><?= e(count($pdf->lines)) ?></td></tr>
    <tr><td>Valor total R$</td><td class="right"><?= e($money($pdf->grossCents())) ?></td></tr>
    <tr><td>Desconto R$</td><td class="right"><?= e($money($pdf->discountCents)) ?></td></tr>
    <tr><td><strong>Valor a Pagar R$</strong></td><td class="right"><strong><?= e($money($invoice->amountCents)) ?></strong></td></tr>
    <tr><td><strong>FORMA PAGAMENTO</strong></td><td class="right"><strong>VALOR PAGO R$</strong></td></tr>
    <tr><td><?= e($pdf->paymentLabel) ?></td><td class="right"><?= e($money($invoice->amountCents)) ?></td></tr>
</table>
<div class="rule"></div>
<div class="center">
    Consulte pela Chave de Acesso em<br><?= e($pdf->consultUrl) ?><br>
    <span class="mono"><?= e($pdf->formatAccessKey()) ?></span>
</div>
<div class="rule"></div>
<div class="center">
    <strong>CONSUMIDOR</strong> · <?= e($taker->describeDocument()) ?><br><?= e($taker->name) ?>
</div>
<div class="rule"></div>
<div class="center">
    <strong>NFC-e nº <?= e(sprintf('%09d', (int) $invoice->number)) ?> · Série <?= e(sprintf('%03d', (int) $invoice->series)) ?></strong><br>
    Emissão: <?= e($invoice->issuedAt?->format('d/m/Y H:i:s') ?? '') ?><br>
    Protocolo de autorização: SIMULACAO-<?= e(sprintf('%015d', $invoice->id)) ?><br>
    <img src="<?= e($pdf->qrCodeDataUri) ?>" style="width: 110px; height: 110px; margin-top: 4px;" alt="">
</div>
<div class="rule"></div>
<div class="center">
    Tributos Totais Incidentes (Lei Federal 12.741/2012): R$ <?= e($money($invoice->taxes->approximateTotalCents())) ?><br>
    ICMS R$ <?= e($money($invoice->taxes->icmsCents)) ?> · CBS R$ <?= e($money($invoice->taxes->cbsCents)) ?> · IBS R$ <?= e($money($invoice->taxes->ibsCents)) ?><?= $invoice->taxes->isReformInformative ? ' (informativos)' : '' ?>
    <?php if ($invoice->status === InvoiceStatus::Cancelled): ?><br><strong>NFC-e CANCELADA:</strong> <?= e((string) $invoice->cancelReason) ?><?php endif; ?>
</div>
<div class="test-banner" style="margin-top: 6px;">DOCUMENTO APENAS PARA CONSULTA · SEM VALOR FISCAL</div>
</body></html>
