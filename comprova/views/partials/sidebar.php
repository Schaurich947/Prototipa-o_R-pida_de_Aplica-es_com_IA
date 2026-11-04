<?php
/**
 * @var Comprova\View\View $view
 * @var Comprova\Domain\User $currentUser
 * @var string $currentPath
 * @var string $companyName
 */
$items = [
    ['path' => '/comprovantes', 'label' => 'Comprovantes', 'icon' => 'M7 3.8h7l4 4V20H7a2 2 0 0 1-2-2V5.8a2 2 0 0 1 2-2ZM14 4v4h4M8.5 12h7M8.5 15.5h7'],
    ['path' => '/comprovantes/novo', 'label' => 'Registrar pagamento', 'icon' => 'M12 5v14M5 12h14'],
    ['path' => '/integracoes', 'label' => 'Integrações', 'icon' => 'M9.5 16.5 7 19a3.5 3.5 0 0 1-5-5l4-4a3.5 3.5 0 0 1 5 0m5.5-.5L19 7a3.5 3.5 0 0 1 5 5l-4 4a3.5 3.5 0 0 1-5 0m-7 1 8-8'],
];
$isItemActive = static fn (string $path): bool => $path === '/comprovantes'
    ? $currentPath === $path || preg_match('#^/comprovantes/\d+#', $currentPath) === 1
    : $currentPath === $path;
?>
<aside class="sidebar">
    <?= $view->partial('brand') ?>
    <div class="nav-label">Workspace</div>
    <nav class="nav" aria-label="Navegação principal">
        <?php foreach ($items as $item): ?>
            <?php $isActive = $isItemActive($item['path']); ?>
            <a href="<?= e(url($item['path'])) ?>" class="<?= $isActive ? 'active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="<?= e($item['icon']) ?>" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
        <div class="profile">
            <div class="avatar" aria-hidden="true"><?= e(initials($companyName)) ?></div>
            <div>
                <div class="profile-name"><?= e($companyName) ?></div>
                <div class="profile-role"><?= e($currentUser->name) ?></div>
            </div>
            <form method="post" action="<?= e(url('/logout')) ?>">
                <?= $view->partial('csrf-field') ?>
                <button type="submit" class="secondary-button small-button">Sair</button>
            </form>
        </div>
    </div>
</aside>
