<?php
$action = ($mode === 'edit')
  ? site_url('admin/system/blocks/edit/' . $block['id'])
  : site_url('admin/system/blocks/create');

$enabled = (int)($block['is_enabled'] ?? 1) === 1;

$html = $block['html'] ?? '';
if ($html === '' && !empty($block['settings_json'])) {
  $s = json_decode($block['settings_json'], true) ?: [];
  $html = $s['html'] ?? '';
}
?>

<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold"><?= esc($title) ?></div>
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label">Type</label>
        <select class="form-select" name="type" <?= $mode === 'edit' ? 'disabled' : '' ?>>
          <option value="html" <?= (($block['type'] ?? 'html') === 'html') ? 'selected' : '' ?>>HTML</option>
        </select>
        <?php if ($mode === 'edit'): ?>
          <input type="hidden" name="type" value="<?= esc($block['type']) ?>">
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label">Title</label>
        <input class="form-control" name="title" value="<?= esc($block['title'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">HTML</label>
        <textarea class="form-control" name="html" rows="8"><?= esc($html) ?></textarea>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="is_enabled" id="enabled" <?= $enabled ? 'checked' : '' ?>>
        <label class="form-check-label" for="enabled">Enabled</label>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary">Save</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('admin/system/blocks') ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
