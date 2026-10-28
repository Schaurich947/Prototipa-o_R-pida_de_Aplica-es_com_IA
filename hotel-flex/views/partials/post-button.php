<?php
/**
 * A single-button POST form (state-changing actions are never links).
 *
 * @var HotelFlex\View\View $view
 * @var string $action
 * @var string $label
 * @var string $buttonClass
 * @var string $activeFilter
 */
?>
<form method="post" action="<?= e(url($action)) ?>" class="w-full">
    <?= $view->partial('hidden-filter', ['activeFilter' => $activeFilter]) ?>
    <button type="submit" class="btn btn-block <?= e($buttonClass) ?>"><?= e($label) ?></button>
</form>
