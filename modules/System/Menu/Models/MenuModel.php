<?php

declare(strict_types=1);

namespace Modules\System\Menu\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table            = 'menus';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'menu_key',
        'title',
        'description',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'menu_key'     => 'required|max_length[64]|regex_match[/^[a-zA-Z0-9._-]+$/]',
        'title'        => 'required|max_length[120]',
        'description'  => 'permit_empty|max_length[255]',
        'is_active'    => 'required|in_list[0,1]',
    ];

    public function findByKey(string $menuKey): ?array
    {
        $menuKey = trim($menuKey);
        if ($menuKey === '') {
            return null;
        }
        return $this->where('menu_key', $menuKey)->first();
    }

    public function listAll(): array
    {
        return $this->orderBy('id', 'DESC')->findAll();
    }
}
