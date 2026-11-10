<?php /** @var string|null $debugMessage only filled when APP_DEBUG=true */ ?>
<div class="space-y-3 text-center">
    <h1 class="text-2xl font-semibold">Algo deu errado</h1>
    <p class="text-sm text-muted-foreground">Não foi possível concluir a operação. Tente novamente em instantes.</p>
    <?php if (!empty($debugMessage)): ?>
        <p class="alert alert-error"><?= e($debugMessage) ?></p>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= e(url('/login')) ?>">Voltar ao início</a>
</div>
