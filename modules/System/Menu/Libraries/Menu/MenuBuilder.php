<?php

namespace Modules\System\Menu\Libraries\Menu;

class MenuBuilder
{
    public function build(string $area = 'admin', string $menuKey = 'sidebar'): array
    {
        try {
            $svc = new MenuService();
            return $svc->getEnabledForRender($area, $menuKey);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
