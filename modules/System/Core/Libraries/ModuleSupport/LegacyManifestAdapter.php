<?php

namespace Modules\System\Core\Libraries\ModuleSupport;

/**
 * Wraps legacy manifests (any object) and exposes ModuleManifestInterface.
 * Provides safe defaults so ModuleCenter can call methods without method_exists().
 */
final class LegacyManifestAdapter implements ModuleManifestInterface
{
    private object $inner;

    public function __construct(object $inner)
    {
        $this->inner = $inner;
    }

    public function install(): bool
    {
        try {
            if (method_exists($this->inner, 'install')) {
                return (bool) $this->inner->install();
            }
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function uninstall(): bool
    {
        try {
            if (method_exists($this->inner, 'uninstall')) {
                return (bool) $this->inner->uninstall();
            }
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function update(string $from, string $to): bool
    {
        try {
            if (method_exists($this->inner, 'update')) {
                return (bool) $this->inner->update($from, $to);
            }
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function menu(): array
    {
        try {
            if (method_exists($this->inner, 'menu')) {
                $m = $this->inner->menu();
                return is_array($m) ? $m : [];
            }
        } catch (\Throwable $e) {
            return [];
        }
        return [];
    }

    public function routes()
    {
        try {
            if (method_exists($this->inner, 'routes')) {
                return $this->inner->routes();
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public function requires(): array
    {
        // method takes priority
        try {
            if (method_exists($this->inner, 'requires')) {
                $r = $this->inner->requires();
                if (is_array($r)) return $r;
            }
        } catch (\Throwable $e) {
            return [];
        }

        // property fallback
        try {
            if (property_exists($this->inner, 'requires') && is_array($this->inner->requires)) {
                return $this->inner->requires;
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    public function inner(): object
    {
        return $this->inner;
    }
}
