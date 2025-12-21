<?php
/**
 * Block view: blocks/Menu:index
 *
 * Expected variables:
 * - $menu (array|null)
 * - $tree (array)
 * - $options (array)
 */
if (!isset($options) || !is_array($options)) {
    $options = [];
}

$ulClass = (string) ($options['ul_class'] ?? 'nav flex-column');
$liClass = (string) ($options['li_class'] ?? 'nav-item');
$aClass  = (string) ($options['a_class'] ?? 'nav-link');
$activeClass = (string) ($options['active_class'] ?? 'active');
$maxDepth = (int) ($options['max_depth'] ?? 10);

$uri = service('uri');
$currentPath = '/' . ltrim($uri->getPath() ?? '', '/');

$makeUrl = function(array $item): string {
    $route = trim((string) ($item['route_name'] ?? ''));
    if ($route !== '') {
        try {
            return route_to($route);
        } catch (Throwable $e) {
            // fallback
        }
    }
    $url = trim((string) ($item['url'] ?? ''));
    if ($url === '') {
        return '#';
    }
    // allow relative or absolute, do not modify
    return $url;
};

$parseAttrs = function(?string $json): array {
    if (!$json) return [];
    $j = trim($json);
    if ($j === '') return [];
    $arr = json_decode($j, true);
    if (!is_array($arr)) return [];
    return $arr;
};

$renderList = function(array $nodes, int $depth = 0) use (&$renderList, $ulClass, $liClass, $aClass, $activeClass, $maxDepth, $currentPath, $makeUrl, $parseAttrs): string {
    if ($depth > $maxDepth) return '';

    $html = '<ul class="' . esc($ulClass) . '">';
    foreach ($nodes as $n) {
        $url = $makeUrl($n);
        $isActive = false;

        // active detection for relative URLs
        if (is_string($url) && strlen($url) > 0 && $url[0] === '/') {
            $isActive = ($url === $currentPath) || (rtrim($url,'/') !== '' && str_starts_with($currentPath, rtrim($url,'/') . '/'));
        }

        $linkClass = trim($aClass . ' ' . ($isActive ? $activeClass : ''));

        $attrs = $parseAttrs($n['attrs_json'] ?? null);
        $attrsStr = '';
        foreach ($attrs as $k => $v) {
            if (!is_scalar($v)) continue;
            $k = (string) $k;
            $v = (string) $v;
            if ($k === '' || preg_match('~[^a-zA-Z0-9:_-]~', $k)) continue;
            $attrsStr .= ' ' . esc($k) . '="' . esc($v) . '"';
        }

        $target = trim((string) ($n['target'] ?? ''));
        if ($target !== '') {
            $attrsStr .= ' target="' . esc($target) . '"';
        }

        $icon = trim((string) ($n['icon'] ?? ''));
        $iconHtml = $icon !== '' ? '<i class="' . esc($icon) . ' me-2"></i>' : '';

        $itemClass = trim($liClass . ' ' . ((string) ($n['css_class'] ?? '')));

        $html .= '<li class="' . esc($itemClass) . '">';
        $html .= '<a class="' . esc($linkClass) . '" href="' . esc($url) . '"' . $attrsStr . '>';
        $html .= $iconHtml . esc((string) ($n['title'] ?? ''));
        $html .= '</a>';

        $children = $n['children'] ?? [];
        if (is_array($children) && !empty($children)) {
            $html .= $renderList($children, $depth + 1);
        }

        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
};

if (!empty($tree)) {
    echo $renderList($tree, 0);
}
