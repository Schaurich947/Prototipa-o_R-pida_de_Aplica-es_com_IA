<?php

use HotelFlex\Domain\RequestType;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RoomView $roomView
 * @var bool $hasPendingRequest
 * @var bool $isAdmin
 */
$maxHours = $roomView->maxLateCheckoutHours;
$currentHours = $roomView->reservation->lateCheckoutHours;
$suggestedHours = max(1, min($currentHours + 1, $maxHours));
$availabilityText = $maxHours === 0
    ? 'Sem disponibilidade após o horário padrão.'
    : 'Disponível para saída até as ' . formatCheckoutHour($maxHours) . '.';
?>
<section class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title">Late check-out por hora</h2>
            <p class="card-description"><?= e($availabilityText) ?></p>
        </div>
    </header>
    <?php if ($maxHours > 0): ?>
        <form method="post" action="/hospede/solicitacoes" class="contents">
            <div class="card-content">
                <?= $view->partial('guest-request-fields', ['type' => RequestType::LateCheckout->value, 'reservationId' => $roomView->reservation->id, 'isAdmin' => $isAdmin]) ?>
                <?= $view->partial('late-checkout-selector', [
                    'inputId' => 'guest-late-checkout',
                    'hours' => $suggestedHours,
                    'minHours' => 1,
                    'maxHours' => $maxHours,
                    'hourlyRateCents' => $roomView->room->hourlyRateCents,
                ]) ?>
                <?php if ($currentHours > 0): ?>
                    <p class="text-xs text-muted-foreground">Já contratado: <?= e(formatHours($currentHours)) ?> (saída às <?= e(formatCheckoutHour($currentHours)) ?>).</p>
                <?php endif; ?>
            </div>
            <footer class="card-footer">
                <button type="submit" class="btn btn-primary btn-block" <?= $hasPendingRequest ? 'disabled' : '' ?>>
                    <?= $hasPendingRequest ? 'Solicitação enviada · aguardando recepção' : 'Solicitar late check-out' ?>
                </button>
            </footer>
        </form>
    <?php endif; ?>
</section>
