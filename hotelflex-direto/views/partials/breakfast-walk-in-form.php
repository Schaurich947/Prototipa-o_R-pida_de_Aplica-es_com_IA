<?php

use HotelFlex\Domain\PaymentMethod;
use HotelFlex\Service\BreakfastService;

/**
 * Counter sale for people who arrive without booking, at the walk-in price.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var int $freeSeats
 */
?>
<form method="post" action="<?= e(url('/hotel/cafe/balcao')) ?>" class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title">Venda na hora (sem reserva)</h2>
            <p class="card-description"><?= e(formatMoney($hotel->breakfastWalkInPriceCents)) ?> por pessoa · <?= e($freeSeats) ?> lugar(es) livre(s) agora</p>
        </div>
    </header>
    <div class="card-content">
        <?= $view->partial('csrf-field') ?>
        <input type="hidden" name="hotel_id" value="<?= e($hotel->id) ?>">
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="space-y-2">
                <label class="label" for="walk-in-name">Nome (opcional)</label>
                <input class="input" id="walk-in-name" name="customer_name" maxlength="120">
            </div>
            <div class="space-y-2">
                <label class="label" for="walk-in-people">Pessoas</label>
                <input class="input" type="number" id="walk-in-people" name="people" min="1" max="<?= e(min(BreakfastService::MAX_PEOPLE, max(1, $freeSeats))) ?>" value="1" required>
            </div>
            <div class="space-y-2">
                <label class="label" for="walk-in-method">Pagamento</label>
                <select class="input" id="walk-in-method" name="method" required>
                    <?php foreach (PaymentMethod::deskMethods() as $method): ?>
                        <option value="<?= e($method->value) ?>"><?= e($method->label()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <footer class="card-footer justify-end">
        <button type="submit" class="btn btn-primary" <?= $freeSeats > 0 ? '' : 'disabled' ?>><?= $freeSeats > 0 ? 'Registrar venda' : 'Sem lugares livres' ?></button>
    </footer>
</form>
