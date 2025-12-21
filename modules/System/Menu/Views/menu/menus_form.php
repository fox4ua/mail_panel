<?php
/** @var array|null $menu */
/** @var array $errors */
$isEdit = is_array($menu);
?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0"><?= $isEdit ? 'Edit menu' : 'Create menu' ?></h1>
        <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/system/menus')) ?>">Back</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Errors</div>
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= esc((string) $e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= esc(site_url($isEdit ? 'admin/system/menus/update/' . (int) $menu['id'] : 'admin/system/menus/store')) ?>">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Menu key</label>
                <input class="form-control" name="menu_key" maxlength="64" required
                       value="<?= esc((string) ($menu['menu_key'] ?? old('menu_key') ?? 'main')) ?>">
                <div class="form-text">Allowed: a-z A-Z 0-9 . _ -</div>
            </div>

            <div class="col-md-8">
                <label class="form-label">Title</label>
                <input class="form-control" name="title" maxlength="120" required
                       value="<?= esc((string) ($menu['title'] ?? old('title') ?? 'Main menu')) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <input class="form-control" name="description" maxlength="255"
                       value="<?= esc((string) ($menu['description'] ?? old('description') ?? '')) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Active</label>
                <select class="form-select" name="is_active">
                    <?php $v = (string) ((int) ($menu['is_active'] ?? old('is_active') ?? 1)); ?>
                    <option value="1" <?= $v === '1' ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= $v === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/system/menus')) ?>">Cancel</a>
        </div>
    </form>
</div>
