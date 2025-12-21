<?php

declare(strict_types=1);

namespace Modules\Blocks\Menu\Libraries;

use Modules\System\Menu\Libraries\MenuTree;
use Modules\System\Menu\Models\MenuItemModel;
use Modules\System\Menu\Models\MenuModel;

class MenuBlock
{
    /**
     * Prepare data for block view.
     *
     * @param string $menuKey menu key (default 'main')
     * @param array $options  options: ul_class, li_class, a_class, active_class, max_depth
     */
    public function build(string $menuKey = 'main', array $options = []): array
    {
        $menuModel = new MenuModel();
        $menu = $menuModel->where('menu_key', $menuKey)->where('is_active', 1)->first();

        if (!$menu) {
            return [
                'menu' => null,
                'tree' => [],
                'options' => $this->defaults($options),
            ];
        }

        $itemModel = new MenuItemModel();
        $items = $itemModel->where('menu_id', (int) $menu['id'])
            ->where('is_active', 1)
            ->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $tree = MenuTree::build($items);

        return [
            'menu' => $menu,
            'tree' => $tree,
            'options' => $this->defaults($options),
        ];
    }

    private function defaults(array $o): array
    {
        return array_merge([
            'ul_class'     => 'nav flex-column',
            'li_class'     => 'nav-item',
            'a_class'      => 'nav-link',
            'active_class' => 'active',
            'max_depth'    => 10,
        ], $o);
    }
}
