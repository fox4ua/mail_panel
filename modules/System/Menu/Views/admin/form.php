<?php
$action = ($mode === 'edit')
  ? site_url('admin/system/menu/edit/' . $item['id'])
  : site_url('admin/system/menu/create');

$enabled  = (int)($item['is_enabled'] ?? 1) === 1;
$isSystem = (int)($item['is_system'] ?? 0) === 1;
?>

<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold"><?= esc($title) ?></div>
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>

      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label class="form-label">Area</label>
          <select class="form-select" name="area">
            <option value="admin" <?= ($item['area'] ?? 'admin')==='admin'?'selected':'' ?>>admin</option>
            <option value="cabinet" <?= ($item['area'] ?? '')==='cabinet'?'selected':'' ?>>cabinet</option>
            <option value="site" <?= ($item['area'] ?? '')==='site'?'selected':'' ?>>site</option>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Menu key</label>
          <input class="form-control" name="menu_key" value="<?= esc($item['menu_key'] ?? 'sidebar') ?>">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Weight</label>
          <input class="form-control" type="number" name="weight" value="<?= esc($item['weight'] ?? 0) ?>">
        </div>
      </div>

      <div class="mt-3">
        <label class="form-label">Item key (unique)</label>
        <input class="form-control" name="item_key" value="<?= esc($item['item_key'] ?? '') ?>" <?= $mode==='edit'?'disabled':'' ?>>
        <?php if ($mode === 'edit'): ?>
          <div class="form-text">Ключ менять нельзя (используется для синхронизации системных пунктов).</div>
        <?php endif; ?>
      </div>

      <div class="mt-3">
        <label class="form-label">Label</label>
        <input class="form-control" name="label" value="<?= esc($item['label'] ?? '') ?>">
      </div>

      <div class="mt-3">
        <label class="form-label">Icon (Bootstrap Icons class)</label>
        <input class="form-control" name="icon" value="<?= esc($item['icon'] ?? '') ?>" placeholder="bi bi-boxes">
      </div>

      <div class="mt-3">
        <label class="form-label">URL (route path)</label>
        <input class="form-control" name="url" value="<?= esc($item['url'] ?? '') ?>" placeholder="admin/system/modules">
        <div class="form-text">Сохраняется как путь. При рендере превращается в site_url(...).</div>
      </div>

      <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" name="is_enabled" id="enabled" <?= $enabled ? 'checked' : '' ?>>
        <label class="form-check-label" for="enabled">Enabled</label>
        <?php if ($isSystem): ?><span class="badge text-bg-info ms-2">system</span><?php endif; ?>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary">Save</button>
        <a class="btn btn-outline-secondary" href="<?= site_url('admin/system/menu') ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
