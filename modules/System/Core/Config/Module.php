<?php

namespace Modules\System\Core\Config;

use Modules\System\Core\Libraries\ModuleSupport\BaseModuleManifest;

class Module extends BaseModuleManifest
{
    public string $name    = 'core';
    public string $title   = 'System Core';
    public string $version = '1.0.2';
    public int    $weight  = -100;

    /** @var string[] */
    public array $requires = [];
}
