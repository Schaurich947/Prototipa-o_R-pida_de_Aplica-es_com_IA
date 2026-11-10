<?php

use HotelFlex\Domain\PaymentPlan;

/**
 * One payment option with its discount, total and due dates.
 *
 * @var PaymentPlan $plan
 * @var int $baseCents nights before discount
 * @var DateTimeImmutable $firstDueAt
 * @var DateTimeImmutable $checkInAt
 */
$discountCents = PaymentPlan::calculateDiscount($baseCents, $plan->discountPercent());
$totalCents = $baseCents - $discountCents;
$parts = $plan->splitAmount($totalCents);
$restDueAt = $plan->getRestDueAt($checkInAt);
$isAvailable = $plan->isAvailable($firstDueAt, $checkInAt);
?>
<label class="flex items-start gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-primary <?= $isAvailable ? '' : 'opacity-50' ?>">
    <input type="radio" name="payment_plan" value="<?= e($plan->value) ?>" class="mt-0.5 accent-primary"
        <?= $plan === PaymentPlan::Full ? 'checked' : '' ?> <?= $isAvailable ? '' : 'disabled' ?> required>
    <span class="flex-1">
        <span class="flex flex-wrap items-center justify-between gap-2">
            <span class="font-medium"><?= e($plan->label()) ?></span>
            <span class="badge <?= $plan->discountPercent() > 0 ? 'badge-success' : 'badge-outline' ?>">
                <?= $plan->discountPercent() > 0 ? e($plan->discountPercent()) . '% de desconto' : 'sem desconto' ?>
            </span>
        </span>
        <span class="mt-1 block text-muted-foreground">
            Total <strong class="text-foreground"><?= e(formatMoney($totalCents)) ?></strong><?= $discountCents > 0 ? ' (economia de ' . e(formatMoney($discountCents)) . ')' : '' ?> ·
            <?= e(formatMoney($parts[0])) ?> até <?= e(formatMoment($firstDueAt)) ?>
            <?php if (isset($parts[1])): ?>
                + <?= e(formatMoney($parts[1])) ?> <?= $plan->isRestPaidAtDesk() ? 'no hotel, até o check-in' : 'via Pix até ' . e(formatMoment($restDueAt)) ?>
            <?php endif; ?>
        </span>
        <?php if (!$isAvailable): ?>
            <span class="mt-1 block text-xs">Indisponível: a entrada é em menos de <?= e(PaymentPlan::REST_DUE_HOURS_BEFORE_CHECK_IN) ?>h após o prazo do primeiro pagamento.</span>
        <?php endif; ?>
    </span>
</label>
