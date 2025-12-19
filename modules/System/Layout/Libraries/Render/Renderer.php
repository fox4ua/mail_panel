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
    protected ?string $title = null;
    protected ?string $cachedModuleViewsDir = null;
    protected bool $moduleViewsDirResolved = false;

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

        // fallback: сначала то, что передали, иначе $title из view/layout, иначе "App"
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

        // сброс кэша пути Views при смене контроллера
        $this->cachedModuleViewsDir = null;
        $this->moduleViewsDirResolved = false;

        return $this;
    }

    protected function getModuleViewsDirCached(): ?string
    {
        if ($this->moduleViewsDirResolved) {
            return $this->cachedModuleViewsDir;
        }

        $this->cachedModuleViewsDir = $this->controllerFile
            ? $this->detectModuleViewsDir($this->controllerFile)
            : null;

        $this->moduleViewsDirResolved = true;

        return $this->cachedModuleViewsDir;
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

        // дедуп + merge attrs (с логированием конфликтов в dev)
        $this->css[$key]['attrs'] = $this->mergeAttrs(
            $this->css[$key]['attrs'],
            $attrs,
            $href,
            'css'
        );

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

          // Уже в head — body игнорируем, attrs мерджим (с логом конфликта)
          if (isset($this->js['head'][$key])) {
              $this->js['head'][$key]['attrs'] = $this->mergeAttrs(
                  $this->js['head'][$key]['attrs'],
                  $attrs,
                  $src,
                  'js'
              );
              return $this;
          }

          // Есть в body, но просят head — переносим в head
          if ($where === 'head' && isset($this->js['body'][$key])) {
              $existing = $this->js['body'][$key];
              unset($this->js['body'][$key]);

              $this->js['head'][$key] = [
                  'src'   => $existing['src'],
                  'attrs' => $this->mergeAttrs($existing['attrs'], $attrs, $src, 'js'),
              ];
              return $this;
          }

          // Нет такого файла — добавляем
          if (!isset($this->js[$where][$key])) {
              $this->js[$where][$key] = ['src' => $src, 'attrs' => $attrs];
              return $this;
          }

          // Уже есть в выбранной зоне — дедуп + merge attrs
          $this->js[$where][$key]['attrs'] = $this->mergeAttrs(
              $this->js[$where][$key]['attrs'],
              $attrs,
              $src,
              'js'
          );

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
        $vars['render'] = $this;

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
        $view = $this->sanitizeViewName($view);

        $rel = str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

        // 1) текущий модуль
        if ($this->config->useModuleViews && $this->controllerFile) {
            $moduleViewsDir = $this->getModuleViewsDirCached();

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
            $candidate = rtrim(APPPATH . 'Views', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('View file not found for: ' . $view);
    }

    /**
     * Разрешаем только безопасные имена: a-zA-Z0-9/_-
     * Запрещаем пустые сегменты, "." и "..", нулевые байты, расширения.
     */
    protected function sanitizeViewName(string $view): string
    {
        $view = str_replace('\\', '/', $view);
        $view = trim($view);

        // убираем ведущие/замыкающие слеши
        $view = trim($view, '/');

        if ($view === '') {
            throw new RuntimeException('Empty view name');
        }

        // защита от нулевых байтов
        if (str_contains($view, "\0")) {
            throw new RuntimeException('Invalid view name');
        }

        // запрещаем расширение (чтобы не было "hello.php")
        if (str_ends_with($view, '.php')) {
            throw new RuntimeException('Do not include ".php" in view name: ' . $view);
        }

        // allowlist символов
        if (!preg_match('~^[A-Za-z0-9/_-]+$~', $view)) {
            throw new RuntimeException('Invalid view name: ' . $view);
        }

        // запрет "." и ".." сегментов + пустых сегментов
        foreach (explode('/', $view) as $seg) {
            if ($seg === '' || $seg === '.' || $seg === '..') {
                throw new RuntimeException('Invalid view path segment in: ' . $view);
            }
        }

        // (опционально) ограничение длины
        if (strlen($view) > 200) {
            throw new RuntimeException('View name too long');
        }

        return $view;
    }


    protected function detectModuleViewsDir(string $controllerFile): ?string
    {
        $modulesBase = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR;
        $norm = str_replace('\\', '/', $controllerFile);
        $base = str_replace('\\', '/', $modulesBase);

        if (!str_starts_with($norm, $base)) {
            return null;
        }

        $rel = trim(substr($norm, strlen($base)), '/');  // <Cat>/<Mod>/...
        $parts = explode('/', $rel);

        if (count($parts) < 2) {
            return null;
        }

        $cat = $parts[0];
        $mod = $parts[1];

        $moduleDir = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules'
            . DIRECTORY_SEPARATOR . $cat . DIRECTORY_SEPARATOR . $mod;

        $viewsDir = $moduleDir . DIRECTORY_SEPARATOR . 'Views';
        return is_dir($viewsDir) ? $viewsDir : null;
    }


    protected function renderFile(string $file, array $vars): string
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Template file not found: ' . $file);
        }

        // фиксируем текущий уровень буферизации, чтобы корректно очистить всё,
        // что было открыто внутри шаблона, если там упадёт исключение
        $level = ob_get_level();

        extract($vars, EXTR_SKIP);

        ob_start();
        try {
            include $file;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            // чистим только те буферы, которые открылись после $level
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
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

    /**
     * Безопасность addCss/addJs
     */

    protected function assertAssetUrlAllowed(string $url, string $kind): void
    {
        $url = trim($url);
        if ($url === '') {
            throw new RuntimeException($kind . ' URL is empty');
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

        // Разрешаем только абсолютные пути от корня сайта: /assets/app.css
        if ($url[0] === '/') {
            return;
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

    protected function mergeAttrs(array $existing, array $incoming, string $asset, string $kind): array
    {
        // определяем конфликты: ключ есть в обоих, но значение разное
        foreach ($incoming as $k => $v) {
            if (array_key_exists($k, $existing) && $existing[$k] !== $v) {
                $this->logAssetConflict($kind, $asset, (string)$k, $existing[$k], $v);
            }
        }

        // политика: первый победил (existing не перезаписываем)
        return $existing + $incoming;
    }

    protected function logAssetConflict(string $kind, string $asset, string $key, mixed $old, mixed $new): void
    {
        if (!defined('ENVIRONMENT') || ENVIRONMENT === 'production') {
            return;
        }

        $oldStr = is_scalar($old) || $old === null ? var_export($old, true) : gettype($old);
        $newStr = is_scalar($new) || $new === null ? var_export($new, true) : gettype($new);

        $msg = sprintf(
            '[Renderer][asset-conflict] %s %s attr "%s" conflict: old=%s new=%s',
            $kind,
            $asset,
            $key,
            $oldStr,
            $newStr
        );

        // В CI4: log_message(level, message)
        log_message('warning', $msg);
    }
}
