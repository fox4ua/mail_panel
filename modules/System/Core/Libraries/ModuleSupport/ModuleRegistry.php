<?php

namespace Modules\System\Core\Libraries\ModuleSupport;

class ModuleRegistry
{
    private ModuleDiscovery $discovery;
    private array $modules = [];

    private string $cacheKey = 'modulecenter:discovery:v2_info_only';
    private int $cacheTtl = 20;

    public function __construct(?ModuleDiscovery $discovery = null, bool $forceRescan = false)
    {
        $this->discovery = $discovery ?? new ModuleDiscovery();

        if ($forceRescan) {
            $this->modules = $this->discovery->discover(ROOTPATH . 'modules');
            $this->saveCache($this->modules);
            return;
        }

        $cached = $this->loadCache();
        if (is_array($cached)) {
            $this->modules = $cached;
        } else {
            $this->modules = $this->discovery->discover(ROOTPATH . 'modules');
            $this->saveCache($this->modules);
        }
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function byName(string $name): ?ModuleInfo
    {
        $name = mb_strtolower($name);
        foreach ($this->modules as $m) {
            if (mb_strtolower($m->module) === $name) return $m;
        }
        return null;
    }

    public function info(ModuleInfo $info): object
    {
        return $this->discovery->loadInfo($info);
    }

    public function rescan(): void
    {
        $this->modules = $this->discovery->discover(ROOTPATH . 'modules');
        $this->saveCache($this->modules);
    }

    public static function clearCache(): void
    {
        try {
            $cache = \Config\Services::cache();
            $cache->delete('modulecenter:discovery:v2_info_only');
        } catch (\Throwable $e) {}
    }

    private function loadCache(): ?array
    {
        try {
            $cache = \Config\Services::cache();
            $v = $cache->get($this->cacheKey);
            return is_array($v) ? $v : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function saveCache(array $modules): void
    {
        try {
            $cache = \Config\Services::cache();
            $cache->save($this->cacheKey, $modules, $this->cacheTtl);
        } catch (\Throwable $e) {}
    }
}
