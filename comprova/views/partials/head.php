<?php /** @var string|null $pageTitle */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f6f7f5">
<title><?= e(isset($pageTitle) ? "{$pageTitle} · Comprova" : 'Comprova') ?></title>
<?php foreach (['base', 'layout', 'components', 'forms'] as $stylesheet): ?>
    <link rel="stylesheet" href="<?= e(url("/assets/css/{$stylesheet}.css")) ?>">
<?php endforeach; ?>
