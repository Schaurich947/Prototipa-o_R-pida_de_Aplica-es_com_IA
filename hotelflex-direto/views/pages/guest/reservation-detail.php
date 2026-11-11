<?php

use HotelFlex\Domain\CancellationPolicy;
use HotelFlex\Domain\ChargeStatus;
use HotelFlex\Domain\RequestType;
use HotelFlex\Domain\ReservationStatus;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 * @var list<HotelFlex\Service\RequestView> $requestViews
 * @var bool $isEditable
 * @var array<string, mixed> $old
 * @var DateTimeImmutable $today
 * @var list<HotelFlex\Domain\Charge> $charges
 * @var list<HotelFlex\Domain\Notification> $messages
 * @var bool $isRefundable
 * @var HotelFlex\Domain\User $currentUser
 */
$reservation = $stay->reservation;
$hasPending = static fn (RequestType $type): bool => array_filter(
    $requestViews,
    static fn ($requestView) => $requestView->request->type === $type && $requestView->request->isPending(),
) !== [];
$hasReceiptUnderReview = array_filter($charges, static fn ($charge) => $charge->status === ChargeStatus::UnderReview) !== [];
$details = [
    'Hotel' => "{$stay->hotel->name} · {$stay->hotel->city}",
    'Quarto' => "{$stay->room->number} · {$stay->room->category}",
    'Entrada' => formatMoment($stay->startsAt()),
    'Saída' => formatMoment($stay->endsAt()),
    'Diárias' => (string) $reservation->nights,
    'Cancelamento grátis até' => formatMoment(CancellationPolicy::getFreeCancellationDeadline($stay->hotel, $reservation)),
    'Tolerância de entrada' => $stay->hotel->earlyFreeHours > 0 ? formatHours($stay->hotel->earlyFreeHours) . ' grátis' : 'não há',
];
?>
<a class="text-sm text-muted-foreground hover:text-foreground" href="<?= e(url('/hospede/reservas')) ?>">← Minhas reservas</a>
<?= $view->partial('page-header', [
    'title' => "Reserva {$reservation->code}",
    'description' => "{$stay->hotel->name} · {$stay->guestName}",
    'actions' => $view->partial('reservation-status-badge', ['status' => $reservation->status]),
]) ?>

<?= $view->partial('reservation-progress', ['status' => $reservation->status, 'hasReceiptUnderReview' => $hasReceiptUnderReview]) ?>

<div class="grid items-start gap-6 lg:grid-cols-[2fr_1fr]">
    <div class="space-y-6">
        <section class="card">
            <header class="card-header">
                <div>
                    <h2 class="card-title">Pagamento direto ao hotel (Pix)</h2>
                    <p class="card-description">Sem intermediário: o valor vai direto para o hotel, que confere o comprovante.</p>
                </div>
            </header>
            <div class="card-content">
                <ul class="space-y-3">
                    <?php foreach ($charges as $charge): ?>
                        <?= $view->partial('charge-item', ['charge' => $charge, 'stay' => $stay, 'currentUser' => $currentUser]) ?>
                    <?php endforeach; ?>
                </ul>
                <?= $view->partial('whatsapp-contact-button', ['stay' => $stay, 'toGuest' => false]) ?>
            </div>
        </section>

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

        <?php if ($reservation->status === ReservationStatus::PendingPayment): ?>
            <p class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                <strong class="text-foreground">Entrar mais cedo ou sair mais tarde:</strong> disponível depois que o pagamento da reserva for confirmado pelo hotel.
            </p>
        <?php elseif ($reservation->status->isActive()): ?>
            <div class="grid gap-4 md:grid-cols-2">
                <?php if ($reservation->status === ReservationStatus::Confirmed): ?>
                    <?= $view->partial('stay-flex-request-card', ['stay' => $stay, 'type' => RequestType::EarlyCheckIn, 'hasPendingRequest' => $hasPending(RequestType::EarlyCheckIn)]) ?>
                <?php endif; ?>
                <?= $view->partial('stay-flex-request-card', ['stay' => $stay, 'type' => RequestType::LateCheckout, 'hasPendingRequest' => $hasPending(RequestType::LateCheckout)]) ?>
            </div>
        <?php endif; ?>

        <section class="space-y-3">
            <h2 class="text-base font-semibold">Pedidos ao hotel</h2>
            <?= $view->partial('request-list', ['requestViews' => $requestViews, 'emptyMessage' => 'Nenhum pedido feito para esta reserva.', 'canResolve' => false, 'canCancel' => true]) ?>
        </section>

        <?= $view->partial('message-history', ['messages' => $messages]) ?>
    </div>

    <aside class="space-y-6">
        <section class="space-y-3">
            <h2 class="text-base font-semibold">Valores</h2>
            <?= $view->partial('billing-summary', ['billing' => $stay->billing, 'nights' => $reservation->nights]) ?>
        </section>
        <?php if ($isEditable): ?>
            <?= $view->partial('change-dates-card', ['stay' => $stay, 'old' => $old, 'minDate' => $today->format('Y-m-d')]) ?>
            <?php $cancelWarning = $isRefundable ? 'O valor pago será devolvido.' : 'Fora do prazo de cancelamento grátis: o valor pago não será devolvido.'; ?>
            <p class="text-xs text-muted-foreground"><?= e($cancelWarning) ?></p>
            <form method="post" action="<?= e(url("/hospede/reservas/{$reservation->id}/cancelar")) ?>" data-confirm="Cancelar a reserva <?= e($reservation->code) ?>? <?= e($cancelWarning) ?>">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="btn btn-ghost btn-block text-destructive">Cancelar reserva</button>
            </form>
        <?php endif; ?>
    </aside>
</div>
