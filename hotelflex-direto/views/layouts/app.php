<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\User $currentUser
 * @var string $content
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?= $view->partial('head', ['pageTitle' => $pageTitle ?? null]) ?>
    <script src="<?= e(url('/assets/js/live-refresh.js')) ?>" defer></script>
</head>
<body class="min-h-svh" data-live-refresh data-version-url="<?= e(url('/api/versao')) ?>">
    <?= $view->partial('app-header', ['currentUser' => $currentUser, 'currentPath' => $currentPath ?? '']) ?>
    <main class="mx-auto max-w-6xl space-y-6 px-4 py-6">
        <?= $view->partial('flash-messages', ['flashes' => $flashes ?? []]) ?>
        <?= $content ?>
    </main>
</body>
</html>
