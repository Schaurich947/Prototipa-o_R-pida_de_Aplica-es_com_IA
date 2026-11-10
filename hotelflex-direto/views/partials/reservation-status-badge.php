<?php

use HotelFlex\Domain\ReservationStatus;

/** @var ReservationStatus $status */
$variantClass = match ($status) {
    ReservationStatus::PendingPayment => 'badge-warning',
    ReservationStatus::Confirmed => 'badge-outline',
    ReservationStatus::CheckedIn => 'badge-success',
    ReservationStatus::CheckedOut => 'badge-secondary',
    ReservationStatus::Cancelled, ReservationStatus::Expired => 'badge-destructive',
};
?>
<span class="badge <?= $variantClass ?>"><?= e($status->label()) ?></span>
