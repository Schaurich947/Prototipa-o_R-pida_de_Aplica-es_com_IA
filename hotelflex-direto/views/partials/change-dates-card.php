<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\StayView $stay
 * @var array<string, mixed> $old
 * @var string $minDate
 */
$reservation = $stay->reservation;
?>
<section class="card">
    <header class="card-header">
        <div>
            <h2 class="card-title">Alterar datas</h2>
            <p class="card-description">Mesmo quarto, se estiver livre. A diferença de valor vira cobrança ou crédito.</p>
        </div>
    </header>
    <form method="post" action="<?= e(url("/hospede/reservas/{$reservation->id}/datas")) ?>" class="contents">
        <div class="card-content">
            <?= $view->partial('csrf-field') ?>
            <?= $view->partial('date-range-fields', [
                'idPrefix' => 'change',
                'checkInName' => 'check_in_date',
                'checkOutName' => 'check_out_date',
                'checkInValue' => (string) ($old['check_in_date'] ?? $reservation->checkInDate->format('Y-m-d')),
                'checkOutValue' => (string) ($old['check_out_date'] ?? $reservation->checkOutDate()->format('Y-m-d')),
                'minDate' => $minDate,
            ]) ?>
        </div>
        <footer class="card-footer">
            <button type="submit" class="btn btn-outline btn-block">Salvar novas datas</button>
        </footer>
    </form>
</section>
