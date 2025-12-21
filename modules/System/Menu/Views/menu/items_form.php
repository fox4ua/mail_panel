<?php
/** @var array $menu */
/** @var array|null $item */
/** @var array $parents */
/** @var array $errors */
$isEdit = is_array($item);
?>
<div class="container-fluid py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-0"><?= $isEdit ? 'Edit item' : 'Add item' ?></h1>
            <div class="text-muted small">
                Menu: <code><?= esc((string) $menu['menu_key']) ?></code> — <?= esc((string) $menu['title']) ?>
            </div>
        </div>
        <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/system/menus/' . (int) $menu['id'] . '/items')) ?>">Back</a>
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

    <form method="post"
          action="<?= esc(site_url($isEdit ? 'admin/system/menus/' . (int) $menu['id'] . '/items/update/' . (int) $item['id'] : 'admin/system/menus/' . (int) $menu['id'] . '/items/store')) ?>">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Title</label>
                <input class="form-control" name="title" maxlength="120" required
                       value="<?= esc((string) ($item['title'] ?? old('title') ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Parent</label>
                <?php $parent = (string) ($item['parent_id'] ?? old('parent_id') ?? ''); ?>
                <select class="form-select" name="parent_id">
                    <option value="">— none —</option>
                    <?php foreach ($parents as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= ((string) (int) $p['id'] === $parent) ? 'selected' : '' ?>>
                            <?= esc((string) $p['title']) ?> (ID <?= (int) $p['id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">URL</label>
                <input class="form-control" name="url" maxlength="255"
                       placeholder="/admin/system/settings or https://example.com"
                       value="<?= esc((string) ($item['url'] ?? old('url') ?? '')) ?>">
                <div class="form-text">If route_name is set, URL can be empty.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Route name (optional)</label>
                <input class="form-control" name="route_name" maxlength="120"
                       placeholder="admin.system.settings"
                       value="<?= esc((string) ($item['route_name'] ?? old('route_name') ?? '')) ?>">
                <div class="form-text">If set, link will be generated via route_to(route_name).</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Order</label>
                <input class="form-control" type="number" name="sort_order"
                       value="<?= esc((string) ($item['sort_order'] ?? old('sort_order') ?? 0)) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Active</label>
                <?php $active = (string) ((int) ($item['is_active'] ?? old('is_active') ?? 1)); ?>
                <select class="form-select" name="is_active">
                    <option value="1" <?= $active === '1' ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= $active === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Target</label>
                <input class="form-control" name="target" maxlength="20"
                       placeholder="_blank"
                       value="<?= esc((string) ($item['target'] ?? old('target') ?? '')) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Icon</label>
                <input class="form-control" name="icon" maxlength="80"
                       placeholder="bi bi-house"
                       value="<?= esc((string) ($item['icon'] ?? old('icon') ?? '')) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">CSS class</label>
                <input class="form-control" name="css_class" maxlength="120"
                       placeholder="nav-link"
                       value="<?= esc((string) ($item['css_class'] ?? old('css_class') ?? '')) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Extra attributes (JSON)</label>
                <textarea class="form-control" rows="4" name="attrs_json"
                          placeholder='{"rel":"nofollow","data-x":"1"}'><?= esc((string) ($item['attrs_json'] ?? old('attrs_json') ?? '')) ?></textarea>
                <div class="form-text">Optional. Must be a JSON object if used.</div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/system/menus/' . (int) $menu['id'] . '/items')) ?>">Cancel</a>
        </div>
    </form>
</div>
