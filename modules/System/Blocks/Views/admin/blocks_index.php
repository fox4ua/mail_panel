<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Blocks</h5>
  <div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('admin/system/blocks/placements') ?>"><i class="bi bi-diagram-3"></i> Placements</a>
    <a class="btn btn-sm btn-primary" href="<?= site_url('admin/system/blocks/create') ?>"><i class="bi bi-plus-lg"></i> New block</a>
  </div>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:80px;">#</th>
          <th style="width:120px;">Type</th>
          <th>Title</th>
          <th style="width:120px;">Enabled</th>
          <th style="width:180px;" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($blocks)): ?>
        <tr><td colspan="5" class="text-muted p-3">No blocks (or Blocks module not installed yet).</td></tr>
      <?php endif; ?>
      <?php foreach ($blocks as $b): ?>
        <tr>
          <td><?= (int)$b['id'] ?></td>
          <td><code><?= esc($b['type']) ?></code></td>
          <td><?= esc($b['title'] ?? '') ?></td>
          <td>
            <?php if ((int)$b['is_enabled'] === 1): ?>
              <span class="badge text-bg-success">yes</span>
            <?php else: ?>
              <span class="badge text-bg-secondary">no</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/system/blocks/edit/' . $b['id']) ?>"><i class="bi bi-pencil"></i> Edit</a>
            <form class="d-inline" method="post" action="<?= site_url('admin/system/blocks/delete/' . $b['id']) ?>"
                  onsubmit="return confirm('Delete block #<?= (int)$b['id'] ?>?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
