<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var list<HotelFlex\Domain\Hotel> $hotelOptions
 * @var list<array{charge: HotelFlex\Domain\Charge, stay: HotelFlex\Service\StayView}> $items
 * @var list<array{charge: HotelFlex\Domain\Charge, stay: HotelFlex\Service\StayView}> $openItems
 */
$hotelPicker = renderIf($hotelOptions !== [], fn () => $view->partial('select-picker', [
    'action' => '/hotel/pagamentos', 'name' => 'hotel', 'label' => 'Hotel',
    'options' => toOptions($hotelOptions, static fn ($option) => $option->name),
    'selectedId' => $hotel->id,
]));
?>
<?= $view->partial('page-header', [
    'title' => "Pagamentos · {$hotel->name}",
    'description' => "Comprovantes Pix enviados pelos hóspedes para a chave {$hotel->pixKey}. Confira no extrato antes de aprovar.",
    'actions' => $hotelPicker,
]) ?>

<h2 class="text-base font-semibold">Comprovantes Pix para conferir (<?= e(count($items)) ?>)</h2>
<?php if ($items === []): ?>
    <?= $view->partial('empty-state', ['message' => 'Nenhum comprovante aguardando conferência.']) ?>
<?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($items as ['charge' => $charge, 'stay' => $stay]): ?>
            <article class="card">
                <header class="card-header">
                    <div>
                        <h2 class="card-title"><?= e(formatMoney($charge->amountCents)) ?> · <?= e($charge->description) ?></h2>
                        <p class="card-description"><?= e($stay->guestName) ?> · <?= e($stay->reservation->code) ?> · Quarto <?= e($stay->room->number) ?></p>
                    </div>
                    <span class="badge badge-warning">Em análise</span>
                </header>
                <div class="card-content">
                    <p class="text-sm">Estadia: <?= e(formatMoment($stay->startsAt())) ?> a <?= e(formatMoment($stay->endsAt())) ?></p>
                    <div class="flex flex-wrap gap-2">
                        <a class="btn btn-outline btn-sm" href="<?= e(url("/cobrancas/{$charge->id}/arquivo")) ?>">Baixar comprovante</a>
                        <?= $view->partial('whatsapp-contact-button', ['stay' => $stay, 'toGuest' => true]) ?>
                    </div>
                </div>
                <footer class="card-footer flex-col items-stretch">
                    <form method="post" action="<?= e(url("/hotel/cobrancas/{$charge->id}/aprovar")) ?>">
                        <?= $view->partial('csrf-field') ?>
                        <button type="submit" class="btn btn-success btn-block">Aprovar pagamento</button>
                    </form>
                    <form method="post" action="<?= e(url("/hotel/cobrancas/{$charge->id}/recusar")) ?>" class="flex gap-2">
                        <?= $view->partial('csrf-field') ?>
                        <label class="visually-hidden sr-only" for="reason-<?= e($charge->id) ?>">Motivo da recusa</label>
                        <input class="input" id="reason-<?= e($charge->id) ?>" name="reason" placeholder="Motivo da recusa (ex.: valor diferente)" minlength="3" maxlength="255" required>
                        <button type="submit" class="btn btn-outline">Recusar</button>
                    </form>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="space-y-3">
    <h2 class="text-base font-semibold">Em aberto · receber no balcão (<?= e(count($openItems)) ?>)</h2>
    <p class="text-sm text-muted-foreground">Dinheiro, cartão na maquininha do hotel ou Pix no balcão. O sistema não guarda dados de cartão.</p>
    <?php if ($openItems === []): ?>
        <?= $view->partial('empty-state', ['message' => 'Nenhum valor em aberto.']) ?>
    <?php else: ?>
        <div class="grid gap-4 md:grid-cols-2">
            <?php foreach ($openItems as ['charge' => $charge, 'stay' => $stay]): ?>
                <?= $view->partial('desk-receipt-card', ['charge' => $charge, 'stay' => $stay]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
