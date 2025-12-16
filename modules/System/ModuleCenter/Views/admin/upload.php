<div class="card shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <div class="fw-semibold">Upload ZIP module</div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/system/modules') ?>"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
  <div class="card-body">
    <div class="alert alert-info">
      ZIP должен содержать один модуль. Поддерживаемые категории: <code>System</code>, <code>Pages</code>, <code>Blocks</code>.
      Внутри должен быть файл: <code>Config/Info.php</code>.
    </div>

    <form method="post" enctype="multipart/form-data" action="<?= site_url('admin/system/modules/upload') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label">ZIP package</label>
        <input class="form-control" type="file" name="module_zip" accept=".zip" required>
      </div>

      <button class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
    </form>
  </div>
</div>
