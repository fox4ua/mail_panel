<?php

namespace Modules\System\Blocks\Models;

use CodeIgniter\Model;

class BlockPlacementModel extends Model
{
    protected $table      = 'block_placements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'instance_id','area','theme','region','weight','conditions_json','is_enabled',
    ];

    protected $useTimestamps = false;
}
