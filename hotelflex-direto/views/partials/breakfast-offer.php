<?php

use HotelFlex\Domain\BreakfastPaymentOption;

/**
 * Availability and price options for the chosen hotel, date and people.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var DateTimeImmutable $date
 * @var int $people
 * @var int $seatsLeft
 */
$isAvailable = $seatsLeft >= $people;
?>
<form method="post" action="<?= e(url('/cafe')) ?>" class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title"><?= e($hotel->name) ?> · <?= e($date->format('d/m/Y')) ?></h2>
            <p class="card-description">
                Das <?= e(formatHour($hotel->breakfastStartHour)) ?> às <?= e(formatHour($hotel->breakfastEndHour)) ?> · <?= e(formatMoney($hotel->breakfastPriceCents)) ?> por pessoa
            </p>
        </div>
        <span class="badge <?= $isAvailable ? 'badge-success' : 'badge-destructive' ?>"><?= e($seatsLeft) ?> lugar(es) livre(s)</span>
    </header>
    <div class="card-content">
        <?= $view->partial('csrf-field') ?>
        <input type="hidden" name="hotel_id" value="<?= e($hotel->id) ?>">
        <input type="hidden" name="date" value="<?= e($date->format('Y-m-d')) ?>">
        <input type="hidden" name="people" value="<?= e($people) ?>">
        <?php if (!$isAvailable): ?>
            <p class="alert alert-error">Não há lugares suficientes para <?= e($people) ?> pessoa(s) nesse dia. Tente outra data.</p>
        <?php else: ?>
            <?php foreach (BreakfastPaymentOption::bookingOptions() as $option): ?>
                <label class="flex items-start gap-3 rounded-lg border p-3 text-sm has-[:checked]:border-primary">
                    <input type="radio" name="payment_option" value="<?= e($option->value) ?>" class="mt-0.5 accent-primary" <?= $option === BreakfastPaymentOption::Pix ? 'checked' : '' ?> required>
                    <span class="flex-1">
                        <span class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium"><?= e($option->label()) ?></span>
                            <span class="badge <?= $option->discountPercent() > 0 ? 'badge-success' : 'badge-outline' ?>"><?= $option->discountPercent() > 0 ? e($option->discountPercent()) . '% de desconto' : 'sem desconto' ?></span>
                        </span>
                        <span class="text-muted-foreground"><?= e($people) ?> pessoa(s) · total <strong class="text-foreground"><?= e(formatMoney($option->calculateTotal($hotel->breakfastPriceCents, $people))) ?></strong></span>
                    </span>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if ($isAvailable): ?>
        <footer class="card-footer justify-end"><button type="submit" class="btn btn-primary">Reservar café da manhã</button></footer>
    <?php endif; ?>
</form>
