<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Block placements</h5>
  <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/system/blocks') ?>"><i class="bi bi-arrow-left"></i> Back to blocks</a>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Add placement</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('admin/system/blocks/placements/add') ?>">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label">Block instance</label>
            <select class="form-select" name="instance_id" required>
              <?php foreach ($instances as $i): ?>
                <option value="<?= (int)$i['id'] ?>">#<?= (int)$i['id'] ?> — <?= esc($i['title'] ?? $i['type']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Area</label>
              <select class="form-select" name="area">
                <option value="admin">admin</option>
                <option value="cabinet">cabinet</option>
                <option value="site">site</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Theme</label>
              <input class="form-control" name="theme" value="default">
            </div>
          </div>

          <div class="row g-2 mt-1">
            <div class="col-6">
              <label class="form-label">Region</label>
              <input class="form-control" name="region" value="sidebar">
            </div>
            <div class="col-6">
              <label class="form-label">Weight</label>
              <input class="form-control" name="weight" value="0" type="number">
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Visible only for URL prefix (optional)</label>
            <input class="form-control" name="path_prefix" placeholder="https://example.com/admin">
          </div>

          <button class="btn btn-primary mt-3">Add</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white fw-semibold">Existing placements</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:80px;">#</th>
              <th>Instance</th>
              <th style="width:80px;">Area</th>
              <th style="width:90px;">Theme</th>
              <th style="width:110px;">Region</th>
              <th style="width:90px;">Weight</th>
              <th style="width:120px;" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($placements)): ?>
            <tr><td colspan="7" class="text-muted p-3">No placements yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($placements as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td>#<?= (int)$p['instance_id'] ?></td>
              <td><code><?= esc($p['area']) ?></code></td>
              <td><?= esc($p['theme']) ?></td>
              <td><?= esc($p['region']) ?></td>
              <td><?= (int)$p['weight'] ?></td>
              <td class="text-end">
                <form class="d-inline" method="post" action="<?= site_url('admin/system/blocks/placements/delete/' . $p['id']) ?>"
                      onsubmit="return confirm('Delete placement #<?= (int)$p['id'] ?>?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
