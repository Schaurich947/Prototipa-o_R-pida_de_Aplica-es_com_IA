<?php

use HotelFlex\Domain\HotelPolicy;

/** @var HotelFlex\Service\RoomView $roomView */
$room = $roomView->room;
$reservation = $roomView->reservation;
$details = [
    'Quarto' => "{$room->number} · {$room->category}",
    'Check-in' => formatDate($reservation->checkInDate) . ' às ' . formatHour(HotelPolicy::STANDARD_CHECK_IN_HOUR),
    'Diárias' => (string) $reservation->nights,
    'Saída prevista' => formatCheckoutHour($reservation->lateCheckoutHours),
    'Status do quarto' => $room->status->label(),
];
?>
<section class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title">Reserva <?= e($reservation->code) ?></h2>
            <p class="card-description"><?= e($reservation->guestName) ?></p>
        </div>
    </header>
    <div class="card-content">
        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
        <?php foreach ($details as $label => $value): ?>
            <div class="space-y-0.5">
                <dt class="text-xs text-muted-foreground"><?= e($label) ?></dt>
                <dd class="font-medium"><?= e($value) ?></dd>
            </div>
        <?php endforeach; ?>
        </dl>
    </div>
</section>
