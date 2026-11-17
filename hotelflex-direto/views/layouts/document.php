<?php
/**
 * Printable page (invoice): no menu, white background when printed.
 *
 * @var HotelFlex\View\View $view
 * @var string $content
 */
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <?= $view->partial('head', ['pageTitle' => $pageTitle ?? null]) ?>
    <script src="<?= e(url('/assets/js/print-button.js')) ?>" defer></script>
</head>
<body class="min-h-svh">
    <main class="mx-auto max-w-3xl space-y-4 px-4 py-6"><?= $content ?></main>
</body>
</html>
