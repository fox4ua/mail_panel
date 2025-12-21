<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries;

use Modules\System\Layout\Config\Render as RenderConfig;
use Modules\System\Layout\Libraries\Assets\AssetManager;
use Modules\System\Layout\Libraries\Modules\LayoutModuleManager;
use Modules\System\Layout\Libraries\Regions\RegionManager;
use Modules\System\Layout\Libraries\Views\PhpFileRenderer;
use Modules\System\Layout\Libraries\Views\ViewLocator;
use RuntimeException;

class Renderer
{
    protected ?string $controllerClass = null;
    protected ?string $controllerFile  = null;

    protected ?string $title = null;

    /** @var array<string,mixed> */
    protected array $shared = [];

    // Lazy singletons
    protected ?AssetManager $assets = null;
    protected ?ViewLocator $viewLocator = null;
    protected ?PhpFileRenderer $phpRenderer = null;
    protected ?LayoutModuleManager $modulesManager = null;
    protected ?RegionManager $regionsManager = null;

    public function __construct(protected RenderConfig $config) {}

    // -------------------------
    // Lazy getters (однотипно)
    // -------------------------

    protected function assets(): AssetManager
    {
        return $this->assets ??= new AssetManager($this->config);
    }

    protected function viewLocator(): ViewLocator
    {
        return $this->viewLocator ??= new ViewLocator($this->config);
    }

    protected function phpRenderer(): PhpFileRenderer
    {
        return $this->phpRenderer ??= new PhpFileRenderer();
    }

    protected function regions(): RegionManager
    {
        return $this->regionsManager ??= new RegionManager($this, $this->config);
    }

    public function modules(): LayoutModuleManager
    {
        return $this->modulesManager ??= new LayoutModuleManager($this, $this->config);
    }

    // -------------------------
    // Public API: Modules
    // -------------------------

    public function module(string $name, array $data = []): string
    {
        return $this->modules()->render($name, $data);
    }

    public function renderModuleView(string $category, string $module, string $view, array $data = []): string
    {
        static $existsCache = [];

        $file = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . $category
            . DIRECTORY_SEPARATOR . $module
            . DIRECTORY_SEPARATOR . 'Views'
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!isset($existsCache[$file])) {
            $existsCache[$file] = is_file($file);
        }
        if ($existsCache[$file] !== true) {
            throw new RuntimeException('Module view not found: ' . $category . '/' . $module . ':' . $view);
        }

        $vars = array_merge($this->shared, $data);
        $vars['render'] = $this;

        return $this->phpRenderer()->render($file, $vars);
    }

    // -------------------------
    // Public API: Regions
    // -------------------------

    public function addModule(string $region, string $name, array $data = []): self
    {
        $this->regions()->add($region, $name, $data);
        return $this;
    }

    public function region(string $region): string
    {
        return $this->regions()->render($region);
    }

    // -------------------------
    // Title / Controller
    // -------------------------

    public function addTitle(string $title, bool $append = false, string $separator = ' — '): self
    {
        $title = trim($title);
        if ($title === '') {
            return $this;
        }

        if ($append && $this->title !== null && $this->title !== '') {
            $this->title = $this->title . $separator . $title;
        } else {
            $this->title = $title;
        }

        return $this;
    }

    public function getTitle(?string $fallback = null): string
    {
        $t = trim((string) $this->title);
        if ($t !== '') {
            return $t;
        }

        $fallback = $fallback ?? ($this->shared['title'] ?? null);
        $fallback = is_string($fallback) ? trim($fallback) : '';

        return $fallback !== '' ? $fallback : 'App';
    }

    public function setController(object|string $controller): self
    {
        $this->controllerClass = is_object($controller) ? get_class($controller) : $controller;

        try {
            $ref = new \ReflectionClass($this->controllerClass);
            $this->controllerFile = $ref->getFileName() ?: null;
        } catch (\Throwable) {
            $this->controllerFile = null;
        }

        $this->viewLocator()->setControllerFile($this->controllerFile);

        return $this;
    }

    public function share(string $key, mixed $value): self
    {
        $this->shared[$key] = $value;
        return $this;
    }

    // -------------------------
    // Assets
    // -------------------------

    public function addCss(string $href, array $attrs = []): self
    {
        $this->assets()->addCss($href, $attrs);
        return $this;
    }

    public function addJs(string $src, string $where = 'body', array $attrs = []): self
    {
        $this->assets()->addJs($src, $where, $attrs);
        return $this;
    }

    public function renderCss(): string
    {
        return $this->assets()->renderCss();
    }

    public function renderJs(string $where = 'body'): string
    {
        return $this->assets()->renderJs($where);
    }

    // -------------------------
    // View
    // -------------------------

    public function view(string $view, array $data = [], string|null $layoutFile = null): string
    {
        // дефолтные регионы поднимаем автоматически (без участия контроллеров страниц)
        $this->regions()->bootstrapDefaults();

        $vars = array_merge($this->shared, $data);
        $vars['render'] = $this;

        $contentFile = $this->viewLocator()->resolve($view);
        $content     = $this->phpRenderer()->render($contentFile, $vars);

        $layoutFile = ($layoutFile === null) ? $this->config->layoutFile : $layoutFile;
        if ($layoutFile === '') {
            return $content;
        }

        $vars['content'] = $content;

        return $this->phpRenderer()->render($layoutFile, $vars);
    }
}
