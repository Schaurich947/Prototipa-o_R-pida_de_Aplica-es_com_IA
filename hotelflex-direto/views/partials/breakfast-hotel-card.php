<?php

use HotelFlex\Domain\BreakfastStatus;
use HotelFlex\Domain\PaymentMethod;

/**
 * One breakfast booking on the reception list, with the actions its state allows.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\BreakfastBooking $booking
 * @var string $customerName
 * @var DateTimeImmutable $date
 */
$actionUrl = url("/hotel/cafe/{$booking->id}");
$hiddenFields = static fn (string $action): string => $view->partial('csrf-field')
    . '<input type="hidden" name="action" value="' . e($action) . '">'
    . '<input type="hidden" name="date" value="' . e($date->format('Y-m-d')) . '">';
?>
<article class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title"><?= e($customerName) ?> · <?= e($booking->people) ?> pessoa(s)</h2>
            <p class="card-description"><?= e($booking->code) ?> · <?= e(formatMoney($booking->totalCents)) ?> · <?= e($booking->isPaid ? 'Pago · ' . ($booking->paidMethod?->label() ?? '') : $booking->paymentOption->label()) ?></p>
        </div>
        <span class="badge <?= in_array($booking->status, [BreakfastStatus::Confirmed, BreakfastStatus::Served], true) ? 'badge-success' : 'badge-warning' ?>"><?= e($booking->status->label()) ?></span>
    </header>
    <div class="card-content gap-2">
        <?php if ($booking->status === BreakfastStatus::UnderReview): ?>
            <a class="btn btn-outline btn-sm w-fit" href="<?= e(url("/cafe/{$booking->id}/arquivo")) ?>">Baixar comprovante</a>
            <form method="post" action="<?= e($actionUrl) ?>"><?= $hiddenFields('approve') ?><button type="submit" class="btn btn-success btn-block">Aprovar pagamento</button></form>
            <form method="post" action="<?= e($actionUrl) ?>" class="flex gap-2">
                <?= $hiddenFields('reject') ?>
                <label class="visually-hidden sr-only" for="breakfast-reason-<?= e($booking->id) ?>">Motivo</label>
                <input class="input" id="breakfast-reason-<?= e($booking->id) ?>" name="reason" placeholder="Motivo da recusa" minlength="3" required>
                <button type="submit" class="btn btn-outline">Recusar</button>
            </form>
        <?php endif; ?>
        <?php if ($booking->canBeReceivedAtDesk()): ?>
            <form method="post" action="<?= e($actionUrl) ?>" class="flex flex-col gap-2 sm:flex-row">
                <?= $hiddenFields('receive') ?>
                <label class="visually-hidden sr-only" for="breakfast-method-<?= e($booking->id) ?>">Forma de recebimento</label>
                <select class="input" id="breakfast-method-<?= e($booking->id) ?>" name="method" required>
                    <option value="">Como foi pago?</option>
                    <?php foreach (PaymentMethod::deskMethods() as $method): ?>
                        <option value="<?= e($method->value) ?>"><?= e($method->label()) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-success shrink-0">Registrar recebimento</button>
            </form>
        <?php endif; ?>
        <?php if ($booking->isPaid && $booking->status === BreakfastStatus::Confirmed): ?>
            <form method="post" action="<?= e($actionUrl) ?>"><?= $hiddenFields('served') ?><button type="submit" class="btn btn-primary btn-block">Marcar como atendido</button></form>
        <?php endif; ?>
    </div>
</article>
