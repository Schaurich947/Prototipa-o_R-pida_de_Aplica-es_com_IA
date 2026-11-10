<?php
/**
 * Admin-only GET selector (hotel or guest) that submits itself on change.
 *
 * @var string $action
 * @var string $name query parameter
 * @var string $label
 * @var array<int, string> $options id => label
 * @var int $selectedId
 */
$inputId = "picker-{$name}";
?>
<form method="get" action="<?= e(url($action)) ?>" class="flex flex-col gap-2" data-auto-submit>
    <label class="label" for="<?= e($inputId) ?>"><?= e($label) ?></label>
    <div class="flex gap-2">
        <select class="select" id="<?= e($inputId) ?>" name="<?= e($name) ?>">
            <?php foreach ($options as $optionId => $optionLabel): ?>
                <option value="<?= e($optionId) ?>" <?= $optionId === $selectedId ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline" data-auto-submit-fallback>Ver</button>
    </div>
</form>
