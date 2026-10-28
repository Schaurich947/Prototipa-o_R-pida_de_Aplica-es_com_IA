<?php /** @var string|null $pageTitle */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<title><?= e(isset($pageTitle) ? "{$pageTitle} · HotelFlex" : 'HotelFlex') ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
<script src="<?= e(url('/assets/js/late-checkout.js')) ?>" defer></script>
