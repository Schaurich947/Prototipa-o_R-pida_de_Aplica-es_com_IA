<?php

use HotelFlex\Domain\HotelPolicy;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\RoomStatus;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RoomView $roomView
 * @var bool $hasPendingRequest
 * @var bool $isAdmin
 */
$statusMessage = match (true) {
    $roomView->room->status === RoomStatus::Released => 'Seu quarto já está liberado. Você pode fazer o check-in agora.',
    $roomView->isEarlyVacant => 'Seu quarto ficou pronto antes do horário. Solicite a entrada antecipada sem custo.',
    default => 'O quarto ainda está em preparação. Esta tela atualiza sozinha quando ele ficar pronto.',
};
?>
<section class="card <?= $roomView->isEarlyVacant ? 'card-highlight' : '' ?>">
    <header class="card-header">
        <div>
            <h2 class="card-title">Entrada antecipada</h2>
            <p class="card-description">Check-in padrão às <?= e(formatHour(HotelPolicy::STANDARD_CHECK_IN_HOUR)) ?></p>
        </div>
        <?php if ($roomView->isEarlyVacant): ?>
            <?= $view->partial('early-vacant-badge') ?>
        <?php endif; ?>
    </header>
    <div class="card-content">
        <p class="text-sm"><?= e($statusMessage) ?></p>
    </div>
    <?php if ($roomView->isEarlyVacant): ?>
        <footer class="card-footer">
            <form method="post" action="/hospede/solicitacoes" class="w-full">
                <?= $view->partial('guest-request-fields', ['type' => RequestType::EarlyCheckIn->value, 'reservationId' => $roomView->reservation->id, 'isAdmin' => $isAdmin]) ?>
                <button type="submit" class="btn btn-success btn-block" <?= $hasPendingRequest ? 'disabled' : '' ?>>
                    <?= $hasPendingRequest ? 'Solicitação enviada · aguardando recepção' : 'Solicitar entrada antecipada' ?>
                </button>
            </form>
        </footer>
    <?php endif; ?>
</section>
