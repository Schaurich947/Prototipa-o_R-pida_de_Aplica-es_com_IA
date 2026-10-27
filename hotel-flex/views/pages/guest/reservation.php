<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Reservation|null $reservation
 * @var HotelFlex\Service\RoomView|null $roomView
 * @var list<HotelFlex\Service\RequestView> $requestViews
 * @var bool $isAdmin
 * @var list<HotelFlex\Domain\Reservation> $reservations
 */
?>
<?= $view->partial('page-header', [
    'title' => 'Minha Reserva',
    'description' => 'Detalhes da estadia, valores e solicitações.',
    'actions' => $isAdmin ? $view->partial('admin-reservation-picker', ['reservations' => $reservations, 'selectedId' => $reservation?->id, 'action' => '/hospede/reserva']) : null,
]) ?>

<?php if ($roomView === null || $roomView->billing === null): ?>
    <?= $view->partial('empty-state', ['message' => 'Reserva não encontrada.']) ?>
<?php else: ?>
    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
            <?= $view->partial('reservation-details', ['roomView' => $roomView]) ?>
            <section class="space-y-3">
                <h2 class="text-base font-semibold">Minhas solicitações</h2>
                <?= $view->partial('request-list', ['requestViews' => $requestViews, 'emptyMessage' => 'Você ainda não fez solicitações.', 'canResolve' => false]) ?>
            </section>
        </div>
        <section class="space-y-3">
            <h2 class="text-base font-semibold">Resumo do faturamento</h2>
            <?= $view->partial('billing-summary', ['billing' => $roomView->billing, 'nights' => $roomView->reservation->nights]) ?>
        </section>
    </div>
<?php endif; ?>
