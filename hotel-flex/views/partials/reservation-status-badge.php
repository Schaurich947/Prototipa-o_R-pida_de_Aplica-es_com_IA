<?php

use HotelFlex\Domain\ReservationStatus;

/** @var ReservationStatus $status */
$variantClass = match ($status) {
    ReservationStatus::Confirmed => 'badge-outline',
    ReservationStatus::CheckedIn => 'badge-success',
    ReservationStatus::CheckedOut => 'badge-secondary',
    ReservationStatus::Cancelled => 'badge-destructive',
};
?>
<span class="badge <?= $variantClass ?>"><?= e($status->label()) ?></span>
