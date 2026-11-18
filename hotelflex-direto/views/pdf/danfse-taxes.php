<?php
/**
 * Municipal, federal and reform taxes, totals and the Lei 12.741/2012 approximation.
 *
 * @var HotelFlex\Fiscal\Pdf\InvoicePdfData $pdf
 * @var callable(string, string, int=): string $field
 * @var callable(int): string $money
 */
$invoice = $pdf->invoice;
$taxes = $invoice->taxes;
$rate = number_format($pdf->issuer->issRateBasisPoints / 100, 2, ',', '') . '%';
$withheldIss = $taxes->isIssWithheld ? $taxes->issCents : 0;
?>
<table class="box">
    <tr><td colspan="4" class="section">Tributação municipal</td></tr>
    <tr><?= $field('Tributação do ISSQN', 'Operação tributável') ?><?= $field('Município de Incidência', $pdf->hotel->city) ?><?= $field('Regime Especial de Tributação', 'Nenhum') ?><?= $field('Benefício Municipal', '-') ?></tr>
    <tr><?= $field('Valor do Serviço', $money($invoice->amountCents)) ?><?= $field('Desconto Incondicionado', $money(0)) ?><?= $field('Total Deduções/Reduções', $money(0)) ?><?= $field('BC ISSQN', $money($invoice->amountCents)) ?></tr>
    <tr><?= $field('Alíquota Aplicada', $rate) ?><?= $field('Retenção do ISSQN', $taxes->isIssWithheld ? 'Retido pelo Tomador' : 'Não Retido') ?><?= $field('ISSQN Apurado', $money($taxes->issCents), 2) ?></tr>
</table>

<table class="box">
    <tr><td colspan="5" class="section">Tributação federal</td></tr>
    <tr><?= $field('IRRF', '-') ?><?= $field('Contribuição Previdenciária', '-') ?><?= $field('CSLL', '-') ?><?= $field('PIS', '-') ?><?= $field('COFINS', '-') ?></tr>
</table>

<table class="box">
    <tr><td colspan="3" class="section">IBS / CBS · LC 214/2025 <?= $taxes->isReformInformative ? '(ano de teste: valores informativos, sem recolhimento)' : '' ?></td></tr>
    <tr><?= $field('CBS', $money($taxes->cbsCents)) ?><?= $field('IBS', $money($taxes->ibsCents)) ?><?= $field('Crédito para o tomador', $taxes->allowsAcquirerCredit ? 'Permitido' : 'Não permitido (regime específico)') ?></tr>
</table>

<table class="box">
    <tr><td colspan="4" class="section">Valor total da NFS-e</td></tr>
    <tr><?= $field('Valor do Serviço', $money($invoice->amountCents)) ?><?= $field('Desconto Condicionado', $money(0)) ?><?= $field('ISSQN Retido', $money($withheldIss)) ?><?= $field('Valor Líquido da NFS-e', $money($pdf->netCents())) ?></tr>
</table>

<table class="box">
    <tr><td colspan="3" class="section">Totais aproximados dos tributos (Lei 12.741/2012)</td></tr>
    <tr><?= $field('Federais (CBS)', $money($taxes->cbsCents)) ?><?= $field('Estaduais e municipais (IBS)', $money($taxes->ibsCents)) ?><?= $field('Municipais (ISS)', $money($taxes->issCents)) ?></tr>
</table>
