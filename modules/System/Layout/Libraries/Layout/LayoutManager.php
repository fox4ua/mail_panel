<?php

namespace Modules\System\Layout\Libraries\Layout;

use Modules\System\Menu\Libraries\Menu\MenuBuilder;

class LayoutManager
{
    public function render(string $view, array $data = [], array $options = []): string
    {
        $area   = $options['area']   ?? 'admin';
        $layout = $options['layout'] ?? $this->defaultLayout($area);

        $content = view($view, $data);

        $menu = [];
        if (class_exists(MenuBuilder::class)) {
            $menu = (new MenuBuilder())->build($area, 'sidebar');
        }

        $blocks = null;
        $blockRendererClass = 'Modules\\System\\Blocks\\Libraries\\Blocks\\BlockRenderer';
        if (class_exists($blockRendererClass)) {
            $blocks = new $blockRendererClass();
        }

        return view($layout, array_merge($data, [
            'area'    => $area,
            'menu'    => $menu,
            'blocks'  => $blocks,
            'content' => $content,
        ]));
    }

    private function defaultLayout(string $area): string
    {
        return match($area) {
            'admin'   => 'Modules\\System\\Layout\\Views\\layouts\\admin',
            'cabinet' => 'Modules\\System\\Layout\\Views\\layouts\\cabinet',
            default   => 'Modules\\System\\Layout\\Views\\layouts\\site',
        };
    }
}
