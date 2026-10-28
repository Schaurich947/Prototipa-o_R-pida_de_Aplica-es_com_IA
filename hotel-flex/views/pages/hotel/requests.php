<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\Hotel $hotel
 * @var list<HotelFlex\Domain\Hotel> $hotelOptions
 * @var list<HotelFlex\Service\RequestView> $pendingViews
 * @var list<HotelFlex\Service\RequestView> $resolvedViews
 */
$hotelPicker = renderIf($hotelOptions !== [], fn () => $view->partial('select-picker', [
    'action' => '/hotel/solicitacoes', 'name' => 'hotel', 'label' => 'Hotel',
    'options' => toOptions($hotelOptions, static fn ($option) => $option->name),
    'selectedId' => $hotel->id,
]));
?>
<?= $view->partial('page-header', [
    'title' => "Solicitações · {$hotel->name}",
    'description' => 'Pedidos de entrada antecipada e saída tardia feitos pelos hóspedes.',
    'actions' => $hotelPicker,
]) ?>

<section class="space-y-3">
    <h2 class="text-base font-semibold">Pendentes (<?= e(count($pendingViews)) ?>)</h2>
    <?= $view->partial('request-list', ['requestViews' => $pendingViews, 'emptyMessage' => 'Nenhuma solicitação pendente.', 'canResolve' => true]) ?>
</section>

<section class="space-y-3">
    <h2 class="text-base font-semibold">Histórico (<?= e(count($resolvedViews)) ?>)</h2>
    <?= $view->partial('request-list', ['requestViews' => $resolvedViews, 'emptyMessage' => 'Nenhuma solicitação respondida ainda.', 'canResolve' => false]) ?>
</section>
