<?php
/** @var array $user */
/** @var array $profile */
/** @var string|null $success */
/** @var string|null $error */
/** @var array $errors */
?>
<div class="container-fluid py-3">

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= esc((string) $success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= esc((string) $error) ?></div>
    <?php endif; ?>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">My profile</h1>
        <a class="btn btn-outline-secondary" href="<?= esc(site_url('/')) ?>">Home</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">User ID</div>
                    <div><code><?= esc((string) ($user['id'] ?? '')) ?></code></div>
                </div>

                <?php if (isset($user['email'])): ?>
                    <div class="col-md-6">
                        <div class="text-muted small">Email</div>
                        <div><?= esc((string) $user['email']) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <hr>

            <form method="post" action="<?= esc(site_url('account/profile/save')) ?>">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">First name</label>
                        <input class="form-control" name="first_name" maxlength="120"
                               value="<?= esc((string) (old('first_name') ?? $profile['first_name'] ?? '')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Last name</label>
                        <input class="form-control" name="last_name" maxlength="120"
                               value="<?= esc((string) (old('last_name') ?? $profile['last_name'] ?? '')) ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Display name</label>
                        <input class="form-control" name="display_name" maxlength="160"
                               value="<?= esc((string) (old('display_name') ?? $profile['display_name'] ?? '')) ?>">
                        <div class="form-text">If empty, will be generated from First + Last name.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" rows="5" name="bio" maxlength="2000"><?= esc((string) (old('bio') ?? $profile['bio'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a class="btn btn-outline-secondary" href="<?= esc(site_url('account/profile')) ?>">Reload</a>
                </div>
            </form>

        </div>
    </div>
</div>
