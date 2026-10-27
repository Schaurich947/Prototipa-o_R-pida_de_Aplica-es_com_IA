<?php
/**
 * Hidden fields shared by every guest request form.
 *
 * @var HotelFlex\View\View $view
 * @var string $type
 * @var int $reservationId
 * @var bool $isAdmin
 */
?>
<?= $view->partial('csrf-field') ?>
<input type="hidden" name="type" value="<?= e($type) ?>">
<?php if ($isAdmin): ?>
    <input type="hidden" name="reservation_id" value="<?= e($reservationId) ?>">
<?php endif; ?>
