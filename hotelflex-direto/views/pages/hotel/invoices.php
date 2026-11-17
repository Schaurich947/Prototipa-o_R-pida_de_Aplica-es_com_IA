<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var list<HotelFlex\Domain\Hotel> $hotelOptions
 * @var string $month YYYY-MM
 * @var list<HotelFlex\Fiscal\Domain\Invoice> $invoices
 * @var array{invoiceCount: int, invoicedCents: int, pendingCount: int, rejectedCount: int, lines: list<array{tax: HotelFlex\Fiscal\Domain\TaxType, nature: HotelFlex\Fiscal\Domain\LedgerNature, cents: int}>} $summary
 * @var bool $hasFiscalProfile
 */
$hotelPicker = renderIf($hotelOptions !== [], fn () => $view->partial('select-picker', [
    'action' => '/hotel/notas', 'name' => 'hotel', 'label' => 'Hotel', 'options' => toOptions($hotelOptions, static fn ($option) => $option->name), 'selectedId' => $hotel->id,
]));
?>
<?= $view->partial('page-header', [
    'title' => "Notas fiscais · {$hotel->name}",
    'description' => 'Emissão SIMULADA: nenhuma nota é enviada à prefeitura ou à SEFAZ. Os tributos abaixo são um pré-controle, não a apuração oficial.',
    'actions' => $hotelPicker,
]) ?>

<?php if (!$hasFiscalProfile): ?>
    <p class="alert alert-error">Cadastre os <a class="underline" href="<?= e(url('/hotel/fiscal')) ?>">dados fiscais do hotel</a>: sem eles as notas são rejeitadas.</p>
<?php endif; ?>

<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <form method="get" action="<?= e(url('/hotel/notas')) ?>" class="flex items-end gap-2">
        <div class="space-y-2">
            <label class="label" for="invoice-month">Mês de competência</label>
            <input class="input" type="month" id="invoice-month" name="mes" value="<?= e($month) ?>" required>
        </div>
        <button type="submit" class="btn btn-outline">Ver mês</button>
    </form>
    <form method="post" action="<?= e(url('/hotel/notas/processar')) ?>">
        <?= $view->partial('csrf-field') ?>
        <input type="hidden" name="hotel_id" value="<?= e($hotel->id) ?>">
        <button type="submit" class="btn btn-primary">Emitir notas da fila (<?= e($summary['pendingCount']) ?>)</button>
    </form>
</div>

<div class="grid gap-3 sm:grid-cols-4">
    <?= $view->partial('stat-card', ['label' => 'Notas autorizadas', 'value' => (string) $summary['invoiceCount']]) ?>
    <?= $view->partial('stat-card', ['label' => 'Valor faturado', 'value' => formatMoney($summary['invoicedCents'])]) ?>
    <?= $view->partial('stat-card', ['label' => 'Na fila', 'value' => (string) $summary['pendingCount']]) ?>
    <?= $view->partial('stat-card', ['label' => 'Rejeitadas', 'value' => (string) $summary['rejectedCount']]) ?>
</div>

<?= $view->partial('tax-summary', ['lines' => $summary['lines']]) ?>

<section class="space-y-3">
    <h2 class="text-base font-semibold">Notas do mês (<?= e(count($invoices)) ?>)</h2>
    <?php if ($invoices === []): ?>
        <?= $view->partial('empty-state', ['message' => 'Nenhuma nota neste mês. As notas entram na fila no check-out e quando o café é servido.']) ?>
    <?php else: ?>
        <div class="relative overflow-x-auto rounded-xl ring-1 ring-foreground/10">
            <table class="data-table">
                <thead><tr><th>Nota</th><th>Tomador</th><th>Serviço</th><th class="text-right">Valor</th><th class="text-right">ISS / ICMS</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                        <?= $view->partial('invoice-row', ['invoice' => $invoice, 'month' => $month]) ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
