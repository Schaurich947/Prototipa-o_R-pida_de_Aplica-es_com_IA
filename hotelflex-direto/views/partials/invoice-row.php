<?php

use HotelFlex\Fiscal\Domain\InvoiceStatus;
use HotelFlex\Fiscal\Service\InvoiceService;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Fiscal\Domain\Invoice $invoice
 * @var string $month
 */
$taxes = $invoice->taxes;
?>
<tr>
    <td>
        <span class="font-medium whitespace-nowrap"><?= e($invoice->kind->label()) ?> <?= e($invoice->formatNumber()) ?></span>
        <span class="block text-xs text-muted-foreground"><?= e(formatDate($invoice->competenceDate)) ?></span>
    </td>
    <td>
        <?= e($invoice->taker->name) ?>
        <span class="block text-xs text-muted-foreground"><?= e($invoice->taker->describeDocument()) ?></span>
    </td>
    <td class="max-w-64 text-xs"><?= e($invoice->description) ?></td>
    <td class="text-right tabular-nums"><?= e(formatMoney($invoice->amountCents)) ?></td>
    <td class="text-right tabular-nums">
        <?= e(formatMoney($taxes->issCents + $taxes->icmsCents)) ?>
        <?php if ($taxes->isIssWithheld): ?><span class="block text-xs text-muted-foreground">ISS retido pelo tomador</span><?php endif; ?>
    </td>
    <td>
        <span class="badge <?= e($invoice->status->badgeClass()) ?>"><?= e($invoice->status->label()) ?></span>
        <?php if ($invoice->lastError !== null && $invoice->status !== InvoiceStatus::Authorized): ?>
            <span class="mt-1 block text-xs text-destructive"><?= e($invoice->lastError) ?></span>
        <?php endif; ?>
        <?php if ($invoice->cancelReason !== null): ?>
            <span class="mt-1 block text-xs text-muted-foreground">Motivo: <?= e($invoice->cancelReason) ?></span>
        <?php endif; ?>
    </td>
    <td class="space-y-2">
        <a class="btn btn-outline btn-sm" href="<?= e(url("/notas/{$invoice->id}")) ?>">Ver</a>
        <?php if ($invoice->accessKey !== null): ?>
            <a class="btn btn-outline btn-sm" href="<?= e(url("/notas/{$invoice->id}/pdf")) ?>" target="_blank" rel="noopener">PDF</a>
        <?php endif; ?>
        <?php if ($invoice->status === InvoiceStatus::Rejected): ?>
            <form method="post" action="<?= e(url("/hotel/notas/{$invoice->id}/reenviar")) ?>">
                <?= $view->partial('csrf-field') ?>
                <input type="hidden" name="month" value="<?= e($month) ?>">
                <button type="submit" class="btn btn-outline btn-sm">Reenviar</button>
            </form>
        <?php elseif ($invoice->status === InvoiceStatus::Authorized): ?>
            <form method="post" action="<?= e(url("/hotel/notas/{$invoice->id}/cancelar")) ?>" class="flex gap-1">
                <?= $view->partial('csrf-field') ?>
                <input type="hidden" name="month" value="<?= e($month) ?>">
                <label class="sr-only" for="cancel-<?= e($invoice->id) ?>">Motivo do cancelamento</label>
                <input class="input h-8 w-40" id="cancel-<?= e($invoice->id) ?>" name="reason" minlength="<?= e(InvoiceService::MIN_CANCEL_REASON_LENGTH) ?>" maxlength="255" placeholder="Motivo (mín. 15)" required>
                <button type="submit" class="btn btn-ghost btn-sm text-destructive">Cancelar</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
