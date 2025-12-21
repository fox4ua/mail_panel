<?php

declare(strict_types=1);

namespace Modules\System\Menu\Libraries;

class MenuTree
{
    /**
     * Build a tree from flat menu_items list.
     *
     * Input item fields: id, parent_id, title, url, route_name, icon, target, css_class, attrs_json, sort_order, is_active
     * Output: each item gets 'children' => []
     */
    public static function build(array $items): array
    {
        $byId = [];
        foreach ($items as $it) {
            $it['children'] = [];
            $byId[(int) ($it['id'] ?? 0)] = $it;
        }

        $tree = [];
        foreach ($byId as $id => $it) {
            $pid = $it['parent_id'] ?? null;
            $pid = $pid !== null ? (int) $pid : null;

            if ($pid && isset($byId[$pid])) {
                $byId[$pid]['children'][] = &$byId[$id];
            } else {
                $tree[] = &$byId[$id];
            }
        }
        unset($it);

        return $tree;
    }

    /**
     * Flatten tree for admin display with depth.
     */
    public static function flatten(array $tree, int $depth = 0): array
    {
        $out = [];
        foreach ($tree as $node) {
            $n = $node;
            $children = $n['children'] ?? [];
            unset($n['children']);
            $n['_depth'] = $depth;
            $out[] = $n;

            if (!empty($children)) {
                $out = array_merge($out, self::flatten($children, $depth + 1));
            }
        }
        return $out;
    }
}
