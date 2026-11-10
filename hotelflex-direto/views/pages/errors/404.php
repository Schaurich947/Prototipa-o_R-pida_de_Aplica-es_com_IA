<?php /** @var string $message */ ?>
<div class="space-y-3 text-center">
    <h1 class="text-2xl font-semibold">Não encontrado</h1>
    <p class="text-sm text-muted-foreground"><?= e($message) ?></p>
    <a class="btn btn-outline" href="<?= e(url('/login')) ?>">Voltar ao início</a>
</div>
