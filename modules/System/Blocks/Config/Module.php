<?php

namespace Modules\System\Blocks\Config;

use Modules\System\Core\Libraries\ModuleSupport\BaseModuleManifest;
use Modules\System\Blocks\Libraries\Blocks\BlocksInstaller;

class Module extends BaseModuleManifest
{
    public string $name    = 'blocks';
    public string $title   = 'Blocks';
    public string $version = '1.0.2';
    public int    $weight  = 10;

    public function menu(): array
    {
        return [
            [
                'key'      => 'system.blocks',
                'area'     => 'admin',
                'menu_key' => 'sidebar',
                'group'    => 'system',
                'icon'     => 'bi bi-layout-text-sidebar',
                'label'    => 'Blocks',
                'url'      => 'admin/system/blocks',
                'weight'   => 10,
            ],
        ];
    }

    public function install(): bool { return (new BlocksInstaller())->install(); }
    public function uninstall(): bool { return (new BlocksInstaller())->uninstall(); }
}
