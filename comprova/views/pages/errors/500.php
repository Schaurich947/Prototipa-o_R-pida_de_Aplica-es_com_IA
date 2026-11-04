<?php /** @var string|null $debugMessage only filled when APP_DEBUG=true */ ?>
<section class="panel panel-body">
    <h1>Algo deu errado</h1>
    <p class="subtitle">Não foi possível concluir a operação. Tente novamente em instantes.</p>
    <?php if (!empty($debugMessage)): ?>
        <p class="alert alert-error"><?= e($debugMessage) ?></p>
    <?php endif; ?>
    <div class="form-actions"><a class="secondary-button" href="<?= e(url('/comprovantes')) ?>">Voltar ao início</a></div>
</section>
