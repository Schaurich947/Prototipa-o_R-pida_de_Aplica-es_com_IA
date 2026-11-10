<?php

use HotelFlex\Domain\RoomStatus;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RoomCardView $card
 * @var string $activeFilter
 */
$room = $card->room;
$housekeepingAction = match ($room->status) {
    RoomStatus::Dirty => ['path' => 'iniciar-limpeza', 'label' => 'Iniciar limpeza'],
    RoomStatus::Cleaning => ['path' => 'marcar-limpo', 'label' => 'Marcar como limpo'],
    default => null,
};
$stays = array_filter(['inHouse' => $card->inHouse, 'arrival' => $card->arrival]);
?>
<article class="card <?= $card->isEarlyVacant ? 'card-highlight' : '' ?>">
    <header class="card-header">
        <div>
            <h2 class="card-title">Quarto <?= e($room->number) ?></h2>
            <p class="card-description"><?= e($room->category) ?> · <?= e(formatMoney($room->dailyRateCents)) ?>/diária</p>
        </div>
        <?= $view->partial('room-status-badge', ['status' => $room->status, 'isEarlyVacant' => $card->isEarlyVacant]) ?>
    </header>
    <div class="card-content">
        <?php if ($stays === []): ?>
            <p class="text-sm text-muted-foreground">Sem hóspede hoje.</p>
        <?php endif; ?>
        <?php foreach ($stays as $role => $stay): ?>
            <div>
                <p class="mb-1 text-sm font-medium"><?= e($stay->guestName) ?></p>
                <?= $view->partial('stay-panel-section', ['stay' => $stay, 'card' => $card, 'isArrival' => $role === 'arrival', 'activeFilter' => $activeFilter]) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($housekeepingAction !== null): ?>
        <footer class="card-footer">
            <?= $view->partial('post-button', ['action' => "/hotel/quartos/{$room->id}/{$housekeepingAction['path']}", 'label' => $housekeepingAction['label'], 'buttonClass' => 'btn-outline', 'activeFilter' => $activeFilter]) ?>
        </footer>
    <?php endif; ?>
</article>
