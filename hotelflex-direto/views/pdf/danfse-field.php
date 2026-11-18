<?php
/**
 * One labelled cell of the DANFSe grid.
 *
 * @var string $label
 * @var string $value
 * @var int $span
 */
?>
<td colspan="<?= e($span ?? 1) ?>"><span class="label"><?= e($label) ?></span><span class="value"><?= e($value !== '' ? $value : '-') ?></span></td>
