<?php

declare(strict_types=1);

namespace Modules\Blocks\UserProfile\Libraries;

use Modules\System\Profile\Libraries\CurrentUser;
use Modules\System\Profile\Libraries\ProfileService;

/**
 * Provides data for header block.
 */
class UserProfileHeaderBlock
{
    public function build(array $options = []): array
    {
        $current = new CurrentUser();
        $uid = $current->id();

        $defaults = [
            'profile_url' => site_url('account/profile'),
            'logout_url'  => site_url('logout'),
            'login_url'   => site_url('login'),
            'dropdown'    => true,
        ];
        $options = array_merge($defaults, $options);

        if (!$uid) {
            return [
                'is_logged_in' => false,
                'user'         => null,
                'profile'      => null,
                'display'      => null,
                'options'      => $options,
            ];
        }

        $svc = new ProfileService();

        try {
            [$user, $profile] = $svc->getOrCreate($uid);
        } catch (\Throwable $e) {
            // degrade gracefully in header
            return [
                'is_logged_in' => true,
                'user'         => ['id' => $uid, 'email' => $current->email(), 'name' => $current->name()],
                'profile'      => null,
                'display'      => $current->name() ?? $current->email() ?? ('User ' . $uid),
                'options'      => $options,
                'error'        => $e->getMessage(),
            ];
        }

        $display = (string) ($profile['display_name'] ?? '');
        if (trim($display) === '') {
            $fn = trim((string) ($profile['first_name'] ?? ''));
            $ln = trim((string) ($profile['last_name'] ?? ''));
            $display = trim($fn . ' ' . $ln);
        }
        if (trim($display) === '') {
            $display = (string) ($user['email'] ?? ('User ' . $uid));
        }

        return [
            'is_logged_in' => true,
            'user'         => $user,
            'profile'      => $profile,
            'display'      => $display,
            'options'      => $options,
        ];
    }
}
