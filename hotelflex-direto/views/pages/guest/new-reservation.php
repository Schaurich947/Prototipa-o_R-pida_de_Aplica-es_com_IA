<?php

use HotelFlex\Service\ReservationService;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\User $guest
 * @var list<HotelFlex\Domain\User> $guestOptions
 * @var list<string> $cities
 * @var array{entrada: string, diarias: int, cidade: string} $criteria
 * @var list<HotelFlex\Service\RoomOffer>|null $offers null before searching
 * @var string $minDate
 */
$offersByHotel = [];
foreach ($offers ?? [] as $offer) {
    $offersByHotel[$offer->hotel->id]['hotel'] = $offer->hotel;
    $offersByHotel[$offer->hotel->id]['offers'][] = $offer;
}
?>
<?= $view->partial('page-header', [
    'title' => 'Nova reserva',
    'description' => "Busque quartos livres em todos os hotéis parceiros para {$guest->name}.",
    'actions' => renderIf($guestOptions !== [], fn () => $view->partial('guest-picker', ['guestOptions' => $guestOptions, 'guest' => $guest, 'action' => '/hospede/nova-reserva'])),
]) ?>

<form method="get" action="<?= e(url('/hospede/nova-reserva')) ?>" class="card">
    <div class="card-content">
        <div class="grid gap-3 sm:grid-cols-4 sm:items-end">
            <div class="space-y-2">
                <label class="label" for="search-check-in">Entrada</label>
                <input class="select sm:w-full" type="date" id="search-check-in" name="entrada" min="<?= e($minDate) ?>" value="<?= e($criteria['entrada']) ?>" required>
            </div>
            <div class="space-y-2">
                <label class="label" for="search-nights">Diárias</label>
                <input class="select sm:w-full" type="number" id="search-nights" name="diarias" min="1" max="<?= e(ReservationService::MAX_NIGHTS) ?>" value="<?= e($criteria['diarias']) ?>" required>
            </div>
            <div class="space-y-2">
                <label class="label" for="search-city">Cidade</label>
                <select class="select sm:w-full" id="search-city" name="cidade">
                    <option value="">Todas</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= e($city) ?>" <?= $city === $criteria['cidade'] ? 'selected' : '' ?>><?= e($city) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </div>
</form>

<?php if ($offers !== null && $offersByHotel === []): ?>
    <?= $view->partial('empty-state', ['message' => 'Nenhum quarto livre para essas datas. Tente outras datas ou outra cidade.']) ?>
<?php endif; ?>
<?php foreach ($offersByHotel as $group): ?>
    <section class="space-y-3">
        <h2 class="text-base font-semibold"><?= e($group['hotel']->name) ?> <span class="font-normal text-muted-foreground">· <?= e($group['hotel']->city) ?></span></h2>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($group['offers'] as $offer): ?>
                <?= $view->partial('room-offer-card', ['offer' => $offer, 'checkInDate' => $criteria['entrada']]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
