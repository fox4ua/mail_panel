<?php

declare(strict_types=1);

namespace Modules\System\Profile\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Profile module configuration.
 *
 * This module is designed to work with:
 * - users table: `user` (UUID or INT) — read-only here
 * - profile table: `user_profile`
 *
 * If your project uses different session keys, adjust Session keys below.
 */
class Profile extends BaseConfig
{
    /** User table name */
    public string $userTable = 'user';

    /** Profile table name */
    public string $profileTable = 'user_profile';

    /** Primary key field on users table */
    public string $userPrimaryKey = 'id';

    /** Field name referencing user on profile table */
    public string $profileUserKey = 'user_id';

    /** Session key(s) for current user id */
    public array $sessionUserIdKeys = ['user_id', 'id', 'uid'];

    /** Session key(s) for current user email (optional) */
    public array $sessionUserEmailKeys = ['email', 'user_email'];

    /** Session key(s) for current user display name (optional) */
    public array $sessionUserNameKeys = ['name', 'username', 'display_name'];
}
