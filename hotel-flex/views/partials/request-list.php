<?php
/**
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Service\RequestView> $requestViews
 * @var string $emptyMessage
 * @var bool $canResolve
 */
?>
<?php if ($requestViews === []): ?>
    <?= $view->partial('empty-state', ['message' => $emptyMessage]) ?>
<?php else: ?>
    <ul class="grid gap-3 md:grid-cols-2">
        <?php foreach ($requestViews as $requestView): ?>
            <li><?= $view->partial('request-card', ['requestView' => $requestView, 'canResolve' => $canResolve]) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
