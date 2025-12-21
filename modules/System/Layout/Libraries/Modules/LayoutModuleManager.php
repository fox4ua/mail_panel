<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries\Modules;

use Modules\System\Layout\Config\Render as RenderConfig;
use Modules\System\Layout\Libraries\Renderer;
use RuntimeException;

class LayoutModuleManager
{
    /** @var array<string, array{0:string,1:string,2:string}> */
    protected array $parseCache = [];

    public function __construct(protected Renderer $renderer, protected RenderConfig $config) {}

    public function render(string $name, array $data = []): string {
      [$category, $module, $view] = $this->parseNameCached($name);
      return $this->renderer->renderModuleView($category, $module, $view, $data);
    }

    protected function parseNameCached(string $name): array {
      $key = $name;
      if (isset($this->parseCache[$key])) {
        return $this->parseCache[$key];
      }
      return $this->parseCache[$key] = $this->parseName($name);
    }

    /**
     * Поддерживаемые форматы:
     *  - "menu"              => Blocks/Menu/index
     *  - "profile"           => Blocks/Profile/index
     *  - "blocks/menu"       => Blocks/Menu/index
     *  - "Blocks/Menu"       => Blocks/Menu/index
     *  - "blocks/menu:top"   => Blocks/Menu/top
     *  - "Blocks/Menu:menu"  => Blocks/Menu/menu
     *
     * category по умолчанию: Blocks
     * view по умолчанию: index
     */
    protected function parseName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('Module name is empty');
        }

        // view suffix: "x:y"
        $view = 'index';
        if (str_contains($name, ':')) {
            [$left, $v] = explode(':', $name, 2);
            $name = trim($left);
            $v = trim($v);
            if ($v !== '') {
                $view = $v;
            }
        }

        $name = str_replace('\\', '/', $name);
        $name = trim($name, '/');

        // allowlist
        if (!preg_match('~^[A-Za-z0-9/_-]+$~', $name)) {
            throw new RuntimeException('Invalid module name: ' . $name);
        }

        $parts = explode('/', $name);

        if (count($parts) === 1) {
            // ВАЖНО: у тебя реальная папка "Blocks", а не "blocks"
            $category = 'Blocks';
            $module   = $parts[0];
        } elseif (count($parts) === 2) {
            // ВАЖНО: не strtolower(), иначе сломаешь Linux-пути
            $category = $this->studly($parts[0]); // blocks => Blocks, pages => Pages, system => System
            $module   = $parts[1];
        } else {
            throw new RuntimeException('Invalid module path (expected "module" or "category/module"): ' . $name);
        }

        $module = $this->studly($module);

        // view sanitize
        $view = trim($view);
        $view = str_replace('\\', '/', $view);
        $view = trim($view, '/');
        if ($view === '') {
            $view = 'index';
        }
        if (!preg_match('~^[A-Za-z0-9/_-]+$~', $view)) {
            throw new RuntimeException('Invalid module view: ' . $view);
        }

        return [$category, $module, $view];
    }

    protected function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords(strtolower($value));
        return str_replace(' ', '', $value);
    }
}
