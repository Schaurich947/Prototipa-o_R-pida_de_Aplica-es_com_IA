<?php

use HotelFlex\Domain\RequestStatus;

/** @var RequestStatus $status */
$variantClass = match ($status) {
    RequestStatus::Pending => 'badge-outline',
    RequestStatus::Approved => 'badge-secondary',
    RequestStatus::Rejected => 'badge-destructive',
};
?>
<span class="badge <?= $variantClass ?>"><?= e($status->label()) ?></span>
