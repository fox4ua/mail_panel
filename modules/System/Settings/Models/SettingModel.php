<?php

declare(strict_types=1);

namespace Modules\System\Settings\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'group_name',
        'setting_key',
        'setting_value',
        'type',
        'autoload',
        'description',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'group_name'    => 'permit_empty|max_length[64]|regex_match[/^[a-zA-Z0-9._-]+$/]',
        'setting_key'   => 'required|max_length[191]|regex_match[/^[a-zA-Z0-9._-]+$/]',
        'type'          => 'required|in_list[string,int,bool,float,json,text,select]',
        'autoload'      => 'required|in_list[0,1]',
        'description'   => 'permit_empty|max_length[255]',
        'setting_value' => 'permit_empty',
    ];

    public function findByKey(string $key): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        return $this->where('setting_key', $key)->first();
    }

    public function upsertByKey(
        string $key,
        ?string $value,
        string $type = 'string',
        string $group = 'general',
        int $autoload = 1,
        ?string $description = null
    ): int {
        $key   = trim($key);
        $group = trim($group) !== '' ? trim($group) : 'general';

        $existing = $this->findByKey($key);

        $data = [
            'group_name'    => $group,
            'setting_key'   => $key,
            'setting_value' => $value,
            'type'          => $type,
            'autoload'      => $autoload,
            'description'   => $description,
        ];

        if ($existing) {
            $ok = $this->update((int) $existing['id'], $data);
            if ($ok === false) {
                throw new \RuntimeException('Failed to update setting: ' . $key);
            }
            return (int) $existing['id'];
        }

        $id = $this->insert($data, true);
        if (!$id) {
            throw new \RuntimeException('Failed to create setting: ' . $key);
        }

        return (int) $id;
    }

    public function deleteByKey(string $key): bool
    {
        $existing = $this->findByKey($key);
        if (!$existing) {
            return true;
        }

        return (bool) $this->delete((int) $existing['id']);
    }

    public function listAutoload(): array
    {
        return $this->where('autoload', 1)
            ->orderBy('group_name', 'ASC')
            ->orderBy('setting_key', 'ASC')
            ->findAll();
    }
}
