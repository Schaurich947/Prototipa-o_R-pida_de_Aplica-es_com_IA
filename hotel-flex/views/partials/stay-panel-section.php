<?php

use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\RoomStatus;

/**
 * One stay inside a room card: today's arrival (entry controls) or the in-house guest (exit controls).
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 * @var HotelFlex\Service\RoomCardView $card
 * @var bool $isArrival
 * @var string $activeFilter
 */
$reservation = $stay->reservation;
$type = $isArrival ? RequestType::EarlyCheckIn : RequestType::LateCheckout;
$canCheckIn = $isArrival && $card->inHouse === null && in_array($card->room->status, [RoomStatus::Clean, RoomStatus::Released], true);
?>
<section class="space-y-3 rounded-lg border p-3">
    <div class="flex items-start justify-between gap-2">
        <div>
            <p class="text-xs font-medium text-muted-foreground"><?= $isArrival ? 'Chegada hoje' : 'Hospedado' ?></p>
            <p class="font-medium"><?= e($reservation->code) ?></p>
        </div>
        <p class="text-right text-xs text-muted-foreground">
            <?= $isArrival ? 'Entrada ' . e(formatTime($stay->startsAt())) : 'Saída ' . e(formatMoment($stay->endsAt())) ?>
        </p>
    </div>
    <form method="post" action="<?= e(url("/hotel/reservas/{$reservation->id}/horarios")) ?>" class="space-y-3">
        <?= $view->partial('hidden-filter', ['activeFilter' => $activeFilter]) ?>
        <input type="hidden" name="type" value="<?= e($type->value) ?>">
        <?= $view->partial('flex-hours-slider', ['stay' => $stay, 'type' => $type, 'hours' => $stay->getCurrentHours($type), 'inputId' => "hours-{$reservation->id}"]) ?>
        <?= $view->partial('billing-summary', ['billing' => $stay->billing, 'nights' => $reservation->nights]) ?>
        <?php if ($stay->getMaxHours($type) > 0): ?>
            <button type="submit" class="btn btn-outline btn-sm btn-block">Salvar <?= $isArrival ? 'horário de entrada' : 'horário de saída' ?></button>
        <?php endif; ?>
    </form>
    <?php if ($isArrival && $card->isEarlyVacant): ?>
        <?= $view->partial('post-button', ['action' => "/hotel/quartos/{$card->room->id}/liberar", 'label' => 'Liberar para o Hóspede', 'buttonClass' => 'btn-success', 'activeFilter' => $activeFilter]) ?>
    <?php elseif ($canCheckIn): ?>
        <?= $view->partial('post-button', ['action' => "/hotel/reservas/{$reservation->id}/check-in", 'label' => 'Registrar check-in', 'buttonClass' => 'btn-primary', 'activeFilter' => $activeFilter]) ?>
    <?php elseif (!$isArrival): ?>
        <?= $view->partial('post-button', ['action' => "/hotel/reservas/{$reservation->id}/check-out", 'label' => 'Registrar check-out', 'buttonClass' => 'btn-outline', 'activeFilter' => $activeFilter]) ?>
    <?php endif; ?>
</section>
