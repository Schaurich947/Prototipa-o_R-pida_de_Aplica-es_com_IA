<?php
/**
 * @var HotelFlex\Domain\BillingSummary $billing
 * @var int $nights
 */
?>
<dl class="space-y-1.5 rounded-lg bg-muted/50 p-3 text-sm" data-billing data-base-cents="<?= e($billing->baseCents) ?>">
    <div class="flex justify-between text-muted-foreground">
        <dt>Valor base (<?= e($nights) ?> diária<?= $nights > 1 ? 's' : '' ?>)</dt>
        <dd class="tabular-nums"><?= e(formatMoney($billing->baseCents)) ?></dd>
    </div>
    <div class="flex justify-between text-muted-foreground">
        <dt>Valor adicional</dt>
        <dd class="tabular-nums" data-billing-additional><?= e(formatMoney($billing->additionalCents)) ?></dd>
    </div>
    <div class="my-1 h-px bg-border"></div>
    <div class="flex justify-between font-semibold">
        <dt>Total</dt>
        <dd class="tabular-nums" data-billing-total><?= e(formatMoney($billing->totalCents())) ?></dd>
    </div>
</dl>
