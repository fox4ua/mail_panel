<?php
/** @var array $menus */
?>
<div class="container-fluid py-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Menus</h1>
        <a class="btn btn-primary" href="<?= esc(site_url('admin/system/menus/create')) ?>">Create</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th style="width:90px;">ID</th>
                <th style="width:220px;">Key</th>
                <th>Title</th>
                <th style="width:130px;">Active</th>
                <th style="width:260px;"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($menus as $m): ?>
                <tr>
                    <td><?= (int) $m['id'] ?></td>
                    <td><code><?= esc((string) $m['menu_key']) ?></code></td>
                    <td>
                        <div class="fw-semibold"><?= esc((string) $m['title']) ?></div>
                        <?php if (!empty($m['description'])): ?>
                            <div class="text-muted small"><?= esc((string) $m['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= ((int) $m['is_active']) ? 'Yes' : 'No' ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary"
                           href="<?= esc(site_url('admin/system/menus/' . (int) $m['id'] . '/items')) ?>">
                            Items
                        </a>
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= esc(site_url('admin/system/menus/edit/' . (int) $m['id'])) ?>">
                            Edit
                        </a>
                        <form class="d-inline" method="post"
                              action="<?= esc(site_url('admin/system/menus/delete/' . (int) $m['id'])) ?>"
                              onsubmit="return confirm('Delete menu and its items?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($menus)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">No menus</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
