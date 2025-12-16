<?= $this->extend('Modules\System\Layout\Views\layouts\admin'); ?>

<?= $this->section('content'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 m-0"><?= esc($title ?? 'Modules'); ?></h1>

  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="<?= site_url('admin/system/modules/upload'); ?>">
      <i class="bi bi-upload"></i> Upload
    </a>

    <form method="post" action="<?= site_url('admin/system/modules/rescan'); ?>" class="m-0">
      <?= csrf_field(); ?>
      <button type="submit" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-clockwise"></i> Rescan
      </button>
    </form>
  </div>
</div>

<?php
$groups = [
  'system' => 'System',
  'pages'  => 'Pages',
  'blocks' => 'Blocks',
];

$byCat = ['system' => [], 'pages' => [], 'blocks' => [], 'other' => []];
foreach (($modules ?? []) as $m) {
  $c = strtolower((string)($m['category'] ?? 'other'));
  if (!isset($byCat[$c])) $c = 'other';
  $byCat[$c][] = $m;
}

function badgeClass(string $state): string {
  return match ($state) {
    'enabled'       => 'bg-success',
    'disabled'      => 'bg-secondary',
    'not_installed' => 'bg-warning text-dark',
    'broken'        => 'bg-danger',
    default         => 'bg-light text-dark',
  };
}

function badgeLabel(string $state): string {
  return match ($state) {
    'enabled'       => 'Enabled',
    'disabled'      => 'Disabled',
    'not_installed' => 'Not installed',
    'broken'        => 'Broken',
    default         => $state,
  };
}
?>

<div class="card shadow-sm">
  <div class="card-body p-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 180px;">Name</th>
          <th>Title</th>
          <th style="width: 120px;">Version</th>
          <th style="width: 140px;">State</th>
          <th style="width: 440px;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($groups as $cat => $label): ?>
        <tr class="table-secondary">
          <th colspan="5"><?= esc($label); ?></th>
        </tr>

        <?php foreach (($byCat[$cat] ?? []) as $m): ?>
          <?php
            $name   = (string)($m['name'] ?? '');
            $title2 = (string)($m['title'] ?? '');
            $ver    = (string)($m['version'] ?? '-');
            $state  = (string)($m['state'] ?? 'not_installed');
            $broken = !empty($m['broken']);
            $reason = (string)($m['broken_reason'] ?? '');

            $isSystem = ($cat === 'system');

            $canInstall  = !$isSystem && !$broken && ($state === 'not_installed');
            $canEnable   = !$isSystem && !$broken && ($state === 'disabled');
            $canDisable  = !$isSystem && !$broken && ($state === 'enabled');
            $canUninstall = !$isSystem && ($state !== 'not_installed');
          ?>
          <tr>
            <td><code><?= esc($name); ?></code></td>
            <td>
              <?= esc($title2); ?>
              <?php if ($broken && $reason): ?>
                <div class="small text-danger">Reason: <?= esc($reason); ?></div>
              <?php endif; ?>
              <?php if (!empty($m['route_conflicts'])): ?>
                <div class="small text-danger">Route conflicts detected</div>
              <?php endif; ?>
            </td>
            <td><?= esc($ver ?: '-'); ?></td>
            <td>
              <span class="badge <?= esc(badgeClass($state)); ?>">
                <?= esc(badgeLabel($state)); ?>
              </span>
              <?php if ($isSystem): ?>
                <span class="badge bg-dark ms-1">System</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex flex-wrap gap-2">
                <?php if ($canInstall): ?>
                  <form method="post" action="<?= site_url('admin/system/modules/install/' . rawurlencode($name)); ?>" class="m-0">
                    <?= csrf_field(); ?>
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-play-fill"></i> Install
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($canEnable): ?>
                  <form method="post" action="<?= site_url('admin/system/modules/enable/' . rawurlencode($name)); ?>" class="m-0">
                    <?= csrf_field(); ?>
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-toggle-on"></i> Enable
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($canDisable): ?>
                  <form method="post" action="<?= site_url('admin/system/modules/disable/' . rawurlencode($name)); ?>" class="m-0">
                    <?= csrf_field(); ?>
                    <button type="submit" class="btn btn-sm btn-warning">
                      <i class="bi bi-toggle-off"></i> Disable
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($canUninstall): ?>
                  <form method="post" action="<?= site_url('admin/system/modules/uninstall/' . rawurlencode($name)); ?>" class="m-0"
                        onsubmit="return confirm('Удалить модуль <?= esc($name); ?>? Будут удалены файлы и данные модуля.');">
                    <?= csrf_field(); ?>
                    <button type="submit" class="btn btn-sm btn-danger">
                      <i class="bi bi-trash"></i> Uninstall
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($isSystem): ?>
                  <span class="text-muted small align-self-center">Protected</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection(); ?>
