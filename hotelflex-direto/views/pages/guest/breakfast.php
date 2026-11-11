<?php

use HotelFlex\Domain\BreakfastPaymentOption;
use HotelFlex\Service\BreakfastService;

/**
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Domain\Hotel> $hotels hotels that sell breakfast
 * @var HotelFlex\Domain\Hotel|null $selectedHotel
 * @var DateTimeImmutable|null $selectedDate
 * @var int $people
 * @var int|null $seatsLeft
 * @var list<HotelFlex\Domain\BreakfastBooking> $myBookings
 * @var array<int, HotelFlex\Domain\Hotel> $hotelsById
 * @var string $minDate
 */
?>
<?= $view->partial('page-header', [
    'title' => 'Café da manhã',
    'description' => 'Não precisa estar hospedado: reserve o café da manhã dos hotéis parceiros. Pagando via Pix antecipado, ganha ' . BreakfastPaymentOption::Pix->discountPercent() . '% de desconto.',
]) ?>

<form method="get" action="<?= e(url('/cafe')) ?>" class="card">
    <div class="card-content">
        <div class="grid gap-3 sm:grid-cols-4 sm:items-end">
            <div class="space-y-2 sm:col-span-2">
                <label class="label" for="breakfast-hotel">Hotel</label>
                <select class="input" id="breakfast-hotel" name="hotel" required>
                    <option value="">Escolha o hotel</option>
                    <?php foreach ($hotels as $hotel): ?>
                        <option value="<?= e($hotel->id) ?>" <?= $hotel->id === $selectedHotel?->id ? 'selected' : '' ?>>
                            <?= e($hotel->name) ?> · <?= e($hotel->city) ?> · <?= e(formatMoney($hotel->breakfastPriceCents)) ?>/pessoa
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-2">
                <label class="label" for="breakfast-date">Data</label>
                <input class="input" type="date" id="breakfast-date" name="data" min="<?= e($minDate) ?>" value="<?= e($selectedDate?->format('Y-m-d') ?? '') ?>" required>
            </div>
            <div class="space-y-2">
                <label class="label" for="breakfast-people">Pessoas</label>
                <input class="input" type="number" id="breakfast-people" name="pessoas" min="1" max="<?= e(BreakfastService::MAX_PEOPLE) ?>" value="<?= e($people) ?>" required>
            </div>
        </div>
    </div>
    <footer class="card-footer justify-end"><button type="submit" class="btn btn-primary">Ver disponibilidade</button></footer>
</form>

<?= $view->partial('breakfast-walk-in-notice', ['hotels' => $selectedHotel !== null ? [$selectedHotel] : $hotels]) ?>

<?php if ($selectedHotel !== null && $selectedDate !== null && $seatsLeft !== null): ?>
    <?= $view->partial('breakfast-offer', ['hotel' => $selectedHotel, 'date' => $selectedDate, 'people' => $people, 'seatsLeft' => $seatsLeft]) ?>
<?php endif; ?>

<section class="space-y-3">
    <h2 class="text-base font-semibold">Meus cafés da manhã</h2>
    <?php if ($myBookings === []): ?>
        <?= $view->partial('empty-state', ['message' => 'Você ainda não reservou café da manhã.']) ?>
    <?php else: ?>
        <ul class="grid gap-3 md:grid-cols-2">
            <?php foreach ($myBookings as $booking): ?>
                <li class="card py-3">
                    <div class="card-content flex-row items-center justify-between px-3">
                        <div>
                            <p class="font-medium"><?= e($hotelsById[$booking->hotelId]->name ?? '') ?> · <?= e($booking->date->format('d/m/Y')) ?></p>
                            <p class="text-sm text-muted-foreground"><?= e($booking->code) ?> · <?= e($booking->people) ?> pessoa(s) · <?= e(formatMoney($booking->totalCents)) ?> · <?= e($booking->status->label()) ?></p>
                        </div>
                        <a class="btn btn-outline btn-sm" href="<?= e(url("/cafe/{$booking->id}")) ?>">Detalhes</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
