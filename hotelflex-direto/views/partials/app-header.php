<?php

use HotelFlex\Http\Navigation;

/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\User $currentUser
 * @var string $currentPath
 */
$navigationGroups = Navigation::getItemsByArea($currentUser->role);
$showAreaLabels = count($navigationGroups) > 1;
?>
<header class="border-b">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="<?= e(url(Navigation::getHomePath($currentUser->role))) ?>" class="flex items-center gap-2 font-semibold">
                <?= $view->partial('icon-bed') ?>
                HotelFlex
            </a>
            <div class="flex items-center gap-2">
                <span class="hidden text-sm text-muted-foreground sm:inline"><?= e($currentUser->name) ?></span>
                <span class="badge badge-outline"><?= e($currentUser->role->label()) ?></span>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= $view->partial('csrf-field') ?>
                    <button type="submit" class="btn btn-ghost btn-sm">Sair</button>
                </form>
            </div>
        </div>
        <nav aria-label="Navegação principal" class="flex flex-wrap gap-x-4 gap-y-2">
            <?php foreach ($navigationGroups as $area => $items): ?>
                <div class="flex flex-wrap items-center gap-1">
                    <?php if ($showAreaLabels): ?>
                        <span class="px-1 text-xs font-medium text-muted-foreground"><?= e($area) ?>:</span>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <?php $isActive = Navigation::isActive($item['path'], $currentPath); ?>
                        <a href="<?= e(url($item['path'])) ?>" class="nav-link <?= $isActive ? 'nav-link-active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
