<?php

declare(strict_types=1);

namespace Modules\System\Menu\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table            = 'menu_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'route_name',
        'icon',
        'target',
        'css_class',
        'attrs_json',
        'sort_order',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'menu_id'     => 'required|is_natural_no_zero',
        'parent_id'   => 'permit_empty|is_natural_no_zero',
        'title'       => 'required|max_length[120]',
        'url'         => 'permit_empty|max_length[255]',
        'route_name'  => 'permit_empty|max_length[120]|regex_match[/^[a-zA-Z0-9._-]+$/]',
        'icon'        => 'permit_empty|max_length[80]',
        'target'      => 'permit_empty|max_length[20]',
        'css_class'   => 'permit_empty|max_length[120]',
        'attrs_json'  => 'permit_empty',
        'sort_order'  => 'required|integer',
        'is_active'   => 'required|in_list[0,1]',
    ];

    public function listByMenu(int $menuId): array
    {
        return $this->where('menu_id', $menuId)
            ->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function listParents(int $menuId): array
    {
        // for parent dropdown
        return $this->select('id,title,parent_id')
            ->where('menu_id', $menuId)
            ->orderBy('title', 'ASC')
            ->findAll();
    }

    public function belongsToMenu(int $itemId, int $menuId): bool
    {
        return (bool) $this->where('id', $itemId)->where('menu_id', $menuId)->first();
    }
}
