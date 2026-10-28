<?php

use HotelFlex\Service\ReservationService;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 * @var array<string, mixed> $old
 * @var string $minDate
 */
$reservation = $stay->reservation;
$checkInValue = (string) ($old['check_in_date'] ?? $reservation->checkInDate->format('Y-m-d'));
$nightsValue = (int) ($old['nights'] ?? $reservation->nights);
?>
<section class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title">Alterar datas</h2>
            <p class="card-description">Mesmo quarto, se estiver livre. Horários flexíveis já combinados são zerados.</p>
        </div>
    </header>
    <form method="post" action="<?= e(url("/hospede/reservas/{$reservation->id}/datas")) ?>" class="contents">
        <div class="card-content">
            <?= $view->partial('csrf-field') ?>
            <div class="grid gap-3 sm:grid-cols-2">
            <div class="space-y-2">
                <label class="label" for="change-check-in">Entrada</label>
                <input class="select sm:w-full" type="date" id="change-check-in" name="check_in_date" min="<?= e($minDate) ?>" value="<?= e($checkInValue) ?>" required>
            </div>
            <div class="space-y-2">
                <label class="label" for="change-nights">Diárias</label>
                <input class="select sm:w-full" type="number" id="change-nights" name="nights" min="1" max="<?= e(ReservationService::MAX_NIGHTS) ?>" value="<?= e($nightsValue) ?>" required>
            </div>
            </div>
        </div>
        <footer class="card-footer">
            <button type="submit" class="btn btn-outline btn-block">Salvar novas datas</button>
        </footer>
    </form>
</section>
