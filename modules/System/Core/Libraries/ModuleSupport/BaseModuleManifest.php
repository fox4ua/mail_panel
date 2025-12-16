<?php

namespace Modules\System\Core\Libraries\ModuleSupport;

/**
 * Base manifest with safe defaults.
 *
 * Extend this in your modules:
 *
 * class Module extends BaseModuleManifest
 * {
 *     public string $name = 'helloworld';
 *     public string $title = 'Hello World';
 *     public string $version = '1.0.0';
 *     public int $weight = 0;
 *     public array $requires = ['system/layout', 'system/menu'];
 * }
 */
abstract class BaseModuleManifest implements ModuleManifestInterface
{
    public string $name = '';
    public string $title = '';
    public string $version = '';
    public int $weight = 0;

    /** @var string[] */
    public array $requires = [];

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }

    public function update(string $from, string $to): bool
    {
        return true;
    }

    public function menu(): array
    {
        return [];
    }

    public function routes()
    {
        return null;
    }

    public function requires(): array
    {
        return $this->requires;
    }
}
