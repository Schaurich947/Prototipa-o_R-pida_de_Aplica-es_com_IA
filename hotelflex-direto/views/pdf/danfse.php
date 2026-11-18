<?php

use HotelFlex\Fiscal\Domain\InvoiceStatus;
use HotelFlex\Fiscal\Issuer\InvoiceXmlBuilder;

/**
 * DANFSe in the layout of the national NFS-e (Documento Auxiliar da NFS-e), marked as a simulation.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Fiscal\Pdf\InvoicePdfData $pdf
 */
$invoice = $pdf->invoice;
$issuer = $pdf->issuer;
$taxes = $invoice->taxes;
$taker = $invoice->taker;
$field = static fn (string $label, string $value, int $span = 1): string => $view->renderDocument('danfse-field', ['label' => $label, 'value' => $value, 'span' => $span]);
$money = static fn (int $cents): string => formatMoney($cents);
$issuedAt = $invoice->issuedAt !== null ? $invoice->issuedAt->format('d/m/Y H:i:s') : '';
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><?= $view->renderDocument('document-style') ?></head>
<body>
<div class="watermark">SIMULAÇÃO<?= $invoice->status === InvoiceStatus::Cancelled ? ' · CANCELADA' : '' ?></div>
<div class="test-banner">EMITIDA EM AMBIENTE DE SIMULAÇÃO (HOMOLOGAÇÃO) – SEM VALOR FISCAL · DOCUMENTO APENAS PARA CONSULTA</div>

<table class="box">
    <tr>
        <td style="width: 70%; border: 0;">
            <span class="value" style="font-size: 12pt;">DANFSe v1.0</span>
            <span class="label" style="font-size: 8pt;">Documento Auxiliar da NFS-e</span>
            <span class="label">Prefeitura de <?= e($pdf->hotel->city) ?> · Ambiente: simulação</span>
        </td>
        <td style="width: 30%; border: 0;" class="center">
            <img src="<?= e($pdf->qrCodeDataUri) ?>" style="width: 80px; height: 80px;" alt="">
            <span class="label">Consulta: <?= e($pdf->consultUrl) ?></span>
        </td>
    </tr>
</table>

<table class="box">
    <tr><?= $field('Chave de Acesso da NFS-e', $pdf->formatAccessKey(), 3) ?></tr>
    <tr><?= $field('Número da NFS-e', (string) $invoice->number) ?><?= $field('Competência da NFS-e', $invoice->competenceDate->format('d/m/Y')) ?><?= $field('Data e Hora da emissão da NFS-e', $issuedAt) ?></tr>
    <tr><?= $field('Número da DPS', (string) $invoice->number) ?><?= $field('Série da DPS', (string) $invoice->series) ?><?= $field('Data e Hora da emissão da DPS', $issuedAt) ?></tr>
</table>

<table class="box">
    <tr><td colspan="3" class="section">Emitente da NFS-e · Prestador do serviço</td></tr>
    <tr><?= $field('CNPJ / CPF / NIF', $issuer->cnpj->format()) ?><?= $field('Inscrição Municipal', $issuer->municipalRegistration) ?><?= $field('Telefone', $pdf->hotel->whatsappPhone?->format() ?? '') ?></tr>
    <tr><?= $field('Nome / Nome Empresarial', $issuer->legalName, 2) ?><?= $field('Regime tributário', $issuer->taxRegime->label()) ?></tr>
    <tr><?= $field('Endereço', $issuer->address, 2) ?><?= $field('Município (IBGE)', "{$pdf->hotel->city} ({$issuer->municipalityCode})") ?></tr>
</table>

<table class="box">
    <tr><td colspan="3" class="section">Tomador do serviço</td></tr>
    <tr><?= $field('CNPJ / CPF / NIF', $taker->describeDocument()) ?><?= $field('Inscrição Estadual', $taker->stateRegistration ?? '') ?><?= $field('E-mail', $taker->email ?? '') ?></tr>
    <tr><?= $field('Nome / Nome Empresarial', $taker->name, 3) ?></tr>
    <tr><?= $field('Endereço', $taker->address ?? '', 3) ?></tr>
</table>

<table class="box">
    <tr><td class="section">Intermediário do serviço</td></tr>
    <tr><td class="center">NÃO IDENTIFICADO NA NFS-e</td></tr>
</table>

<table class="box">
    <tr><td colspan="3" class="section">Serviço prestado</td></tr>
    <tr><?= $field('Código de Tributação Nacional', "{$issuer->serviceCode} - Hospedagem em hotéis e congêneres") ?><?= $field('Local da Prestação', $pdf->hotel->city) ?><?= $field('País da Prestação', 'Brasil') ?></tr>
    <tr><?= $field('Descrição do Serviço', $invoice->description, 3) ?></tr>
</table>

<?= $view->renderDocument('danfse-taxes', ['pdf' => $pdf, 'field' => $field, 'money' => $money]) ?>

<table class="box">
    <tr><td class="section">Informações complementares</td></tr>
    <tr><td>
        <?= e(InvoiceXmlBuilder::SIMULATION_NOTICE) ?>. Gerado pelo simulador HotelFlex: não foi transmitido à prefeitura nem ao Ambiente de Dados Nacional.
        <?php if ($invoice->status === InvoiceStatus::Cancelled): ?><br><strong>NFS-e CANCELADA.</strong> Motivo: <?= e((string) $invoice->cancelReason) ?><?php endif; ?>
    </td></tr>
</table>
</body></html>
