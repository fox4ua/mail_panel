<!doctype html>
<html lang="uk">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Admin') ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= site_url('admin') ?>">Admin</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="topnav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="<?= site_url('admin/system/modules') ?>"><i class="bi bi-boxes"></i> Modules</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('admin/system/menu') ?>"><i class="bi bi-list"></i> Menu</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('admin/system/blocks') ?>"><i class="bi bi-layout-text-sidebar"></i> Blocks</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid mt-3">
  <div class="row g-3">
    <aside class="col-12 col-lg-3">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Menu</div>
        <div class="list-group list-group-flush">
          <?php if (!empty($menu)): ?>
            <?php foreach ($menu as $item): ?>
              <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                 href="<?= esc($item['url']) ?>">
                <span>
                  <?php if (!empty($item['icon'])): ?><i class="<?= esc($item['icon']) ?> me-2"></i><?php endif; ?>
                  <?= esc($item['label']) ?>
                </span>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="p-3 text-muted">Menu is empty</div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (isset($blocks) && $blocks): ?>
        <div class="mt-3">
          <?= $blocks->renderRegion('admin', 'default', 'sidebar', current_url()) ?>
        </div>
      <?php endif; ?>
    </aside>

    <main class="col-12 col-lg-9">
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>
      <?php
      $content = $content ?? ($body ?? ($main ?? ''));
      if ($content === '' && isset($this) && method_exists($this, 'renderSection')) {
          $content = $this->renderSection('content');
      }
      ?>
      <?= $content ?>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
