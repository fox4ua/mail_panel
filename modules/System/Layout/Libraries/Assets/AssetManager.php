<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries\Assets;

use Modules\System\Layout\Config\Render as RenderConfig;
use RuntimeException;

class AssetManager
{
    /** @var array<string, array{href:string, attrs:array}> */
    protected array $css = [];

    /** @var array{head: array<string, array{src:string, attrs:array}>, body: array<string, array{src:string, attrs:array}>} */
    protected array $js = [
        'head' => [],
        'body' => [],
    ];

    public function __construct(protected RenderConfig $config) {}

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

        // Уже в head — body игнорируем, attrs мерджим
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
     * HTML для <head> или перед </body>: JS
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
                $this->logAssetConflict($kind, $asset, (string) $k, $existing[$k], $v);
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

        log_message('warning', $msg);
    }
}
