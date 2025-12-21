<?php
/**
 * Block view: blocks/UserProfile:header
 *
 * Variables:
 * - $is_logged_in (bool)
 * - $user (array|null)
 * - $profile (array|null)
 * - $display (string|null)
 * - $options (array)
 */
$isLogged = !empty($is_logged_in);
$opts = $options ?? [];
$profileUrl = (string) ($opts['profile_url'] ?? site_url('account/profile'));
$logoutUrl  = (string) ($opts['logout_url'] ?? site_url('logout'));
$loginUrl   = (string) ($opts['login_url'] ?? site_url('login'));
$dropdown   = (bool) ($opts['dropdown'] ?? true);
?>
<div class="d-flex align-items-center gap-2">
    <?php if (!$isLogged): ?>
        <a class="btn btn-sm btn-outline-light" href="<?= esc($loginUrl) ?>">Login</a>
    <?php else: ?>
        <?php if ($dropdown): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= esc((string) ($display ?? 'Profile')) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?= esc($profileUrl) ?>">My profile</a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= esc($logoutUrl) ?>">Logout</a>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <a class="btn btn-sm btn-outline-light" href="<?= esc($profileUrl) ?>">
                <?= esc((string) ($display ?? 'Profile')) ?>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</div>
