<?php

use HotelFlex\Domain\RequestType;

/**
 * Slider for early check-in (hours before) or late check-out (hours after).
 * The server renders the first state; flex-hours.js keeps the preview live.
 *
 * @var HotelFlex\Service\StayView $stay
 * @var RequestType $type
 * @var int $hours
 * @var string $inputId
 */
$isEarly = $type === RequestType::EarlyCheckIn;
$maxHours = $stay->getMaxHours($type);
$baseHour = $isEarly ? $stay->hotel->checkInHour : $stay->hotel->checkOutHour;
$momentHour = $isEarly ? $baseHour - $hours : $baseHour + $hours;
$feeCents = $stay->calculateFee($type, $hours);
$freeHours = $isEarly ? $stay->hotel->earlyFreeHours : 0;
?>
<?php if ($maxHours === 0): ?>
    <p class="text-sm text-muted-foreground">
        <?= $isEarly ? 'Sem horário livre antes da entrada padrão' : 'Sem horário livre depois da saída padrão' ?> (<?= e(formatHour($baseHour)) ?>).
    </p>
<?php else: ?>
    <div class="space-y-2" data-flex-hours data-kind="<?= $isEarly ? 'early' : 'late' ?>" data-base-hour="<?= e($baseHour) ?>"
        data-hourly-rate-cents="<?= e($stay->room->hourlyRateCents) ?>" data-free-hours="<?= e($freeHours) ?>">
        <div class="flex items-baseline justify-between gap-2">
            <label class="label" for="<?= e($inputId) ?>"><?= $isEarly ? 'Horas antes da entrada' : 'Horas depois da saída' ?></label>
            <span class="text-xs text-muted-foreground"><?= e(formatMoney($stay->room->hourlyRateCents)) ?>/h<?= $freeHours > 0 ? ' · ' . e(formatHours($freeHours)) . ' grátis' : '' ?></span>
        </div>
        <input class="range" type="range" id="<?= e($inputId) ?>" name="hours" min="0" max="<?= e($maxHours) ?>" step="1" value="<?= e($hours) ?>" required>
        <div class="flex justify-between text-xs text-muted-foreground" aria-hidden="true">
            <span><?= e(formatHour($baseHour)) ?></span>
            <span><?= e(formatHour($isEarly ? $baseHour - $maxHours : $baseHour + $maxHours)) ?></span>
        </div>
        <p class="text-sm" aria-live="polite" data-preview>
            <span class="font-medium"><?= $isEarly ? 'Entrada' : 'Saída' ?> às <?= e(formatHour($momentHour)) ?></span>
            <?php if ($hours > 0): ?>
                <span class="text-muted-foreground">· <?= e(formatHours($hours)) ?> <?= $isEarly ? 'antes' : 'depois' ?> ·</span>
                <span class="font-semibold <?= $feeCents > 0 ? 'text-success' : '' ?>"><?= $feeCents > 0 ? '+' . e(formatMoney($feeCents)) : 'sem custo' ?></span>
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>
