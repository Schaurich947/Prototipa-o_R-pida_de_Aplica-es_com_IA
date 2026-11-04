<?php
/**
 * @var Comprova\View\View $view
 * @var string $content
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?= $view->partial('head', ['pageTitle' => $pageTitle ?? null]) ?>
</head>
<body>
    <main class="auth-page">
        <div class="auth-card content">
            <?= $view->partial('flash-messages', ['flashes' => $flashes ?? []]) ?>
            <?= $content ?>
        </div>
    </main>
</body>
</html>
