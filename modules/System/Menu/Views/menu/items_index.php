<?php
/** @var array $menu */
/** @var array $items */
?>
<div class="container-fluid py-3">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 mb-0">Menu items</h1>
            <div class="text-muted small">
                Menu: <code><?= esc((string) $menu['menu_key']) ?></code> — <?= esc((string) $menu['title']) ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/system/menus')) ?>">Back</a>
            <a class="btn btn-primary" href="<?= esc(site_url('admin/system/menus/' . (int) $menu['id'] . '/items/create')) ?>">Add item</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th style="width:80px;">ID</th>
                <th>Title</th>
                <th style="width:120px;">Order</th>
                <th style="width:110px;">Active</th>
                <th style="width:420px;">Link</th>
                <th style="width:180px;"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <?php
                $depth = (int) ($it['_depth'] ?? 0);
                $prefix = str_repeat('— ', $depth);
                $link = '';
                if (!empty($it['route_name'])) {
                    $link = 'route:' . (string) $it['route_name'];
                } elseif (!empty($it['url'])) {
                    $link = (string) $it['url'];
                }
                ?>
                <tr>
                    <td><?= (int) $it['id'] ?></td>
                    <td>
                        <div class="fw-semibold"><?= esc($prefix . (string) $it['title']) ?></div>
                        <div class="text-muted small">
                            <?php if (!empty($it['icon'])): ?>icon: <code><?= esc((string) $it['icon']) ?></code><?php endif; ?>
                            <?php if (!empty($it['css_class'])): ?> class: <code><?= esc((string) $it['css_class']) ?></code><?php endif; ?>
                        </div>
                    </td>
                    <td><?= (int) ($it['sort_order'] ?? 0) ?></td>
                    <td>
                        <button class="btn btn-sm <?= ((int) $it['is_active']) ? 'btn-outline-success' : 'btn-outline-secondary' ?> js-toggle"
                                data-url="<?= esc(site_url('admin/system/menus/' . (int) $menu['id'] . '/items/toggle/' . (int) $it['id'])) ?>">
                            <?= ((int) $it['is_active']) ? 'On' : 'Off' ?>
                        </button>
                    </td>
                    <td><code><?= esc($link) ?></code></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= esc(site_url('admin/system/menus/' . (int) $menu['id'] . '/items/edit/' . (int) $it['id'])) ?>">
                            Edit
                        </a>
                        <form class="d-inline" method="post"
                              action="<?= esc(site_url('admin/system/menus/' . (int) $menu['id'] . '/items/delete/' . (int) $it['id'])) ?>"
                              onsubmit="return confirm('Delete item? Children will remain but become top-level.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No items</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    async function postJson(url){
        const tokenInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        const tokenName = tokenInput ? tokenInput.name : null;
        const tokenVal  = tokenInput ? tokenInput.value : null;

        const form = new FormData();
        if (tokenName) form.append(tokenName, tokenVal);

        const res = await fetch(url, { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        return await res.json();
    }

    document.querySelectorAll('.js-toggle').forEach(btn => {
        btn.addEventListener('click', async function(){
            const url = this.getAttribute('data-url');
            try {
                const data = await postJson(url);
                if (!data.ok) return alert(data.error || 'Error');
                this.textContent = data.is_active ? 'On' : 'Off';
                this.classList.toggle('btn-outline-success', !!data.is_active);
                this.classList.toggle('btn-outline-secondary', !data.is_active);
            } catch (e) {
                alert('Request failed');
            }
        });
    });
})();
</script>
