<?php
/**
 * Lets the administrator open the guest screens as any guest.
 *
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Domain\Reservation> $reservations
 * @var int|null $selectedId
 * @var string $action
 */
?>
<form method="get" action="<?= e(url($action)) ?>" class="flex flex-col gap-2" data-auto-submit>
    <label class="label" for="admin-reservation">Visualizar como hóspede</label>
    <div class="flex gap-2">
        <select class="select" id="admin-reservation" name="reserva">
            <?= $view->partial('reservation-options', ['reservations' => $reservations, 'selectedId' => $selectedId]) ?>
        </select>
        <button type="submit" class="btn btn-outline" data-auto-submit-fallback>Ver</button>
    </div>
</form>
