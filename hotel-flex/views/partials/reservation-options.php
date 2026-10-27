<?php
/**
 * @var list<HotelFlex\Domain\Reservation> $reservations
 * @var int|null $selectedId
 */
?>
<?php foreach ($reservations as $reservation): ?>
    <option value="<?= e($reservation->id) ?>" <?= $reservation->id === $selectedId ? 'selected' : '' ?>>
        <?= e($reservation->code) ?> · <?= e($reservation->guestName) ?>
    </option>
<?php endforeach; ?>
