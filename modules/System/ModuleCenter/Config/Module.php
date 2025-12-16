<?php

namespace Modules\System\ModuleCenter\Config;

use Modules\System\Core\Libraries\ModuleSupport\BaseModuleManifest;
use Modules\System\ModuleCenter\Libraries\ModuleCenter\ModuleInstaller;

class Module extends BaseModuleManifest
{
    public string $name    = 'modulecenter';
    public string $title   = 'Module Center';
    public string $version = '1.1.0';
    public int    $weight  = 0;

    /** @var string[] */
    public array $requires = ['system/menu', 'system/layout', 'system/core'];

    public function menu(): array
    {
        return [
            [
                'key'      => 'system.modules',
                'area'     => 'admin',
                'menu_key' => 'sidebar',
                'group'    => 'system',
                'icon'     => 'bi bi-boxes',
                'label'    => 'Modules',
                'url'      => 'admin/system/modules',
                'weight'   => 0,
            ],
        ];
    }

    public function install(): bool
    {
        return (new ModuleInstaller())->installSelf();
    }

    public function uninstall(): bool
    {
        return (new ModuleInstaller())->uninstallSelf();
    }
}
