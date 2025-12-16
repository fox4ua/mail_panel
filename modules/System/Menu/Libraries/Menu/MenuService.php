<?php

namespace Modules\System\Menu\Libraries\Menu;

use Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry;
use Modules\System\Menu\Models\MenuItemModel;

class MenuService
{
    private MenuItemModel $model;

    public function __construct()
    {
        $this->model = new MenuItemModel();
        $this->ensureTable();
    }

    public function list(string $area = 'admin', string $menuKey = 'sidebar'): array
    {
        $this->syncSystemItemsFromManifests($area, $menuKey);

        return $this->model
            ->where(['area' => $area, 'menu_key' => $menuKey])
            ->orderBy('weight', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function getEnabledForRender(string $area = 'admin', string $menuKey = 'sidebar'): array
    {
        $this->syncSystemItemsFromManifests($area, $menuKey);

        $items = $this->model
            ->where(['area' => $area, 'menu_key' => $menuKey, 'is_enabled' => 1])
            ->orderBy('weight', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $enabledMap = null;
        try {
            if (class_exists('Modules\\System\\ModuleCenter\\Models\\ModuleModel')) {
                $mm = new \Modules\System\ModuleCenter\Models\ModuleModel();
                $rows = $mm->findAll();
                $enabledMap = [];
                foreach ($rows as $r) {
                    $enabledMap[mb_strtolower($r['name'])] = (int)$r['is_enabled'] === 1;
                }
            }
        } catch (\Throwable $e) {
            $enabledMap = null;
        }

// Map module => category (system/pages/blocks) from filesystem
$categoryMap = [];
try {
    $registryTmp = new ModuleRegistry();
    foreach ($registryTmp->all() as $mi) {
        $categoryMap[mb_strtolower($mi->module)] = mb_strtolower((string)$mi->category);
    }
} catch (\Throwable $e) {
    $categoryMap = [];
}


        $out = [];
        foreach ($items as $i) {
            if ((int)$i['is_system'] === 1 && !empty($i['module'])) {
    $mn  = mb_strtolower($i['module']);
    $cat = $categoryMap[$mn] ?? null;

    // System-category modules: show unless explicitly disabled
    if ($cat === 'system') {
        if (is_array($enabledMap) && array_key_exists($mn, $enabledMap) && $enabledMap[$mn] === false) {
            continue;
        }
    } else {
        // Pages/Blocks modules: show only if installed+enabled (when ModuleCenter is available)
        if (is_array($enabledMap)) {
            if (!array_key_exists($mn, $enabledMap) || $enabledMap[$mn] === false) {
                continue;
            }
        }
    }
}

            $out[] = [
                'label' => $i['label'],
                'icon'  => $i['icon'],
                'url'   => site_url($i['url'] ?? '#'),
                'key'   => $i['item_key'],
            ];
        }

        return $out;
    }

    public function create(array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['is_system']  = 0;
        $data['module']     = null;

        return (bool)$this->model->insert($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        unset($data['is_system'], $data['module'], $data['item_key']);
        return (bool)$this->model->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $row = $this->model->find($id);
        if (!$row) return false;
        if ((int)$row['is_system'] === 1) return false;
        return (bool)$this->model->delete($id);
    }

    public function toggle(int $id): bool
    {
        $row = $this->model->find($id);
        if (!$row) return false;

        $val = ((int)$row['is_enabled'] === 1) ? 0 : 1;
        return (bool)$this->model->update($id, [
            'is_enabled' => $val,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function syncSystemItemsFromManifests(string $area = 'admin', string $menuKey = 'sidebar'): void
    {
        $registry = new ModuleRegistry();

        foreach ($registry->all() as $info) {
            $manifest = $registry->manifest($info);
            if (!method_exists($manifest, 'menu')) continue;

            $moduleName = property_exists($manifest, 'name') ? (string)$manifest->name : mb_strtolower($info->module);

            foreach ((array)$manifest->menu() as $item) {
                $iArea = (string)($item['area'] ?? 'admin');
                $iMenu = (string)($item['menu_key'] ?? 'sidebar');
                if ($iArea !== $area || $iMenu !== $menuKey) continue;

                $key = (string)($item['key'] ?? ($moduleName . '.' . $this->slug($item['label'] ?? $info->module)));
                $label = (string)($item['label'] ?? $key);
                $icon = $item['icon'] ?? null;
                $url  = $item['url'] ?? ($item['route'] ?? null);
                $weight = (int)($item['weight'] ?? 0);

                $existing = $this->model->where('item_key', $key)->first();

                $data = [
                    'menu_key' => $iMenu,
                    'area' => $iArea,
                    'parent_id' => null,
                    'item_key' => $key,
                    'label' => $label,
                    'icon' => $icon,
                    'url' => $url,
                    'weight' => $weight,
                    'is_enabled' => 1,
                    'is_system' => 1,
                    'module' => $moduleName,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if (!$existing) {
                    $data['created_at'] = $data['updated_at'];
                    $this->model->insert($data);
                } else {
                    $data['is_enabled'] = (int)$existing['is_enabled'];
                    $this->model->update((int)$existing['id'], $data);
                }
            }
        }
    }

    private function ensureTable(): void
    {
        try { (new MenuInstaller())->install(); } catch (\Throwable $e) {}
    }

    private function slug(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9\p{L}]+/u', '-', $s);
        $s = trim($s, '-');
        return $s === '' ? 'item' : $s;
    }
}
