<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RoomOffer $offer
 * @var string $checkInDate
 */
?>
<article class="card">
    <header class="card-header">
        <div>
            <h3 class="card-title"><?= e($offer->room->category) ?></h3>
            <p class="card-description">Quarto <?= e($offer->room->number) ?> · <?= e(formatMoney($offer->room->dailyRateCents)) ?>/diária</p>
        </div>
    </header>
    <div class="card-content text-sm text-muted-foreground">
        Entrada <?= e(formatHour($offer->hotel->checkInHour)) ?> · saída <?= e(formatHour($offer->hotel->checkOutHour)) ?>
        · horas extras <?= e(formatMoney($offer->room->hourlyRateCents)) ?>/h
    </div>
    <footer class="card-footer justify-between">
        <span class="font-semibold tabular-nums"><?= e(formatMoney($offer->totalCents())) ?></span>
        <form method="post" action="<?= e(url('/hospede/nova-reserva')) ?>">
            <?= $view->partial('csrf-field') ?>
            <input type="hidden" name="room_id" value="<?= e($offer->room->id) ?>">
            <input type="hidden" name="check_in_date" value="<?= e($checkInDate) ?>">
            <input type="hidden" name="nights" value="<?= e($offer->nights) ?>">
            <button type="submit" class="btn btn-primary btn-sm">Reservar</button>
        </form>
    </footer>
</article>
