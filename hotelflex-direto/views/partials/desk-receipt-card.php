<?php

use HotelFlex\Domain\PaymentMethod;

/**
 * Open charge the reception can settle in person (cash, card terminal or Pix at the desk).
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Charge $charge
 * @var HotelFlex\Service\StayView $stay
 */
$selectId = "method-{$charge->id}";
?>
<article class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title"><?= e(formatMoney($charge->amountCents)) ?> · <?= e($charge->description) ?></h2>
            <p class="card-description"><?= e($stay->guestName) ?> · <?= e($stay->reservation->code) ?> · Quarto <?= e($stay->room->number) ?></p>
        </div>
        <span class="badge <?= $charge->payAtDesk ? 'badge-outline' : 'badge-warning' ?>"><?= $charge->payAtDesk ? 'Pagar no hotel' : 'Aguardando Pix' ?></span>
    </header>
    <div class="card-content text-sm">
        <p>Estadia: <?= e(formatMoment($stay->startsAt())) ?> a <?= e(formatMoment($stay->endsAt())) ?></p>
        <?php if ($charge->dueAt !== null): ?>
            <p class="text-muted-foreground">Vence em <?= e(formatMoment($charge->dueAt)) ?></p>
        <?php endif; ?>
    </div>
    <footer class="card-footer">
        <form method="post" action="<?= e(url("/hotel/cobrancas/{$charge->id}/receber")) ?>" class="flex w-full flex-col gap-2 sm:flex-row">
            <?= $view->partial('csrf-field') ?>
            <label class="visually-hidden sr-only" for="<?= e($selectId) ?>">Forma de recebimento</label>
            <select class="input" id="<?= e($selectId) ?>" name="method" required>
                <option value="">Como foi pago?</option>
                <?php foreach (PaymentMethod::deskMethods() as $method): ?>
                    <option value="<?= e($method->value) ?>"><?= e($method->label()) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-success shrink-0">Registrar recebimento</button>
        </form>
    </footer>
</article>
