<?php

namespace Modules\System\ModuleCenter\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table      = 'modules';
    protected $primaryKey = 'name';
    protected $returnType = 'array';

    protected $allowedFields = [
        'name','title','version','is_enabled','installed_at','updated_at',
    ];

    protected $useTimestamps = false;
}
