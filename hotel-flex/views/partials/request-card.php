<?php

use HotelFlex\Domain\RequestType;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Service\RequestView $requestView
 * @var bool $canResolve
 */
$request = $requestView->request;
?>
<article class="card py-3">
    <header class="card-header px-3">
        <div>
            <h3 class="card-title text-sm"><?= e($request->type->label()) ?></h3>
            <p class="card-description">
                <?= e($requestView->reservation->guestName) ?> · Quarto <?= e($requestView->room->number) ?> · <?= e(formatDateTime($request->createdAt)) ?>
            </p>
        </div>
        <?= $view->partial('request-status-badge', ['status' => $request->status]) ?>
    </header>
    <div class="card-content px-3">
        <?php if ($request->type === RequestType::EarlyCheckIn): ?>
            <p class="text-sm text-muted-foreground">Entrada antes do horário padrão, sem custo adicional.</p>
        <?php else: ?>
            <p class="text-sm">
                +<?= e(formatHours($request->extraHours)) ?> até <?= e(formatCheckoutHour($request->extraHours)) ?>
                <span class="font-semibold">· <?= e(formatMoney($requestView->feeCents())) ?></span>
            </p>
        <?php endif; ?>
    </div>
    <?php if ($canResolve && $request->isPending()): ?>
        <footer class="card-footer -mb-3 p-3">
            <form method="post" action="<?= e(url("/hotel/solicitacoes/{$request->id}/aprovar")) ?>">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="btn btn-primary btn-sm">Aprovar</button>
            </form>
            <form method="post" action="<?= e(url("/hotel/solicitacoes/{$request->id}/recusar")) ?>">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="btn btn-ghost btn-sm">Recusar</button>
            </form>
        </footer>
    <?php endif; ?>
</article>
