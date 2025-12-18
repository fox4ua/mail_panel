<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries\Render;

use Modules\System\Layout\Config\Render as RenderConfig;
use RuntimeException;
use Throwable;

class Renderer
{
    protected ?string $controllerClass = null;
    protected ?string $controllerFile  = null;

    /** @var array<string,mixed> */
    protected array $shared = [];

    /** @var array<string, array{href:string, attrs:array}> */
    protected array $css = [];

    /** @var array{head: array<string, array{src:string, attrs:array}>, body: array<string, array{src:string, attrs:array}>} */
    protected array $js = [
        'head' => [],
        'body' => [],
    ];

    public function __construct(protected RenderConfig $config) {}

    public function setController(object|string $controller): self
    {
        $this->controllerClass = is_object($controller) ? get_class($controller) : $controller;

        try {
            $ref = new \ReflectionClass($this->controllerClass);
            $this->controllerFile = $ref->getFileName() ?: null;
        } catch (Throwable) {
            $this->controllerFile = null;
        }

        return $this;
    }

    public function share(string $key, mixed $value): self
    {
        $this->shared[$key] = $value;
        return $this;
    }

    /**
     * Добавить CSS (дедуп по href).
     */
    public function addCss(string $href, array $attrs = []): self
    {
        $href = trim($href);
        if ($href === '') {
            return $this;
        }

        $this->assertAssetUrlAllowed($href, 'CSS');

        $key = $this->normalizeAssetKey($href);

        if (!isset($this->css[$key])) {
            $this->css[$key] = ['href' => $href, 'attrs' => $attrs];
            return $this;
        }

        // не задваиваем, attrs дополняем (не перетирая уже заданные)
        $this->css[$key]['attrs'] = $this->css[$key]['attrs'] + $attrs;

        return $this;
    }

    /**
     * Добавить JS (дедуп по src). where: head|body
     * Правило: если один и тот же src добавили в body и в head — оставляем в head.
     */
    public function addJs(string $src, string $where = 'body', array $attrs = []): self
    {
        $src = trim($src);
        if ($src === '') {
            return $this;
        }

        $this->assertAssetUrlAllowed($src, 'JS');

        $where = ($where === 'head') ? 'head' : 'body';
        $key   = $this->normalizeAssetKey($src);

        // уже в head — body игнорируем, attrs дополним
        if (isset($this->js['head'][$key])) {
            $this->js['head'][$key]['attrs'] = $this->js['head'][$key]['attrs'] + $attrs;
            return $this;
        }

        // был в body, но просят head — переносим в head
        if ($where === 'head' && isset($this->js['body'][$key])) {
            $existing = $this->js['body'][$key];
            unset($this->js['body'][$key]);

            $this->js['head'][$key] = [
                'src'   => $existing['src'],
                'attrs' => $existing['attrs'] + $attrs,
            ];
            return $this;
        }

        // обычная установка
        if (!isset($this->js[$where][$key])) {
            $this->js[$where][$key] = ['src' => $src, 'attrs' => $attrs];
            return $this;
        }

        // дедуп + merge attrs
        $this->js[$where][$key]['attrs'] = $this->js[$where][$key]['attrs'] + $attrs;

        return $this;
    }

    /**
     * HTML для <head>: CSS
     */
    public function renderCss(): string
    {
        $out = '';
        foreach ($this->css as $item) {
            $href = function_exists('esc') ? esc($item['href']) : htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
            $out .= '<link rel="stylesheet" href="' . $href . '"' . $this->renderAttrs($item['attrs']) . ">\n";
        }
        return $out;
    }

    /**
     * HTML для head/body: JS
     */
    public function renderJs(string $where = 'body'): string
    {
        $where = ($where === 'head') ? 'head' : 'body';

        $out = '';
        foreach ($this->js[$where] as $item) {
            $src = function_exists('esc') ? esc($item['src']) : htmlspecialchars($item['src'], ENT_QUOTES, 'UTF-8');
            $out .= '<script src="' . $src . '"' . $this->renderAttrs($item['attrs']) . "></script>\n";
        }
        return $out;
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
        $vars += ['render' => $this];

        $contentFile = $this->resolveViewFile($view);
        $content     = $this->renderFile($contentFile, $vars);

        $layoutFile = ($layoutFile === null) ? $this->config->layoutFile : $layoutFile;
        if ($layoutFile === '') {
            return $content;
        }

        $vars['content'] = $content;

        return $this->renderFile($layoutFile, $vars);
    }

