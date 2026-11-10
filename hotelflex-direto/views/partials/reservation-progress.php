<?php

use HotelFlex\Domain\ReservationStatus;

/**
 * Where the reservation is in its life cycle.
 *
 * @var ReservationStatus $status
 * @var bool $hasReceiptUnderReview
 */
$steps = ['Reserva criada', 'Pagamento Pix', 'Confirmada', 'Hospedado', 'Concluída'];
$currentStep = match ($status) {
    ReservationStatus::PendingPayment => 1,
    ReservationStatus::Confirmed => 2,
    ReservationStatus::CheckedIn => 3,
    ReservationStatus::CheckedOut => 5,
    ReservationStatus::Cancelled, ReservationStatus::Expired => -1,
};
?>
<?php if ($currentStep === -1): ?>
    <p class="alert alert-error">
        <?= $status === ReservationStatus::Expired ? 'Esta reserva expirou porque o comprovante não foi enviado no prazo.' : 'Esta reserva foi cancelada.' ?>
    </p>
<?php else: ?>
    <ol class="grid grid-cols-5 gap-1 text-center text-xs" aria-label="Andamento da reserva">
        <?php foreach ($steps as $index => $label): ?>
            <?php $state = $index < $currentStep ? 'done' : ($index === $currentStep ? 'current' : 'next'); ?>
            <li class="space-y-1" <?= $state === 'current' ? 'aria-current="step"' : '' ?>>
                <div class="h-1.5 rounded-full <?= $state === 'next' ? 'bg-muted' : 'bg-success' ?>"></div>
                <span class="<?= $state === 'current' ? 'font-semibold' : 'text-muted-foreground' ?>">
                    <?= e($index === 1 && $hasReceiptUnderReview ? 'Comprovante em análise' : $label) ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
