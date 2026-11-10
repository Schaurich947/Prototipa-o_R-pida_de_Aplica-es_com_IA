<?php

use HotelFlex\Service\RoomPanelFilter;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var list<HotelFlex\Domain\Hotel> $hotelOptions
 * @var list<HotelFlex\Service\RoomCardView> $cards
 * @var list<HotelFlex\Service\RoomCardView> $allCards
 * @var RoomPanelFilter $activeFilter
 * @var DateTimeImmutable $today
 */
$stats = [
    'Chegadas hoje' => (string) count(RoomPanelFilter::Arrivals->apply($allCards)),
    'Vagos antecipados' => (string) count(RoomPanelFilter::Early->apply($allCards)),
    'Em limpeza' => (string) count(RoomPanelFilter::Housekeeping->apply($allCards)),
    'Receita adicional' => formatMoney(array_sum(array_map(static fn ($card) => $card->additionalRevenueCents(), $allCards))),
];
$hotelPicker = renderIf($hotelOptions !== [], fn () => $view->partial('select-picker', [
    'action' => '/hotel/liberacao', 'name' => 'hotel', 'label' => 'Hotel',
    'options' => toOptions($hotelOptions, static fn ($option) => $option->name),
    'selectedId' => $hotel->id,
]));
?>
<?= $view->partial('page-header', [
    'title' => "Painel do dia · {$hotel->name}",
    'description' => formatDate($today) . ' · entrada padrão ' . formatHour($hotel->checkInHour) . ' · saída padrão ' . formatHour($hotel->checkOutHour),
    'actions' => $hotelPicker,
]) ?>

<section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Resumo do dia">
    <?php foreach ($stats as $label => $value): ?>
        <?= $view->partial('stat-card', ['label' => $label, 'value' => $value]) ?>
    <?php endforeach; ?>
</section>

<nav class="tabs flex-wrap" aria-label="Filtrar quartos">
    <?php foreach (RoomPanelFilter::cases() as $filter): ?>
        <?php $isActive = $filter === $activeFilter; ?>
        <a href="<?= e(url('/hotel/liberacao' . ($filter === RoomPanelFilter::All ? '' : '?filtro=' . $filter->value))) ?>" class="tab <?= $isActive ? 'tab-active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>><?= e($filter->label()) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($cards === []): ?>
    <?= $view->partial('empty-state', ['message' => 'Nenhum quarto neste filtro.']) ?>
<?php else: ?>
    <section class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Quartos">
        <?php foreach ($cards as $card): ?>
            <?= $view->partial('room-card', ['card' => $card, 'activeFilter' => $activeFilter->value]) ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