    protected function resolveViewFile(string $view): string
    {
        $view = trim($view, '/');
        $view = str_replace(['\\', '..'], ['/', ''], $view);

        // 1) текущий модуль
        if ($this->config->useModuleViews && $this->controllerFile) {
            $moduleViewsDir = $this->detectModuleViewsDir($this->controllerFile);
            if ($moduleViewsDir !== null) {
                $candidate = rtrim($moduleViewsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        // 2) app/Views fallback
        if ($this->config->fallbackToAppViews) {
            $candidate = rtrim(APPPATH . 'Views', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('View file not found for: ' . $view);
    }

    protected function detectModuleViewsDir(string $controllerFile): ?string
    {
        $norm = str_replace('\\', '/', $controllerFile);
        $root = rtrim(str_replace('\\', '/', ROOTPATH), '/') . '/';
        $modulesRoot = $root . 'modules/';

        if (!str_starts_with($norm, $modulesRoot)) {
            return null;
        }

        // modules/<cat>/<Module>/Controllers/<X>.php => modules/<cat>/<Module>/Views
        $controllersDir = str_replace('\\', '/', dirname($controllerFile));
        $moduleDir = dirname($controllersDir);

        if (!is_dir($moduleDir)) {
            return null;
        }

        $viewsDir = $moduleDir . DIRECTORY_SEPARATOR . 'Views';
        return is_dir($viewsDir) ? $viewsDir : null;
    }

    protected function renderFile(string $file, array $vars): string
    {
        if (!is_file($file)) {
            throw new RuntimeException('Template file not found: ' . $file);
        }

        extract($vars, EXTR_SKIP);

        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    protected function normalizeAssetKey(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if ($this->config->dedupeIgnoreQuery) {
            // /app.css?v=1#x => /app.css
            $path = preg_split('~[?#]~', $path, 2)[0] ?? $path;
        }

        return $path;
    }

    protected function renderAttrs(array $attrs): string
    {
        if ($attrs === []) {
            return '';
        }

        $out = '';
        foreach ($attrs as $k => $v) {
            if (is_int($k)) {
                $k = (string) $v;
                $v = true;
            }

            $k = (string) $k;
            if ($k === '') {
                continue;
            }

            if ($v === true) {
                $out .= ' ' . (function_exists('esc') ? esc($k) : htmlspecialchars($k, ENT_QUOTES, 'UTF-8'));
                continue;
            }
            if ($v === false || $v === null) {
                continue;
            }

            $kk = function_exists('esc') ? esc($k) : htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
            $vv = function_exists('esc') ? esc((string) $v) : htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
            $out .= ' ' . $kk . '="' . $vv . '"';
        }

        return $out;
    }

    protected function assertAssetUrlAllowed(string $url, string $kind): void
    {
        $url = trim($url);
        if ($url === '') {
            throw new RuntimeException($kind . ' URL is empty');
        }

        // Разрешаем только абсолютные пути от корня сайта: /assets/app.css
        if ($url[0] === '/') {
            return;
        }

        $lower = strtolower($url);

        // Запрещаем опасные схемы
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) {
            throw new RuntimeException($kind . ' URL scheme is not allowed: ' . $url);
        }

        // Запрещаем протокол-относительные URL: //cdn...
        if (str_starts_with($lower, '//')) {
            throw new RuntimeException($kind . ' protocol-relative URL is not allowed: ' . $url);
        }

        // Разрешаем только http/https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme)) {
            throw new RuntimeException($kind . ' URL must be absolute (https://...) or start with "/": ' . $url);
        }

        $scheme = strtolower($scheme);
        if ($scheme === 'https') {
            return;
        }

        if ($scheme === 'http' && $this->config->assetsAllowHttp) {
            return;
        }

        throw new RuntimeException($kind . ' URL scheme not allowed: ' . $url);
    }

    protected function assertAssetUrlAllowed(string $url, string $kind): void
    {
        $url = trim($url);
        if ($url === '') {
            throw new RuntimeException($kind . ' URL is empty');
        }

        // Разрешаем только абсолютные пути от корня сайта: /assets/app.css
        if ($url[0] === '/') {
            return;
        }

        $lower = strtolower($url);

        // Запрещаем опасные схемы
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) {
            throw new RuntimeException($kind . ' URL scheme is not allowed: ' . $url);
        }

        // Запрещаем протокол-относительные URL: //cdn...
        if (str_starts_with($lower, '//')) {
            throw new RuntimeException($kind . ' protocol-relative URL is not allowed: ' . $url);
        }

        // Разрешаем только http/https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme)) {
            throw new RuntimeException($kind . ' URL must be absolute (https://...) or start with "/": ' . $url);
        }

        $scheme = strtolower($scheme);
        if ($scheme === 'https') {
            return;
        }

        if ($scheme === 'http' && $this->config->assetsAllowHttp) {
            return;
        }

        throw new RuntimeException($kind . ' URL scheme not allowed: ' . $url);
    }

}
