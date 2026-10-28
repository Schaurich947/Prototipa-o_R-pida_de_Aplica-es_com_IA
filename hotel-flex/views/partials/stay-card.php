<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 */
$reservation = $stay->reservation;
?>
<article class="card">
    <header class="card-header">
        <div>
            <h3 class="card-title"><?= e($stay->hotel->name) ?></h3>
            <p class="card-description"><?= e($stay->hotel->city) ?> · <?= e($stay->room->category) ?> · <?= e($reservation->code) ?></p>
        </div>
        <?= $view->partial('reservation-status-badge', ['status' => $reservation->status]) ?>
    </header>
    <div class="card-content">
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <div><dt class="text-xs text-muted-foreground">Entrada</dt><dd class="font-medium"><?= e(formatMoment($stay->startsAt())) ?></dd></div>
            <div><dt class="text-xs text-muted-foreground">Saída</dt><dd class="font-medium"><?= e(formatMoment($stay->endsAt())) ?></dd></div>
        </dl>
    </div>
    <footer class="card-footer justify-between">
        <span class="font-semibold tabular-nums"><?= e(formatMoney($stay->billing->totalCents())) ?></span>
        <a class="btn btn-outline btn-sm" href="<?= e(url("/hospede/reservas/{$reservation->id}")) ?>">Ver detalhes</a>
    </footer>
</article>
