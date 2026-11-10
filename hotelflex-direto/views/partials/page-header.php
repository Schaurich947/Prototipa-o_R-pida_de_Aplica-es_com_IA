<?php
/**
 * @var string $title
 * @var string $description
 * @var string|null $actions pre-rendered HTML from another partial
 */
?>
<header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight"><?= e($title) ?></h1>
        <p class="text-sm text-muted-foreground"><?= e($description) ?></p>
    </div>
    <?= $actions ?? '' ?>
</header>
