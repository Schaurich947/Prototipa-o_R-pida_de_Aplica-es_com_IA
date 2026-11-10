<?php

use HotelFlex\Domain\PaymentPlan;

/**
 * Last step before booking: the guest checks everything and chooses how to pay.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 * @var HotelFlex\Domain\StayDates $dates
 * @var HotelFlex\Domain\User $guest
 * @var DateTimeImmutable $freeCancellationUntil
 * @var DateTimeImmutable $paymentDeadline
 * @var string $backUrl
 */
$details = [
    'Hotel' => "{$stay->hotel->name} · {$stay->hotel->city}",
    'Quarto' => "{$stay->room->category} · quarto {$stay->room->number}",
    'Hóspede' => $guest->name,
    'Entrada' => formatMoment($stay->startsAt()),
    'Saída' => formatMoment($stay->endsAt()),
    'Diárias' => (string) $dates->nights,
];
?>
<a class="text-sm text-muted-foreground hover:text-foreground" href="<?= e($backUrl) ?>">← Voltar à busca</a>
<?= $view->partial('page-header', ['title' => 'Revisar reserva', 'description' => 'Confira os dados e escolha a forma de pagamento. Nada é cobrado até você confirmar.']) ?>

<form method="post" action="<?= e(url('/hospede/nova-reserva')) ?>" class="grid items-start gap-6 lg:grid-cols-[2fr_1fr]">
    <?= $view->partial('csrf-field') ?>
    <input type="hidden" name="room_id" value="<?= e($stay->room->id) ?>">
    <input type="hidden" name="check_in_date" value="<?= e($dates->checkInDate->format('Y-m-d')) ?>">
    <input type="hidden" name="check_out_date" value="<?= e($dates->checkOutDate->format('Y-m-d')) ?>">
    <div class="space-y-6">
        <section class="card">
            <div class="card-content">
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <?php foreach ($details as $label => $value): ?>
                        <div class="space-y-0.5"><dt class="text-xs text-muted-foreground"><?= e($label) ?></dt><dd class="font-medium"><?= e($value) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
                <p class="text-sm text-muted-foreground">Precisa entrar mais cedo ou sair mais tarde? Depois de reservar, peça na página da reserva (cobrado por hora).</p>
            </div>
        </section>
        <fieldset class="card">
            <legend class="card-header card-title">Forma de pagamento (direto ao hotel)</legend>
            <div class="card-content">
                <?php foreach (PaymentPlan::cases() as $plan): ?>
                    <?= $view->partial('payment-plan-option', [
                        'plan' => $plan,
                        'baseCents' => $stay->billing->baseCents,
                        'firstDueAt' => $paymentDeadline,
                        'checkInAt' => $stay->startsAt(),
                    ]) ?>
                <?php endforeach; ?>
                <p class="text-xs text-muted-foreground">Chave Pix do hotel: <span class="font-mono"><?= e($stay->hotel->pixKey) ?></span>. Sem o primeiro comprovante no prazo, a reserva expira.</p>
            </div>
        </fieldset>
    </div>
    <aside class="space-y-4">
        <?= $view->partial('billing-summary', ['billing' => $stay->billing, 'nights' => $dates->nights]) ?>
        <p class="text-sm text-muted-foreground">Desconto nas diárias conforme a forma de pagamento: até <?= e(PaymentPlan::Full->discountPercent()) ?>% pagando tudo na reserva.</p>
        <p class="text-sm">Cancelamento grátis até <strong><?= e(formatMoment($freeCancellationUntil)) ?></strong>. Depois disso, o valor pago não é devolvido.</p>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="accept_policy" value="1" class="mt-0.5 accent-primary" required>
            <span>Li e aceito a política de cancelamento.</span>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Confirmar reserva</button>
    </aside>
</form>
