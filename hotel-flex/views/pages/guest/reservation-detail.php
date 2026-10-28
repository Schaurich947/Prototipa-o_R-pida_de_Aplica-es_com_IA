<?php

use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\ReservationStatus;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 * @var list<HotelFlex\Service\RequestView> $requestViews
 * @var bool $isEditable
 * @var array<string, mixed> $old
 * @var DateTimeImmutable $today
 */
$reservation = $stay->reservation;
$hasPending = static fn (RequestType $type): bool => array_filter(
    $requestViews,
    static fn ($requestView) => $requestView->request->type === $type && $requestView->request->isPending(),
) !== [];
$details = [
    'Hotel' => "{$stay->hotel->name} · {$stay->hotel->city}",
    'Quarto' => "{$stay->room->number} · {$stay->room->category}",
    'Entrada' => formatMoment($stay->startsAt()),
    'Saída' => formatMoment($stay->endsAt()),
    'Diárias' => (string) $reservation->nights,
    'Tolerância de entrada' => $stay->hotel->earlyFreeHours > 0 ? formatHours($stay->hotel->earlyFreeHours) . ' grátis' : 'não há',
];
?>
<a class="text-sm text-muted-foreground hover:text-foreground" href="<?= e(url('/hospede/reservas')) ?>">← Minhas reservas</a>
<?= $view->partial('page-header', [
    'title' => "Reserva {$reservation->code}",
    'description' => "{$stay->hotel->name} · {$stay->guestName}",
    'actions' => $view->partial('reservation-status-badge', ['status' => $reservation->status]),
]) ?>

<div class="grid items-start gap-6 lg:grid-cols-[2fr_1fr]">
    <div class="space-y-6">
        <section class="card">
            <div class="card-content">
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <?php foreach ($details as $label => $value): ?>
                        <div class="space-y-0.5">
                            <dt class="text-xs text-muted-foreground"><?= e($label) ?></dt>
                            <dd class="font-medium"><?= e($value) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </section>

        <?php if ($reservation->status->isActive()): ?>
            <div class="grid gap-4 md:grid-cols-2">
                <?php if ($reservation->status === ReservationStatus::Confirmed): ?>
                    <?= $view->partial('stay-flex-request-card', ['stay' => $stay, 'type' => RequestType::EarlyCheckIn, 'hasPendingRequest' => $hasPending(RequestType::EarlyCheckIn)]) ?>
                <?php endif; ?>
                <?= $view->partial('stay-flex-request-card', ['stay' => $stay, 'type' => RequestType::LateCheckout, 'hasPendingRequest' => $hasPending(RequestType::LateCheckout)]) ?>
            </div>
        <?php endif; ?>

        <section class="space-y-3">
            <h2 class="text-base font-semibold">Pedidos ao hotel</h2>
            <?= $view->partial('request-list', ['requestViews' => $requestViews, 'emptyMessage' => 'Nenhum pedido feito para esta reserva.', 'canResolve' => false]) ?>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="space-y-3">
            <h2 class="text-base font-semibold">Valores</h2>
            <?= $view->partial('billing-summary', ['billing' => $stay->billing, 'nights' => $reservation->nights]) ?>
        </section>
        <?php if ($isEditable): ?>
            <?= $view->partial('change-dates-card', ['stay' => $stay, 'old' => $old, 'minDate' => $today->format('Y-m-d')]) ?>
            <form method="post" action="<?= e(url("/hospede/reservas/{$reservation->id}/cancelar")) ?>" data-confirm="Cancelar a reserva <?= e($reservation->code) ?>? Esta ação não pode ser desfeita.">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="btn btn-ghost btn-block text-destructive">Cancelar reserva</button>
            </form>
        <?php endif; ?>
    </aside>
</div>
