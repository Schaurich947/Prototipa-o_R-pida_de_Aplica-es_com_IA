<?php

use HotelFlex\Service\OfferSort;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\User $guest
 * @var list<HotelFlex\Domain\User> $guestOptions
 * @var list<string> $cities
 * @var list<string> $categories
 * @var array<string, mixed> $query
 * @var HotelFlex\Domain\StayDates|null $dates
 * @var string|null $dateError
 * @var HotelFlex\Service\OfferFilter $filter
 * @var list<HotelFlex\Service\RoomOffer>|null $offers null before searching
 * @var string $minDate
 */
$queryValue = static fn (string $key): string => is_string($query[$key] ?? null) ? $query[$key] : '';
?>
<?= $view->partial('page-header', [
    'title' => 'Nova reserva',
    'description' => "Escolha as datas e compare os quartos livres de todos os hotéis parceiros para {$guest->name}.",
    'actions' => renderIf($guestOptions !== [], fn () => $view->partial('guest-picker', ['guestOptions' => $guestOptions, 'guest' => $guest, 'action' => '/hospede/nova-reserva'])),
]) ?>

<form method="get" action="<?= e(url('/hospede/nova-reserva')) ?>" class="card">
    <div class="card-content">
        <?= $view->partial('date-range-fields', [
            'idPrefix' => 'search',
            'checkInName' => 'entrada',
            'checkOutName' => 'saida',
            'checkInValue' => $queryValue('entrada'),
            'checkOutValue' => $queryValue('saida'),
            'minDate' => $minDate,
        ]) ?>
        <div class="grid gap-3 sm:grid-cols-4 sm:items-end">
            <div class="space-y-2">
                <label class="label" for="search-city">Cidade</label>
                <select class="input" id="search-city" name="cidade">
                    <option value="">Todas</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= e($city) ?>" <?= $city === $queryValue('cidade') ? 'selected' : '' ?>><?= e($city) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-2">
                <label class="label" for="search-category">Categoria</label>
                <select class="input" id="search-category" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category) ?>" <?= $category === $filter->category ? 'selected' : '' ?>><?= e($category) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="space-y-2">
                <label class="label" for="search-max-price">Diária até (R$)</label>
                <input class="input" type="number" min="1" step="1" inputmode="numeric" id="search-max-price" name="preco_max" placeholder="Sem limite" value="<?= e($queryValue('preco_max')) ?>">
            </div>
            <div class="space-y-2">
                <label class="label" for="search-sort">Ordenar por</label>
                <select class="input" id="search-sort" name="ordem">
                    <?php foreach (OfferSort::cases() as $sort): ?>
                        <option value="<?= e($sort->value) ?>" <?= $sort === $filter->sort ? 'selected' : '' ?>><?= e($sort->label()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <footer class="card-footer justify-end">
        <button type="submit" class="btn btn-primary">Buscar quartos</button>
    </footer>
</form>

<?php if ($dateError !== null): ?>
    <p class="alert alert-error"><?= e($dateError) ?></p>
<?php elseif ($offers === []): ?>
    <?= $view->partial('empty-state', ['message' => 'Nenhum quarto livre com esses filtros. Tente outras datas, outra cidade ou um valor maior.']) ?>
<?php elseif ($offers !== null && $dates !== null): ?>
    <section class="space-y-3" aria-label="Quartos disponíveis">
        <p class="text-sm text-muted-foreground"><?= e(count($offers)) ?> quarto(s) livre(s) · <?= e($dates->nights) ?> diária(s) · <?= e($filter->sort->label()) ?></p>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($offers as $offer): ?>
                <?= $view->partial('room-offer-card', ['offer' => $offer, 'dates' => $dates]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
