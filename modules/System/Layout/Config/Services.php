<?php
declare(strict_types=1);

namespace Modules\System\Layout\Config;

use CodeIgniter\Config\BaseService;
use Modules\System\Layout\Libraries\Renderer;

class Services extends BaseService
{
    public static function render(bool $getShared = true): Renderer
    {
        if ($getShared) {
            return static::getSharedInstance('render');
        }

        return new Renderer(config(Render::class));
    }
}
