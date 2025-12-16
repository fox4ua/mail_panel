<?php

namespace Modules\System\Menu\Models;

use Modules\System\Menu\Models\MenuItemModel;

class InstallModel
{
    private const MENU_ITEM_KEY = 'system.menu';
    private const MODULE_NAME   = 'menu';

    public function addMenu(): bool
    {
        if (!class_exists(MenuItemModel::class)) {
            return false;
        }

        $m = new MenuItemModel();
        $existing = $m->where('item_key', self::MENU_ITEM_KEY)->first();

        $data = [
            'area'       => 'admin',
            'menu_key'   => 'sidebar',
            'group'      => 'system',
            'parent_id'  => null,
            'item_key'   => self::MENU_ITEM_KEY,
            'label'      => 'Menu',
            'icon'       => 'bi bi-list',
            'url'        => 'admin/system/menu',
            'weight'     => 5,
            'is_enabled' => 1,
            'is_system'  => 1,
            'module'     => self::MODULE_NAME,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!$existing) {
            $data['created_at'] = $data['updated_at'];
            return (bool)$m->insert($data);
        }

        // Preserve manual disable flag if admin turned it off
        $data['is_enabled'] = (int)($existing['is_enabled'] ?? 1);

        return (bool)$m->update((int)$existing['id'], $data);
    }

    public function delMenu(): bool
    {
        if (!class_exists(MenuItemModel::class)) {
            return true;
        }

        try {
            $m = new MenuItemModel();

            $row = $m->where('item_key', self::MENU_ITEM_KEY)->first();
            if ($row && isset($row['id'])) {
                return (bool)$m->delete((int)$row['id']);
            }

            $m->where(['module' => self::MODULE_NAME, 'is_system' => 1])->delete();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
