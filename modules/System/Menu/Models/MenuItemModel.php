<?php

namespace Modules\System\Menu\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table      = 'menu_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'menu_key','area','parent_id','item_key','label','icon','url','weight',
        'is_enabled','is_system','module','created_at','updated_at',
    ];

    protected $useTimestamps = false;
}
