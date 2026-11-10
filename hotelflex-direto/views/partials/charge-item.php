<?php

use HotelFlex\Domain\ChargeStatus;
use HotelFlex\Domain\Role;

/**
 * One charge with what the guest (or the hotel) can do with it.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Charge $charge
 * @var HotelFlex\Service\StayView $stay
 * @var HotelFlex\Domain\User $currentUser
 */
$badgeClass = match ($charge->status) {
    ChargeStatus::Paid, ChargeStatus::Refunded => 'badge-success',
    ChargeStatus::Pending, ChargeStatus::UnderReview, ChargeStatus::RefundPending => 'badge-warning',
    default => 'badge-outline',
};
$canUpload = $charge->acceptsReceipt() && $stay->reservation->status->isActive();
?>
<li class="space-y-3 rounded-lg border p-3">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <p class="font-medium"><?= e($charge->description) ?></p>
            <p class="text-sm text-muted-foreground"><?= e($charge->kind->label()) ?> · <?= e(formatMoney($charge->amountCents)) ?></p>
        </div>
        <span class="badge <?= $badgeClass ?>"><?= e($charge->status->label()) ?><?= $charge->paidMethod !== null ? ' · ' . e($charge->paidMethod->label()) : '' ?></span>
    </div>
    <?php if ($charge->rejectionReason !== null && $charge->status === ChargeStatus::Pending): ?>
        <p class="alert alert-error">Comprovante anterior recusado: <?= e($charge->rejectionReason) ?></p>
    <?php endif; ?>
    <?php if ($charge->payAtDesk && $charge->status === ChargeStatus::Pending): ?>
        <p class="rounded-lg bg-muted/50 p-3 text-sm">Pague no hotel até o check-in: <strong>dinheiro, cartão ou Pix no balcão</strong>. A recepção registra o recebimento e você recebe a confirmação.</p>
    <?php endif; ?>
    <?php if ($canUpload): ?>
        <div class="rounded-lg bg-muted/50 p-3 text-sm">
            <p>Pague <strong><?= e(formatMoney($charge->amountCents)) ?></strong> via Pix para a chave:</p>
            <p class="my-1 font-mono text-base font-semibold break-all"><?= e($stay->hotel->pixKey) ?></p>
            <?php if ($charge->dueAt !== null): ?>
                <p class="text-muted-foreground">Envie o comprovante até <?= e(formatMoment($charge->dueAt)) ?>.</p>
            <?php endif; ?>
        </div>
        <form method="post" action="<?= e(url("/hospede/cobrancas/{$charge->id}/comprovante")) ?>" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row">
            <?= $view->partial('csrf-field') ?>
            <input type="hidden" name="reservation_id" value="<?= e($stay->reservation->id) ?>">
            <label class="visually-hidden sr-only" for="receipt-<?= e($charge->id) ?>">Comprovante Pix</label>
            <input class="input py-1" type="file" id="receipt-<?= e($charge->id) ?>" name="receipt" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg" required>
            <button type="submit" class="btn btn-primary">Enviar comprovante</button>
        </form>
    <?php endif; ?>
    <?php if ($charge->receiptPath !== null): ?>
        <a class="text-sm underline" href="<?= e(url("/cobrancas/{$charge->id}/arquivo")) ?>">Ver comprovante enviado</a>
    <?php endif; ?>
    <?php if ($charge->status === ChargeStatus::RefundPending && $currentUser->role === Role::Admin): ?>
        <form method="post" action="<?= e(url("/hotel/cobrancas/{$charge->id}/devolvido")) ?>">
            <?= $view->partial('csrf-field') ?>
            <input type="hidden" name="reservation_id" value="<?= e($stay->reservation->id) ?>">
            <button type="submit" class="btn btn-outline btn-sm">Marcar como devolvido</button>
        </form>
    <?php endif; ?>
</li>
