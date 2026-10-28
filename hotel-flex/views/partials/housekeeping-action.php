<?php

use HotelFlex\Domain\RoomStatus;

/**
 * One contextual action per room, following the housekeeping flow.
 *
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RoomView $roomView
 * @var string $activeFilter
 */
$room = $roomView->room;
$action = match (true) {
    $roomView->isEarlyVacant => ['path' => 'liberar', 'label' => 'Liberar para o Hóspede', 'class' => 'btn-success'],
    $room->status === RoomStatus::Dirty => ['path' => 'iniciar-limpeza', 'label' => 'Iniciar limpeza', 'class' => 'btn-outline'],
    $room->status === RoomStatus::Cleaning => ['path' => 'marcar-limpo', 'label' => 'Marcar como limpo', 'class' => 'btn-outline'],
    default => null,
};
?>
<?php if ($action !== null): ?>
    <form method="post" action="<?= e(url("/hotel/quartos/{$room->id}/{$action['path']}")) ?>" class="w-full">
        <?= $view->partial('csrf-field') ?>
        <input type="hidden" name="filtro" value="<?= e($activeFilter) ?>">
        <button type="submit" class="btn btn-block <?= e($action['class']) ?>"><?= e($action['label']) ?></button>
    </form>
<?php endif; ?>
