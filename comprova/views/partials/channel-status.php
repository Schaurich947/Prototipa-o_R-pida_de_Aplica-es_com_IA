<?php
/**
 * @var Comprova\Domain\Notification|null $notification
 * @var string $emptyLabel shown when the channel does not apply (e.g. no WhatsApp consent)
 */
?>
<?php if ($notification === null): ?>
    <span class="status none"><?= e($emptyLabel) ?></span>
<?php else: ?>
    <span class="<?= e(statusClass($notification->status)) ?>" <?= $notification->lastError ? 'title="' . e($notification->lastError) . '"' : '' ?>><?= e($notification->status->label()) ?></span>
<?php endif; ?>
