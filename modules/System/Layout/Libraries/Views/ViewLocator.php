<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries\Views;

use Modules\System\Layout\Config\Render as RenderConfig;
use RuntimeException;

class ViewLocator
{
    protected ?string $controllerFile = null;

    protected ?string $cachedModuleViewsDir = null;
    protected bool $moduleViewsDirResolved = false;

    public function __construct(protected RenderConfig $config) {}

    public function setControllerFile(?string $controllerFile): self
    {
        $this->controllerFile = $controllerFile ?: null;

        $this->cachedModuleViewsDir = null;
        $this->moduleViewsDirResolved = false;

        return $this;
    }

    public function resolve(string $view): string
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
}
