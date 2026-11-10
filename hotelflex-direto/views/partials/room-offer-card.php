<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RoomOffer $offer
 * @var HotelFlex\Domain\StayDates $dates
 */
$reviewUrl = url('/hospede/nova-reserva/revisar?' . http_build_query([
    'quarto' => $offer->room->id,
    'entrada' => $dates->checkInDate->format('Y-m-d'),
    'saida' => $dates->checkOutDate->format('Y-m-d'),
]));
?>
<article class="card">
    <header class="card-header">
        <div>
            <h3 class="card-title"><?= e($offer->hotel->name) ?></h3>
            <p class="card-description"><?= e($offer->hotel->city) ?> · <?= e($offer->room->category) ?> · quarto <?= e($offer->room->number) ?></p>
        </div>
    </header>
    <div class="card-content text-sm text-muted-foreground">
        <p><?= e(formatMoney($offer->room->dailyRateCents)) ?>/diária · entrada <?= e(formatHour($offer->hotel->checkInHour)) ?> · saída <?= e(formatHour($offer->hotel->checkOutHour)) ?></p>
        <p>Horas extras: <?= e(formatMoney($offer->room->hourlyRateCents)) ?>/h · cancelamento grátis até <?= e($offer->hotel->freeCancellationHours) ?>h antes</p>
    </div>
    <footer class="card-footer justify-between">
        <div>
            <p class="text-xs text-muted-foreground"><?= e($offer->nights) ?> diária<?= $offer->nights > 1 ? 's' : '' ?></p>
            <p class="font-semibold tabular-nums"><?= e(formatMoney($offer->totalCents())) ?></p>
        </div>
        <a class="btn btn-primary btn-sm" href="<?= e($reviewUrl) ?>">Ver e reservar</a>
    </footer>
</article>
