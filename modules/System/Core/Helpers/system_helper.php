<?php

use Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry;

if (! function_exists('system_modules')) {
    function system_modules(): ModuleRegistry
    {
        static $registry;
        if (! $registry) $registry = new ModuleRegistry();
        return $registry;
    }
}
