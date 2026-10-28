<?php
/**
 * Keeps the active panel filter after a POST.
 *
 * @var HotelFlex\View\View $view
 * @var string $activeFilter
 */
?>
<?= $view->partial('csrf-field') ?>
<input type="hidden" name="filtro" value="<?= e($activeFilter) ?>">
