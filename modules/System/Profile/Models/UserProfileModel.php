<?php

declare(strict_types=1);

namespace Modules\System\Profile\Models;

use CodeIgniter\Model;
use Modules\System\Profile\Config\Profile as ProfileConfig;

class UserProfileModel extends Model
{
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id'       => 'required|max_length[64]',
        'first_name'    => 'permit_empty|max_length[120]',
        'last_name'     => 'permit_empty|max_length[120]',
        'display_name'  => 'permit_empty|max_length[160]',
        'bio'           => 'permit_empty|max_length[2000]',
    ];

    public function __construct(?ProfileConfig $cfg = null)
    {
        parent::__construct();
        $cfg = $cfg ?? config(ProfileConfig::class);

        $this->table      = $cfg->profileTable;
        $this->primaryKey = 'id';

        $this->allowedFields = [
            $cfg->profileUserKey,
            'first_name',
            'last_name',
            'display_name',
            'bio',
        ];
    }

    public function findByUserId(string $userId): ?array
    {
        $cfg = config(ProfileConfig::class);
        $field = $cfg->profileUserKey;

        return $this->where($field, $userId)->first();
    }
}
