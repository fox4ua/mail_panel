<?php
/** @var array $groups */
/** @var array $values */
/** @var array $errors */
/** @var object $render */
?>
<div class="container-fluid py-3">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc((string) session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc((string) session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Settings</h1>
        <div class="text-muted small">Saved in DB table <code>settings</code></div>
    </div>

    <form method="post" action="<?= esc(site_url('admin/system/settings/save')) ?>">
        <?= csrf_field() ?>

        <ul class="nav nav-tabs" role="tablist">
            <?php $i = 0; foreach ($groups as $groupKey => $group): $i++; ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $i === 1 ? 'active' : '' ?>"
                            id="tab-<?= esc($groupKey) ?>"
                            data-bs-toggle="tab"
                            data-bs-target="#pane-<?= esc($groupKey) ?>"
                            type="button"
                            role="tab">
                        <?= esc((string) ($group['label'] ?? $groupKey)) ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3">
            <?php $i = 0; foreach ($groups as $groupKey => $group): $i++; ?>
                <div class="tab-pane fade <?= $i === 1 ? 'show active' : '' ?>" id="pane-<?= esc($groupKey) ?>" role="tabpanel">

                    <?php foreach (($group['fields'] ?? []) as $field): ?>
                        <?php
                        $key     = (string) ($field['key'] ?? '');
                        $label   = (string) ($field['label'] ?? $key);
                        $type    = (string) ($field['type'] ?? 'string');
                        $help    = (string) ($field['help'] ?? '');
                        $options = (array)  ($field['options'] ?? []);
                        $val     = $values[$key] ?? ($field['default'] ?? '');
                        $err     = $errors[$key] ?? null;

                        $id = 'f_' . preg_replace('~[^a-zA-Z0-9_]+~', '_', $key);
                        ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= esc($id) ?>">
                                <?= esc($label) ?>
                                <?php if ($key !== ''): ?><span class="text-muted small ms-1"><code><?= esc($key) ?></code></span><?php endif; ?>
                            </label>

                            <?php if ($type === 'text'): ?>
                                <textarea class="form-control <?= $err ? 'is-invalid' : '' ?>"
                                          id="<?= esc($id) ?>"
                                          name="settings[<?= esc($key) ?>]"
                                          rows="4"><?= esc((string) $val) ?></textarea>

                            <?php elseif ($type === 'bool'): ?>
                                <!-- hidden to ensure key exists -->
                                <input type="hidden" name="settings[<?= esc($key) ?>]" value="0">
                                <div class="form-check">
                                    <input class="form-check-input <?= $err ? 'is-invalid' : '' ?>"
                                           type="checkbox"
                                           id="<?= esc($id) ?>"
                                           name="settings[<?= esc($key) ?>]"
                                           value="1"
                                           <?= ($val === true || $val === 1 || $val === '1') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= esc($id) ?>">Enabled</label>
                                    <?php if ($err): ?>
                                        <div class="invalid-feedback d-block"><?= esc((string) $err) ?></div>
                                    <?php endif; ?>
                                </div>

                            <?php elseif ($type === 'int'): ?>
                                <input class="form-control <?= $err ? 'is-invalid' : '' ?>"
                                       id="<?= esc($id) ?>"
                                       type="number"
                                       name="settings[<?= esc($key) ?>]"
                                       value="<?= esc((string) $val) ?>">

                            <?php elseif ($type === 'select'): ?>
                                <select class="form-select <?= $err ? 'is-invalid' : '' ?>"
                                        id="<?= esc($id) ?>"
                                        name="settings[<?= esc($key) ?>]">
                                    <?php foreach ($options as $optVal => $optLabel): ?>
                                        <option value="<?= esc((string) $optVal) ?>" <?= ((string) $val === (string) $optVal) ? 'selected' : '' ?>>
                                            <?= esc((string) $optLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php else: ?>
                                <input class="form-control <?= $err ? 'is-invalid' : '' ?>"
                                       id="<?= esc($id) ?>"
                                       type="text"
                                       name="settings[<?= esc($key) ?>]"
                                       value="<?= esc((string) $val) ?>">
                            <?php endif; ?>

                            <?php if ($type !== 'bool' && $err): ?>
                                <div class="invalid-feedback d-block"><?= esc((string) $err) ?></div>
                            <?php endif; ?>

                            <?php if ($help !== ''): ?>
                                <div class="form-text"><?= esc($help) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn btn-outline-secondary" href="<?= esc(site_url('admin/system/settings')) ?>">Reload</a>
        </div>

    </form>

</div>
