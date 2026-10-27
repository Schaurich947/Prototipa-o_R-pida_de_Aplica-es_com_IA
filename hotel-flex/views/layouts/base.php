<?php
/**
 * @var HotelFlex\View\View $view
 * @var string $content
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?= $view->partial('head', ['pageTitle' => $pageTitle ?? null]) ?>
</head>
<body class="min-h-svh">
    <main class="mx-auto flex min-h-svh max-w-5xl flex-col justify-center gap-6 px-4 py-10">
        <?= $view->partial('flash-messages', ['flashes' => $flashes ?? []]) ?>
        <?= $content ?>
    </main>
</body>
</html>
