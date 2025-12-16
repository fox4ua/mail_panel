<?php

namespace Modules\System\Layout\Config;

use Modules\System\Core\Libraries\ModuleSupport\BaseModuleManifest;

class Module extends BaseModuleManifest
{
    public string $name    = 'layout';
    public string $title   = 'Layout/Themes';
    public string $version = '1.0.2';
    public int    $weight  = -50;

    /** @var string[] */
    public array $requires = ['system/core'];
}
