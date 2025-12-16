<?php
// Variables: $title, $dry
$dry = $dry ?? [];
$inUse = !empty($dry['in_use']);
$reasons = $dry['in_use_reasons'] ?? [];
$broken = !empty($dry['broken']);
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <h3 class="m-0"><?= esc($title ?? 'Uninstall preview'); ?></h3>
  <a class="btn btn-outline-secondary" href="<?= site_url('admin/system/modules'); ?>">Back</a>
</div>

<div class="card shadow-sm border-0 rounded-4">
  <div class="card-body">
    <div class="mb-2"><b>Module:</b> <?= esc($dry['name'] ?? '') ?></div>
    <div class="mb-2"><b>Category:</b> <?= esc($dry['category'] ?? '') ?></div>
    <div class="mb-2"><b>Installed:</b> <?= !empty($dry['installed']) ? 'yes' : 'no' ?></div>
    <div class="mb-2"><b>Enabled:</b> <?= !empty($dry['enabled']) ? 'yes' : 'no' ?></div>
    <div class="mb-2"><b>Version:</b> <?= esc($dry['version'] ?? '-') ?></div>

    <?php if ($broken): ?>
      <div class="alert alert-danger mt-3 mb-3">
        Manifest is broken. Safe uninstall is blocked (hook may fail). Fix the module files first.
      </div>
    <?php endif; ?>

    <?php if ($inUse): ?>
      <div class="alert alert-warning mt-3 mb-3">
        <div class="fw-semibold mb-1">Module is in use. Uninstall is blocked.</div>
        <?php if (!empty($reasons)): ?>
          <ul class="mb-0">
            <?php foreach ($reasons as $r): ?>
              <li><?= esc((string)$r); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <hr>

    <div class="mb-3">
      <div class="fw-semibold mb-1">Menu</div>
      <div class="small text-muted">
        system items: <?= (int)($dry['menu']['count_system'] ?? 0) ?>,
        total: <?= (int)($dry['menu']['count_total'] ?? 0) ?>
      </div>
    </div>

    <div class="mb-3">
      <div class="fw-semibold mb-1">Files</div>
      <?php if (!empty($dry['files']['will_delete'])): ?>
        <div class="small text-muted">
          <?= esc($dry['files']['path'] ?? '') ?>,
          files: <?= (int)($dry['files']['files'] ?? 0) ?>,
          size: <?= number_format(((int)($dry['files']['bytes'] ?? 0))/1024/1024, 2) ?> MB
        </div>
      <?php else: ?>
        <div class="small text-muted">not deleting files for this category</div>
      <?php endif; ?>
    </div>

    <hr>

    <form method="post" action="<?= site_url('admin/system/modules/uninstall/' . ($dry['name'] ?? '')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-danger" <?= ($inUse || $broken) ? 'disabled' : '' ?>>Confirm uninstall</button>
    </form>
  </div>
</div>
