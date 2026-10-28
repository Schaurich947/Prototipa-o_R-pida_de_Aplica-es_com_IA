<?php /** @var HotelFlex\View\View $view @var string $subtitle */ ?>
<div class="space-y-2 text-center">
    <div class="flex justify-center"><?= $view->partial('icon-bed') ?></div>
    <h1 class="text-2xl font-semibold tracking-tight">HotelFlex</h1>
    <p class="text-sm text-muted-foreground"><?= e($subtitle) ?></p>
</div>
