<?php
/**
 * @var HotelFlex\View\View $view
 * @var HotelFlex\Domain\User $user
 */
?>
<?= $view->partial('page-header', ['title' => 'Meu perfil', 'description' => "{$user->name} · {$user->email}"]) ?>

<form method="post" action="<?= e(url('/perfil')) ?>" class="card max-w-xl">
    <header class="card-header">
        <div>
            <h2 class="card-title">Mensagens automáticas</h2>
            <p class="card-description">E-mails sobre suas reservas são sempre enviados. O WhatsApp só é usado com a sua autorização.</p>
        </div>
    </header>
    <div class="card-content">
        <?= $view->partial('csrf-field') ?>
        <?= $view->partial('whatsapp-consent-fields', ['phoneValue' => $user->whatsappPhone?->format() ?? '', 'isConsentChecked' => $user->whatsappOptInAt !== null]) ?>
        <?php if ($user->whatsappOptInAt !== null): ?>
            <p class="text-xs text-muted-foreground">Autorizado em <?= e(formatMoment($user->whatsappOptInAt)) ?>.</p>
        <?php endif; ?>
    </div>
    <footer class="card-footer">
        <button type="submit" class="btn btn-primary">Salvar preferências</button>
    </footer>
</form>
