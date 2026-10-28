<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RoomView $roomView
 * @var string $activeFilter
 */
$room = $roomView->room;
$reservation = $roomView->reservation;
$actionHtml = $view->partial('housekeeping-action', ['roomView' => $roomView, 'activeFilter' => $activeFilter]);
?>
<article class="card <?= $roomView->isEarlyVacant ? 'card-highlight' : '' ?>">
    <header class="card-header">
        <div>
            <h2 class="card-title">Quarto <?= e($room->number) ?></h2>
            <p class="card-description"><?= e($room->category) ?></p>
        </div>
        <?= $view->partial('room-status-badge', ['status' => $room->status, 'isEarlyVacant' => $roomView->isEarlyVacant]) ?>
    </header>
    <div class="card-content">
        <?php if ($reservation === null || $roomView->billing === null): ?>
            <p class="text-sm text-muted-foreground">Sem reserva vinculada.</p>
        <?php else: ?>
            <p class="text-sm">
                <span class="font-medium"><?= e($reservation->guestName) ?></span>
                <span class="text-muted-foreground">· check-in <?= e(formatDate($reservation->checkInDate)) ?></span>
            </p>
            <form method="post" action="<?= e(url("/hotel/reservas/{$reservation->id}/late-checkout")) ?>" class="space-y-3" data-late-checkout-form>
                <?= $view->partial('csrf-field') ?>
                <input type="hidden" name="filtro" value="<?= e($activeFilter) ?>">
                <?= $view->partial('late-checkout-selector', [
                    'inputId' => "late-checkout-{$reservation->id}",
                    'hours' => $reservation->lateCheckoutHours,
                    'minHours' => 0,
                    'maxHours' => $roomView->maxLateCheckoutHours,
                    'hourlyRateCents' => $room->hourlyRateCents,
                ]) ?>
                <?= $view->partial('billing-summary', ['billing' => $roomView->billing, 'nights' => $reservation->nights]) ?>
                <?php if ($roomView->maxLateCheckoutHours > 0): ?>
                    <button type="submit" class="btn btn-outline btn-sm btn-block">Salvar horas adicionais</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
    <?php if (trim($actionHtml) !== ''): ?>
        <footer class="card-footer"><?= $actionHtml ?></footer>
    <?php endif; ?>
</article>
