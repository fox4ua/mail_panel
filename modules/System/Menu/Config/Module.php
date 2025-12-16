<?php

namespace Modules\System\Menu\Config;

use Modules\System\Core\Libraries\ModuleSupport\BaseModuleManifest;
use Modules\System\Menu\Libraries\Menu\MenuInstaller;

class Module extends BaseModuleManifest
{
    public string $name    = 'menu';
    public string $title   = 'Menu';
    public string $version = '1.1.1';
    public int    $weight  = -40;

    /** @var string[] */
    public array $requires = ['system/layout', 'system/core'];

    public function menu(): array
    {
        return [
            [
                'key'      => 'system.menu',
                'area'     => 'admin',
                'menu_key' => 'sidebar',
                'group'    => 'system',
                'icon'     => 'bi bi-list',
                'label'    => 'Menu',
                'url'      => 'admin/system/menu',
                'weight'   => 5,
            ],
        ];
    }

    public function install(): bool
    {
        return (new MenuInstaller())->install();
    }

    public function uninstall(): bool
    {
        return (new MenuInstaller())->uninstall();
    }
}
