<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries\Modules;

use Modules\System\Layout\Config\Render as RenderConfig;
use Modules\System\Layout\Libraries\Renderer;
use RuntimeException;

final class LayoutModuleManager
{
    /** @var array<string, callable(array):string> */
    private array $registry = [];

    /** @var array<string, string> */
    private array $cache = [];

    /** @var string[] */
    private array $stack = [];

    public function __construct(
        private readonly Renderer $renderer,
        private readonly RenderConfig $config
    ) {}

    public function register(string $name, callable $resolver, bool $override = true): self
    {
        $name = trim($name);
        if (!preg_match('~^[a-zA-Z0-9_-]{1,64}$~', $name)) {
            throw new RuntimeException('Invalid module name: ' . $name);
        }

        if (!$override && isset($this->registry[$name])) {
            return $this;
        }

        $this->registry[$name] = $resolver;
        $this->invalidate($name);

        return $this;
    }

    public function render(string $name, array $data = []): string
    {
        $name = trim($name);
        if (!preg_match('~^[a-zA-Z0-9_-]{1,64}$~', $name)) {
            return $this->handleError($name, 'Invalid module name');
        }

        // рекурсия: menu -> menu -> ...
        if (in_array($name, $this->stack, true)) {
            return $this->handleError($name, 'Recursive module call detected');
        }

        $cacheKey = $this->cacheKey($name, $data);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $resolver = $this->resolve($name);
        if ($resolver === null) {
            return $this->handleError($name, 'Module is not registered');
        }

        $this->stack[] = $name;

        try {
            $html = (string) $resolver($data);
        } catch (\Throwable $e) {
            array_pop($this->stack);
            return $this->handleError($name, $e->getMessage(), $e);
        }

        array_pop($this->stack);

        // кэш на один запрос
        $this->cache[$cacheKey] = $html;

        return $html;
    }

    public function invalidate(?string $name = null): void
    {
        if ($name === null) {
            $this->cache = [];
            return;
        }

        $prefix = $name . ':';
        foreach (array_keys($this->cache) as $k) {
            if ($k === $name || str_starts_with($k, $prefix)) {
                unset($this->cache[$k]);
            }
        }
    }

    private function resolve(string $name): ?callable
    {
        if (isset($this->registry[$name])) {
            return $this->registry[$name];
        }

        // ленивый резолв из config->layoutModules
        if (property_exists($this->config, 'layoutModules')
            && is_array($this->config->layoutModules)
            && isset($this->config->layoutModules[$name])
        ) {
            $spec = (string) $this->config->layoutModules[$name];
            $resolver = $this->makeResolverFromSpec($spec);

            if ($resolver !== null) {
                $this->registry[$name] = $resolver;
                return $resolver;
            }
        }

        return null;
    }

    private function makeResolverFromSpec(string $spec): ?callable
    {
        // формат: "Blocks/Menu:menu"
        $spec = trim($spec);
        if ($spec === '' || !str_contains($spec, ':') || !str_contains($spec, '/')) {
            return null;
        }

        [$left, $view] = explode(':', $spec, 2);
        [$category, $module] = explode('/', $left, 2);

        $category = trim($category);
        $module   = trim($module);
        $view     = trim($view, '/');

        if (!preg_match('~^[A-Za-z0-9_]+$~', $category) || !preg_match('~^[A-Za-z0-9_]+$~', $module)) {
            return null;
        }
        if ($view === '' || str_contains($view, '..') || str_contains($view, '\\')) {
            return null;
        }

        return fn(array $data = []) => $this->renderer->renderModuleView($category, $module, $view, $data);
    }

    private function cacheKey(string $name, array $data): string
    {
        if ($data === []) {
            return $name;
        }
        return $name . ':' . sha1(serialize($data));
    }

    private function handleError(string $name, string $message, ?\Throwable $e = null): string
    {
        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            $msg = '[LayoutModule] ' . $name . ': ' . $message;
            if ($e) {
                $msg .= ' (' . get_class($e) . ')';
            }
            log_message('error', $msg);
        }
        return '';
    }
}
