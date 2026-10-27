<?php

use HotelFlex\Domain\RequestType;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Reservation|null $reservation
 * @var HotelFlex\Service\RoomView|null $roomView
 * @var list<HotelFlex\Service\RequestView> $requestViews
 * @var bool $isAdmin
 * @var list<HotelFlex\Domain\Reservation> $reservations
 */
$hasPending = static fn (RequestType $type): bool => array_filter(
    $requestViews,
    static fn ($requestView) => $requestView->request->type === $type && $requestView->request->isPending(),
) !== [];
?>
<?= $view->partial('page-header', [
    'title' => 'Disponibilidade',
    'description' => 'Veja se o seu quarto está livre mais cedo e se dá para sair mais tarde.',
    'actions' => $isAdmin ? $view->partial('admin-reservation-picker', ['reservations' => $reservations, 'selectedId' => $reservation?->id, 'action' => '/hospede/disponibilidade']) : null,
]) ?>

<?php if ($roomView === null): ?>
    <?= $view->partial('empty-state', ['message' => 'Reserva não encontrada.']) ?>
<?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
        <?= $view->partial('early-checkin-card', ['roomView' => $roomView, 'hasPendingRequest' => $hasPending(RequestType::EarlyCheckIn), 'isAdmin' => $isAdmin]) ?>
        <?= $view->partial('late-checkout-request-card', ['roomView' => $roomView, 'hasPendingRequest' => $hasPending(RequestType::LateCheckout), 'isAdmin' => $isAdmin]) ?>
    </div>
<?php endif; ?>
