<?php

use HotelFlex\Domain\HotelPolicy;

/**
 * Slider shared by the reception card and the guest screen.
 *
 * @var HotelFlex\View\View $view
 * @var string $inputId
 * @var int $hours
 * @var int $minHours
 * @var int $maxHours
 * @var int $hourlyRateCents
 */
?>
<?php if ($maxHours === 0): ?>
    <p class="text-sm text-muted-foreground">Sem disponibilidade para late check-out.</p>
<?php else: ?>
    <div class="space-y-3" data-late-checkout data-hourly-rate-cents="<?= e($hourlyRateCents) ?>" data-standard-checkout-hour="<?= e(HotelPolicy::STANDARD_CHECK_OUT_HOUR) ?>">
        <div class="flex items-baseline justify-between gap-2">
            <label class="label" for="<?= e($inputId) ?>">Horas Adicionais Proporcionais (Late Check-out)</label>
            <span class="text-xs text-muted-foreground"><?= e(formatMoney($hourlyRateCents)) ?>/h</span>
        </div>
        <input class="range" type="range" id="<?= e($inputId) ?>" name="hours" min="<?= e($minHours) ?>" max="<?= e($maxHours) ?>" step="1" value="<?= e($hours) ?>" required>
        <div class="flex justify-between text-xs text-muted-foreground" aria-hidden="true">
            <span><?= e(formatCheckoutHour($minHours)) ?></span>
            <span><?= e(formatCheckoutHour($maxHours)) ?></span>
        </div>
        <?= $view->partial('late-checkout-preview', ['hours' => $hours, 'hourlyRateCents' => $hourlyRateCents]) ?>
    </div>
<?php endif; ?>
