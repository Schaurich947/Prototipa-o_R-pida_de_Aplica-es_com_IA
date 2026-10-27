<?php

use HotelFlex\Domain\RoomStatus;

/**
 * @var HotelFlex\View\View $view
 * @var RoomStatus $status
 * @var bool $isEarlyVacant
 */
$variantClass = match ($status) {
    RoomStatus::Released => 'badge-default',
    RoomStatus::Occupied, RoomStatus::Clean => 'badge-secondary',
    RoomStatus::Dirty, RoomStatus::Cleaning => 'badge-outline',
};
?>
<?php if ($isEarlyVacant): ?>
    <?= $view->partial('early-vacant-badge') ?>
<?php else: ?>
    <span class="badge <?= $variantClass ?>"><?= e($status->label()) ?></span>
<?php endif; ?>
