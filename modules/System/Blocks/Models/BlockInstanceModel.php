<?php

namespace Modules\System\Blocks\Models;

use CodeIgniter\Model;

class BlockInstanceModel extends Model
{
    protected $table      = 'block_instances';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'type','title','settings_json','is_enabled','created_at','updated_at',
    ];

    protected $useTimestamps = false;
}
