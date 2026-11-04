<?php
/**
 * @var string $eyebrow
 * @var string $title
 * @var string $subtitle
 * @var string|null $actions pre-rendered HTML
 */
?>
<header class="page-heading">
    <div>
        <div class="eyebrow"><?= e($eyebrow) ?></div>
        <h1><?= e($title) ?></h1>
        <p class="subtitle"><?= e($subtitle) ?></p>
    </div>
    <?= $actions ?? '' ?>
</header>
