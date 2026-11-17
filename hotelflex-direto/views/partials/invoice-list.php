<?php
/**
 * Invoices of one sale, for the customer.
 *
 * @var list<HotelFlex\Fiscal\Domain\Invoice> $invoices
 * @var string $takerDescription who the invoice will be issued to
 * @var string $whenIssued
 */
?>
<section class="space-y-3">
    <h2 class="text-base font-semibold">Nota fiscal</h2>
    <?php if ($invoices === []): ?>
        <p class="rounded-lg border border-dashed p-3 text-sm text-muted-foreground">
            Emitida <?= e($whenIssued) ?>, em nome de: <strong class="text-foreground"><?= e($takerDescription) ?></strong>.
        </p>
    <?php endif; ?>
    <?php foreach ($invoices as $invoice): ?>
        <div class="flex items-center justify-between gap-2 rounded-lg border p-3 text-sm">
            <div>
                <p class="font-medium"><?= e($invoice->kind->label()) ?> <?= e($invoice->formatNumber()) ?> · <?= e(formatMoney($invoice->amountCents)) ?></p>
                <p class="text-muted-foreground"><?= e($invoice->taker->name) ?> · <span class="badge <?= e($invoice->status->badgeClass()) ?>"><?= e($invoice->status->label()) ?></span></p>
            </div>
            <a class="btn btn-outline btn-sm" href="<?= e(url("/notas/{$invoice->id}")) ?>">Ver nota</a>
        </div>
    <?php endforeach; ?>
</section>
