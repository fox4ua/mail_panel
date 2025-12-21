<?php

declare(strict_types=1);

namespace Modules\System\Profile\Libraries;

use Modules\System\Profile\Models\UserModel;
use Modules\System\Profile\Models\UserProfileModel;

/**
 * ProfileService encapsulates profile read/update logic.
 * Uses CI4 Model methods only. No manual transactions.
 */
class ProfileService
{
    private UserModel $users;
    private UserProfileModel $profiles;

    public function __construct(?UserModel $users = null, ?UserProfileModel $profiles = null)
    {
        $this->users    = $users ?? new UserModel();
        $this->profiles = $profiles ?? new UserProfileModel();
    }

    /**
     * Returns [user, profile] and ensures profile row exists.
     */
    public function getOrCreate(string $userId): array
    {
        $user = $this->users->findUser($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $profile = $this->profiles->findByUserId($userId);

        if (!$profile) {
            $cfg = config(\Modules\System\Profile\Config\Profile::class);
            $data = [
                $cfg->profileUserKey => $userId,
                'first_name'   => '',
                'last_name'    => '',
                'display_name' => '',
                'bio'          => '',
            ];

            $id = $this->profiles->insert($data, true);
            if (!$id) {
                throw new \RuntimeException('Failed to create profile');
            }

            $profile = $this->profiles->find((int) $id);
        }

        return [$user, $profile];
    }

    public function update(string $userId, array $data): void
    {
        $profile = $this->profiles->findByUserId($userId);
        if (!$profile) {
            // create if missing
            $this->getOrCreate($userId);
            $profile = $this->profiles->findByUserId($userId);
        }

        $id = (int) ($profile['id'] ?? 0);
        if ($id <= 0) {
            throw new \RuntimeException('Profile id invalid');
        }

        $allowed = [
            'first_name',
            'last_name',
            'display_name',
            'bio',
        ];

        $payload = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $payload[$k] = is_string($data[$k]) ? trim($data[$k]) : $data[$k];
            }
        }

        // normalize display_name fallback
        if (isset($payload['display_name']) && trim((string) $payload['display_name']) === '') {
            $fn = trim((string) ($payload['first_name'] ?? $profile['first_name'] ?? ''));
            $ln = trim((string) ($payload['last_name'] ?? $profile['last_name'] ?? ''));
            $payload['display_name'] = trim($fn . ' ' . $ln);
        }

        $ok = $this->profiles->update($id, $payload);
        if ($ok === false) {
            $errs = $this->profiles->errors();
            $msg  = $errs ? implode('; ', $errs) : 'Failed to update profile';
            throw new \RuntimeException($msg);
        }
    }
}
