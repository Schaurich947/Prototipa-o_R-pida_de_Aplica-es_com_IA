<?php
/**
 * @var Comprova\View\View $view
 * @var Comprova\Domain\User $currentUser
 * @var string $content
 * @var bool|null $liveRefresh
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?= $view->partial('head', ['pageTitle' => $pageTitle ?? null]) ?>
    <script src="<?= e(url('/assets/js/live-refresh.js')) ?>" defer></script>
</head>
<?php /* Forms opt out of auto-refresh, so a reload never wipes what the operator is typing. */ ?>
<body <?= ($liveRefresh ?? true) ? 'data-version-url="' . e(url('/api/versao')) . '"' : '' ?>>
    <div class="app">
        <?= $view->partial('sidebar', ['currentUser' => $currentUser, 'currentPath' => $currentPath ?? '', 'companyName' => $companyName ?? '']) ?>
        <main>
            <div class="topbar">
                <div class="breadcrumb">Workspace <span>/</span> <?= e($pageTitle ?? '') ?></div>
                <span><?= e(date('d/m/Y')) ?></span>
            </div>
            <div class="content">
                <?= $view->partial('flash-messages', ['flashes' => $flashes ?? []]) ?>
                <?= $content ?>
            </div>
        </main>
    </div>
</body>
</html>
