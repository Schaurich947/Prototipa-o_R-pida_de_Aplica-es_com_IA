<?php
/**
 * Check-in and check-out dates; the number of nights is only informative
 * (filled by date-range.js while choosing and recalculated by the server).
 *
 * @var string $idPrefix
 * @var string $checkInName
 * @var string $checkOutName
 * @var string $checkInValue
 * @var string $checkOutValue
 * @var string $minDate
 */
$nights = '';
if ($checkInValue !== '' && $checkOutValue !== '' && strtotime($checkOutValue) > strtotime($checkInValue)) {
    $nights = (string) (int) round((strtotime($checkOutValue) - strtotime($checkInValue)) / 86400);
}
?>
<div class="grid gap-3 sm:grid-cols-3 sm:items-end" data-date-range>
    <div class="space-y-2">
        <label class="label" for="<?= e($idPrefix) ?>-check-in">Entrada</label>
        <input class="input" type="date" id="<?= e($idPrefix) ?>-check-in" name="<?= e($checkInName) ?>" min="<?= e($minDate) ?>" value="<?= e($checkInValue) ?>" required data-check-in>
    </div>
    <div class="space-y-2">
        <label class="label" for="<?= e($idPrefix) ?>-check-out">Saída</label>
        <input class="input" type="date" id="<?= e($idPrefix) ?>-check-out" name="<?= e($checkOutName) ?>" min="<?= e($minDate) ?>" value="<?= e($checkOutValue) ?>" required data-check-out>
    </div>
    <p class="rounded-lg bg-muted/60 px-3 py-2 text-sm" aria-live="polite">
        Diárias: <strong data-nights-output><?= e($nights !== '' ? $nights : '—') ?></strong>
    </p>
</div>
