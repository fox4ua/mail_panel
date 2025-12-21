<?php

declare(strict_types=1);

namespace Modules\System\Profile\Models;

use CodeIgniter\Model;
use Modules\System\Profile\Config\Profile as ProfileConfig;

class UserModel extends Model
{
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public function __construct(?ProfileConfig $cfg = null)
    {
        parent::__construct();
        $cfg = $cfg ?? config(ProfileConfig::class);

        $this->table      = $cfg->userTable;
        $this->primaryKey = $cfg->userPrimaryKey;

        // read-only in this module
        $this->allowedFields = [];
    }

    public function findUser(string $userId): ?array
    {
        $userId = trim($userId);
        if ($userId === '') {
            return null;
        }
        return $this->where($this->primaryKey, $userId)->first();
    }
}
