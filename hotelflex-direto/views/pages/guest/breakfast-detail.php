<?php

use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Domain\BreakfastStatus;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\BreakfastBooking $booking
 * @var HotelFlex\Domain\Hotel $hotel
 * @var bool $canCancel
 */
$details = [
    'Hotel' => "{$hotel->name} · {$hotel->city}",
    'Data' => $booking->date->format('d/m/Y') . ' · ' . formatHour($hotel->breakfastStartHour) . ' às ' . formatHour($hotel->breakfastEndHour),
    'Pessoas' => (string) $booking->people,
    'Valor' => formatMoney($booking->totalCents) . ($booking->discountPercent > 0 ? " ({$booking->discountPercent}% de desconto)" : ''),
    'Pagamento' => $booking->isPaid ? 'Pago · ' . ($booking->paidMethod?->label() ?? '') : $booking->paymentOption->label(),
];
?>
<a class="text-sm text-muted-foreground hover:text-foreground" href="<?= e(url('/cafe')) ?>">← Café da manhã</a>
<?= $view->partial('page-header', [
    'title' => "Café da manhã {$booking->code}",
    'description' => $booking->status->label(),
    'actions' => '<span class="badge ' . ($booking->status === BreakfastStatus::Confirmed || $booking->status === BreakfastStatus::Served ? 'badge-success' : 'badge-warning') . '">' . e($booking->status->label()) . '</span>',
]) ?>

<section class="card max-w-2xl">
    <div class="card-content">
        <dl class="grid grid-cols-2 gap-4">
            <?php foreach ($details as $label => $value): ?>
                <div><dt class="text-xs text-muted-foreground"><?= e($label) ?></dt><dd class="font-medium"><?= e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <?php if ($booking->rejectionReason !== null && $booking->status === BreakfastStatus::PendingPayment): ?>
            <p class="alert alert-error">Comprovante anterior recusado: <?= e($booking->rejectionReason) ?></p>
        <?php endif; ?>
        <?php if ($booking->acceptsReceipt()): ?>
            <div class="rounded-lg bg-muted/50 p-3 text-sm">
                <p>Pague <strong><?= e(formatMoney($booking->totalCents)) ?></strong> via Pix para a chave:</p>
                <p class="my-1 font-mono text-base font-semibold break-all"><?= e($hotel->pixKey) ?></p>
                <?php if ($booking->dueAt !== null): ?>
                    <p class="text-muted-foreground">Envie o comprovante até <?= e(formatMoment($booking->dueAt)) ?>, senão os lugares são liberados.</p>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= e(url("/cafe/{$booking->id}/comprovante")) ?>" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row">
                <?= $view->partial('csrf-field') ?>
                <label class="visually-hidden sr-only" for="breakfast-receipt">Comprovante Pix</label>
                <input class="input py-1" type="file" id="breakfast-receipt" name="receipt" accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg" required>
                <button type="submit" class="btn btn-primary">Enviar comprovante</button>
            </form>
        <?php elseif ($booking->paymentOption === BreakfastPaymentOption::Desk && !$booking->isPaid && $booking->status === BreakfastStatus::Confirmed): ?>
            <p class="rounded-lg bg-muted/50 p-3 text-sm">Pague no hotel ao chegar: dinheiro, cartão ou Pix no balcão.</p>
        <?php endif; ?>
        <?php if ($booking->receiptPath !== null): ?>
            <a class="text-sm underline" href="<?= e(url("/cafe/{$booking->id}/arquivo")) ?>">Ver comprovante enviado</a>
        <?php endif; ?>
    </div>
    <?php if ($canCancel): ?>
        <footer class="card-footer">
            <form method="post" action="<?= e(url("/cafe/{$booking->id}/cancelar")) ?>" data-confirm="Cancelar o café da manhã <?= e($booking->code) ?>?">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="btn btn-ghost text-destructive">Cancelar café da manhã</button>
            </form>
        </footer>
    <?php endif; ?>
</section>
