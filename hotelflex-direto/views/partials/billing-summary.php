<?php
/**
 * Totals of a stay. The data-* attributes let flex-hours.js update them live.
 *
 * @var HotelFlex\Domain\BillingSummary $billing
 * @var int $nights
 */
$lines = [
    ['label' => "Diárias ({$nights}×)", 'cents' => $billing->baseCents, 'key' => 'base'],
    ['label' => 'Entrada antecipada', 'cents' => $billing->earlyCheckInCents, 'key' => 'early'],
    ['label' => 'Saída tardia', 'cents' => $billing->lateCheckoutCents, 'key' => 'late'],
];
?>
<dl class="space-y-1.5 rounded-lg bg-muted/50 p-3 text-sm" data-billing
    data-base-cents="<?= e($billing->baseCents) ?>" data-early-cents="<?= e($billing->earlyCheckInCents) ?>" data-late-cents="<?= e($billing->lateCheckoutCents) ?>">
    <?php foreach ($lines as $line): ?>
        <div class="flex justify-between text-muted-foreground">
            <dt><?= e($line['label']) ?></dt>
            <dd class="tabular-nums" data-billing-line="<?= e($line['key']) ?>"><?= e(formatMoney($line['cents'])) ?></dd>
        </div>
    <?php endforeach; ?>
    <div class="my-1 h-px bg-border"></div>
    <div class="flex justify-between font-semibold">
        <dt>Total</dt>
        <dd class="tabular-nums" data-billing-total><?= e(formatMoney($billing->totalCents())) ?></dd>
    </div>
</dl>
