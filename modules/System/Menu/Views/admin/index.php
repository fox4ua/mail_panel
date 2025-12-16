<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Menu items</h5>
  <div class="d-flex gap-2">
    <a class="btn btn-sm btn-primary" href="<?= site_url('admin/system/menu/create') ?>"><i class="bi bi-plus-lg"></i> New</a>
    <form class="d-inline" method="post" action="<?= site_url('admin/system/menu/sync') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="area" value="<?= esc($area) ?>">
      <input type="hidden" name="menu_key" value="<?= esc($menuKey) ?>">
      <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat"></i> Sync system items</button>
    </form>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form class="row g-2" method="get" action="<?= site_url('admin/system/menu') ?>">
      <div class="col-6 col-md-3">
        <label class="form-label">Area</label>
        <select class="form-select" name="area">
          <option value="admin" <?= $area==='admin'?'selected':'' ?>>admin</option>
          <option value="cabinet" <?= $area==='cabinet'?'selected':'' ?>>cabinet</option>
          <option value="site" <?= $area==='site'?'selected':'' ?>>site</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Menu key</label>
        <input class="form-control" name="menu_key" value="<?= esc($menuKey) ?>" placeholder="sidebar/top/footer">
      </div>
      <div class="col-12 col-md-3 d-flex align-items-end">
        <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Apply</button>
      </div>
      <div class="col-12 col-md-3 d-flex align-items-end">
        <div class="text-muted small">Системные пункты берутся из манифестов модулей и синхронизируются в БД.</div>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:70px;">#</th>
          <th style="width:180px;">Key</th>
          <th>Label</th>
          <th style="width:140px;">URL</th>
          <th style="width:90px;">W</th>
          <th style="width:110px;">Enabled</th>
          <th style="width:120px;">System</th>
          <th style="width:180px;" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="8" class="text-muted p-3">No items.</td></tr>
      <?php endif; ?>

      <?php foreach ($items as $i): ?>
        <tr>
          <td><?= (int)$i['id'] ?></td>
          <td><code><?= esc($i['item_key']) ?></code></td>
          <td>
            <?php if (!empty($i['icon'])): ?><i class="<?= esc($i['icon']) ?> me-2"></i><?php endif; ?>
            <?= esc($i['label']) ?>
            <?php if (!empty($i['module'])): ?><div class="text-muted small"><?= esc($i['module']) ?></div><?php endif; ?>
          </td>
          <td><code><?= esc($i['url'] ?? '') ?></code></td>
          <td><?= (int)$i['weight'] ?></td>
          <td>
            <?php if ((int)$i['is_enabled'] === 1): ?>
              <span class="badge text-bg-success">yes</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">no</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ((int)$i['is_system'] === 1): ?>
              <span class="badge text-bg-info">system</span>
            <?php else: ?>
              <span class="badge text-bg-light text-dark">custom</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/system/menu/edit/' . $i['id']) ?>"><i class="bi bi-pencil"></i></a>
            <form class="d-inline" method="post" action="<?= site_url('admin/system/menu/toggle/' . $i['id']) ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-secondary" title="Enable/Disable"><i class="bi bi-power"></i></button>
            </form>
            <?php if ((int)$i['is_system'] !== 1): ?>
              <form class="d-inline" method="post" action="<?= site_url('admin/system/menu/delete/' . $i['id']) ?>"
                    onsubmit="return confirm('Delete menu item #<?= (int)$i['id'] ?>?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
