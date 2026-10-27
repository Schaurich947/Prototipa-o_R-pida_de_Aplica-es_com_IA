<?php

use HotelFlex\Domain\HotelPolicy;
use HotelFlex\Service\RoomFilter;

/**
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Service\RoomView> $roomViews
 * @var list<HotelFlex\Service\RoomView> $allRoomViews
 * @var RoomFilter $activeFilter
 */
$additionalRevenue = array_sum(array_map(static fn ($roomView) => $roomView->billing?->additionalCents ?? 0, $allRoomViews));
$stats = [
    'Vagos antecipados' => (string) count(RoomFilter::Early->apply($allRoomViews)),
    'Em limpeza' => (string) count(RoomFilter::Housekeeping->apply($allRoomViews)),
    'Liberados' => (string) count(RoomFilter::Released->apply($allRoomViews)),
    'Receita adicional' => formatMoney($additionalRevenue),
];
?>
<?= $view->partial('page-header', [
    'title' => 'Painel de Liberação e Horas Adicionais',
    'description' => 'Check-in padrão às ' . formatHour(HotelPolicy::STANDARD_CHECK_IN_HOUR) . ' · check-out padrão às ' . formatHour(HotelPolicy::STANDARD_CHECK_OUT_HOUR),
]) ?>

<section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Resumo dos quartos">
    <?php foreach ($stats as $label => $value): ?>
        <?= $view->partial('stat-card', ['label' => $label, 'value' => $value]) ?>
    <?php endforeach; ?>
</section>

<nav class="tabs" aria-label="Filtrar quartos">
    <?php foreach (RoomFilter::cases() as $filter): ?>
        <?php $isActive = $filter === $activeFilter; ?>
        <a href="/hotel/liberacao<?= $filter === RoomFilter::All ? '' : '?filtro=' . e($filter->value) ?>" class="tab <?= $isActive ? 'tab-active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>><?= e($filter->label()) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($roomViews === []): ?>
    <?= $view->partial('empty-state', ['message' => 'Nenhum quarto neste filtro.']) ?>
<?php else: ?>
    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Quartos">
        <?php foreach ($roomViews as $roomView): ?>
            <?= $view->partial('room-card', ['roomView' => $roomView, 'activeFilter' => $activeFilter->value]) ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
