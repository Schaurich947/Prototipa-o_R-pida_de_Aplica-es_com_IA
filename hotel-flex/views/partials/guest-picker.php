<?php
/**
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Domain\User> $guestOptions
 * @var HotelFlex\Domain\User $guest
 * @var string $action
 */
?>
<?= $view->partial('select-picker', [
    'action' => $action,
    'name' => 'hospede',
    'label' => 'Ver como hóspede',
    'options' => toOptions($guestOptions, static fn ($option) => "{$option->name} · {$option->email}"),
    'selectedId' => $guest->id,
]) ?>
