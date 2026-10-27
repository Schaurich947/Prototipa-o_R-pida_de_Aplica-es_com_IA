<?php
/**
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Service\RequestView> $pendingViews
 * @var list<HotelFlex\Service\RequestView> $resolvedViews
 */
?>
<?= $view->partial('page-header', [
    'title' => 'Solicitações dos Hóspedes',
    'description' => 'Confira e responda pedidos de entrada antecipada e late check-out.',
]) ?>

<section class="space-y-3">
    <h2 class="text-base font-semibold">Pendentes (<?= e(count($pendingViews)) ?>)</h2>
    <?= $view->partial('request-list', ['requestViews' => $pendingViews, 'emptyMessage' => 'Nenhuma solicitação pendente.', 'canResolve' => true]) ?>
</section>

<section class="space-y-3">
    <h2 class="text-base font-semibold">Histórico (<?= e(count($resolvedViews)) ?>)</h2>
    <?= $view->partial('request-list', ['requestViews' => $resolvedViews, 'emptyMessage' => 'Nenhuma solicitação respondida ainda.', 'canResolve' => false]) ?>
</section>
