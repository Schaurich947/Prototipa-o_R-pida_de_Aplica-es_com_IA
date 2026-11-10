<?php

use HotelFlex\Domain\RequestType;

/**
 * Guest asks the hotel to change the entry or exit time of a stay.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 * @var RequestType $type
 * @var bool $hasPendingRequest
 */
$isEarly = $type === RequestType::EarlyCheckIn;
$reservation = $stay->reservation;
$currentHours = $stay->getCurrentHours($type);
$suggestedHours = min($currentHours + 1, $stay->getMaxHours($type));
?>
<section class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title"><?= $isEarly ? 'Entrar mais cedo' : 'Sair mais tarde' ?></h2>
            <p class="card-description">
                <?= $isEarly ? 'Entrada em ' . e(formatDate($reservation->checkInDate)) : 'Saída em ' . e(formatDate($reservation->checkOutDate())) ?>
                · hoje: <?= e(formatTime($isEarly ? $stay->startsAt() : $stay->endsAt())) ?>
            </p>
        </div>
    </header>
    <form method="post" action="<?= e(url("/hospede/reservas/{$reservation->id}/horarios")) ?>" class="contents">
        <div class="card-content">
            <?= $view->partial('csrf-field') ?>
            <input type="hidden" name="type" value="<?= e($type->value) ?>">
            <?= $view->partial('flex-hours-slider', ['stay' => $stay, 'type' => $type, 'hours' => $suggestedHours, 'inputId' => "guest-{$type->value}"]) ?>
        </div>
        <?php if ($stay->getMaxHours($type) > 0): ?>
            <footer class="card-footer">
                <button type="submit" class="btn btn-primary btn-block" <?= $hasPendingRequest ? 'disabled' : '' ?>>
                    <?= $hasPendingRequest ? 'Pedido enviado · aguardando o hotel' : 'Pedir ao hotel' ?>
                </button>
            </footer>
        <?php endif; ?>
    </form>
</section>
