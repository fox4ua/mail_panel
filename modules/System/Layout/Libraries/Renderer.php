<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries;

use Modules\System\Layout\Config\Render as RenderConfig;
use Modules\System\Layout\Libraries\Assets\AssetManager;
use Modules\System\Layout\Libraries\Modules\LayoutModuleManager;
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

    protected ?LayoutModuleManager $modulesManager = null;
    protected AssetManager $assets;
    protected ViewLocator $viewLocator;
    protected PhpFileRenderer $phpRenderer;


    public function __construct(protected RenderConfig $config)
    {
        $this->assets      = new AssetManager($config);
        $this->viewLocator = new ViewLocator($config);
        $this->phpRenderer = new PhpFileRenderer();
    }

    public function module(string $name, array $data = []): string
    {
        return $this->modules()->render($name, $data);
    }

    public function modules(): LayoutModuleManager
    {
        return $this->modulesManager ??= new LayoutModuleManager($this, $this->config);
    }

    public function renderModuleView(string $category, string $module, string $view, array $data = []): string
    {
        $file = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . $category
            . DIRECTORY_SEPARATOR . $module
            . DIRECTORY_SEPARATOR . 'Views'
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('Module view not found: ' . $category . '/' . $module . ':' . $view);
        }

        $vars = array_merge($this->shared, $data);
        $vars['render'] = $this;

        return $this->phpRenderer->render($file, $vars);
    }

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

        $this->viewLocator->setControllerFile($this->controllerFile);

        return $this;
    }

    public function share(string $key, mixed $value): self
    {
        $this->shared[$key] = $value;
        return $this;
    }

    public function addCss(string $href, array $attrs = []): self
    {
        $this->assets->addCss($href, $attrs);
        return $this;
    }

    public function addJs(string $src, string $where = 'body', array $attrs = []): self
    {
        $this->assets->addJs($src, $where, $attrs);
        return $this;
    }

    public function renderCss(): string
    {
        return $this->assets->renderCss();
    }

    public function renderJs(string $where = 'body'): string
    {
        return $this->assets->renderJs($where);
    }

    /**
     * Рендер страницы.
     * - $view: 'index' или 'folder/name' (без .php)
     * - $layoutFile: null => config layout, '' => только content
     */
    public function view(string $view, array $data = [], string|null $layoutFile = null): string
    {
        $vars = array_merge($this->shared, $data);

        // чтобы можно было добавлять ассеты прямо из view/layout: $render->addCss(...)
        $vars['render'] = $this;

        $contentFile = $this->viewLocator->resolve($view);
        $content     = $this->phpRenderer->render($contentFile, $vars);

        $layoutFile = ($layoutFile === null) ? $this->config->layoutFile : $layoutFile;
        if ($layoutFile === '') {
            return $content;
        }

        $vars['content'] = $content;

        return $this->phpRenderer->render($layoutFile, $vars);
    }
}
