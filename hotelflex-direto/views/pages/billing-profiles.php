<?php
/**
 * @var HotelFlex\View\View $view
 * @var list<HotelFlex\Fiscal\Domain\BillingProfile> $profiles
 * @var string|null $returnTo
 * @var array<string, mixed> $old
 * @var HotelFlex\Fiscal\Domain\CompanyRecord|null $company
 * @var string|null $lookupMessage
 */
?>
<?php if ($returnTo !== null): ?>
    <a class="text-sm text-muted-foreground hover:text-foreground" href="<?= e(url($returnTo)) ?>">← Voltar ao pagamento</a>
<?php endif; ?>
<?= $view->partial('page-header', [
    'title' => 'Dados para nota fiscal',
    'description' => 'Escolha um destes cadastros antes de cada pagamento. A nota pode sair em seu nome, de outra pessoa ou de uma empresa.',
]) ?>

<div class="grid items-start gap-6 lg:grid-cols-[1fr_1.4fr]">
    <section class="space-y-3">
        <h2 class="text-base font-semibold">Meus cadastros</h2>
        <?php if ($profiles === []): ?>
            <?= $view->partial('empty-state', ['message' => 'Nenhum cadastro ainda. Sem cadastro, a nota sai como "consumidor não identificado".']) ?>
        <?php endif; ?>
        <?php foreach ($profiles as $profile): ?>
            <article class="card py-3">
                <div class="card-content flex-row items-start justify-between px-3">
                    <div class="space-y-0.5">
                        <p class="font-medium"><?= e($profile->name) ?></p>
                        <p class="text-muted-foreground"><?= e($profile->kind->label()) ?> · <?= e($profile->document->mask()) ?></p>
                        <?php if ($profile->company !== null): ?>
                            <p class="text-xs text-muted-foreground">Receita: <?= e($profile->company->status) ?> · <?= e($profile->company->describeRegime()) ?> · consultado em <?= e(formatDate($profile->company->lookedUpAt)) ?></p>
                        <?php endif; ?>
                        <?php if ($profile->address !== null): ?>
                            <p class="text-xs text-muted-foreground"><?= e($profile->address->format()) ?></p>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= e(url("/faturamento/{$profile->id}/remover")) ?>" data-confirm="Remover este cadastro? Notas já emitidas não mudam.">
                        <?= $view->partial('csrf-field') ?>
                        <button type="submit" class="btn btn-ghost btn-sm text-destructive">Remover</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <p class="text-xs text-muted-foreground">Por segurança (LGPD), o CPF aparece só em parte. Os dados são usados apenas para emitir notas fiscais.</p>
    </section>

    <?= $view->partial('billing-profile-form', ['returnTo' => $returnTo, 'old' => $old, 'company' => $company, 'lookupMessage' => $lookupMessage]) ?>
</div>
