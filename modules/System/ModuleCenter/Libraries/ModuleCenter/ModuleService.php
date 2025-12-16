<?php

namespace Modules\System\ModuleCenter\Libraries\ModuleCenter;

use Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry;
use Modules\System\ModuleCenter\Models\ModuleModel;

/**
 * Module state manager (Installed/Enabled) + install/update/uninstall hooks.
 *
 * New contract:
 * - Module metadata is read from Config/Info.php (Info class).
 * - Install/Uninstall/Update hooks are executed via Controllers\InstallController.
 * - Menu is stored in DB only (no menu arrays from files).
 */
class ModuleService
{
    private ModuleRegistry $registry;
    private ModuleModel $model;

    private string $listCacheKey = 'modulecenter:listModules:v8_info_only';
    private int $listCacheTtl = 20;

    private ?string $lastError = null;

    public function __construct(?ModuleRegistry $registry = null, ?ModuleModel $model = null)
    {
        $this->registry = $registry ?? new ModuleRegistry();
        $this->model    = $model ?? new ModuleModel();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Returns merged list: filesystem (Info.php) + DB state.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listModules(bool $forceRescan = false): array
    {
        $cache = null;
        try { $cache = \Config\Services::cache(); } catch (\Throwable $e) {}

        if ($cache) {
            if ($forceRescan) {
                try { $cache->delete($this->listCacheKey); } catch (\Throwable $e) {}
            } else {
                try {
                    $cached = $cache->get($this->listCacheKey);
                    if (is_array($cached)) {
                        return $cached;
                    }
                } catch (\Throwable $e) {}
            }
        }

        // filesystem modules
        $infos = $this->registry->all();

        // DB state
        $rowsDb = [];
        try {
            foreach ($this->model->findAll() as $r) {
                $rowsDb[mb_strtolower((string)$r['name'])] = $r;
            }
        } catch (\Throwable $e) {
            $rowsDb = [];
        }

        $rows = [];
        foreach ($infos as $info) {
            $dirName = (string)$info->module;
            $nameKey = mb_strtolower($dirName);
            $cat     = mb_strtolower((string)$info->category);

            $broken = false;
            $brokenReason = null;

            $title = $dirName;
            $version = '';
            $weight = 0;

            try {
                $meta = $this->registry->info($info);

                if (property_exists($meta, 'title'))   $title = (string)$meta->title;
                if (property_exists($meta, 'version')) $version = (string)$meta->version;
                if (property_exists($meta, 'weight'))  $weight = (int)$meta->weight;

                // name from Info is authoritative for DB key
                if (property_exists($meta, 'name') && trim((string)$meta->name) !== '') {
                    $nameKey = mb_strtolower(trim((string)$meta->name));
                }
            } catch (\Throwable $e) {
                $broken = true;
                $brokenReason = 'Info.php load failed';
            }

            $db = $rowsDb[$nameKey] ?? null;

            $isInstalled = $cat === 'system' ? true : (is_array($db));
            $isEnabled   = $cat === 'system' ? true : (is_array($db) ? ((int)($db['is_enabled'] ?? 0) === 1) : false);

            // If module exists on disk but has no InstallController, it is not installable => broken
            $installCtl = $info->namespace . '\\Controllers\\InstallController';
            if (!$broken && $cat !== 'system') {
                if (!class_exists($installCtl)) {
                    // try load file explicitly
                    $ctlFile = rtrim($info->path, '/\\') . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'InstallController.php';
                    if (is_file($ctlFile)) {
                        try { (static function(string $__f){ require_once $__f; })($ctlFile); } catch (\Throwable $e) {}
                    }
                }
                if (!class_exists($installCtl)) {
                    $broken = true;
                    $brokenReason = 'Missing InstallController';
                }
            }

            $state = 'not_installed';
            if ($broken) {
                $state = 'broken';
            } elseif ($isInstalled && !$isEnabled) {
                $state = 'disabled';
            } elseif ($isInstalled && $isEnabled) {
                $state = 'enabled';
            }

            $rows[] = [
                'name'           => $nameKey,
                'dir'            => $dirName,
                'category'       => $cat,
                'title'          => $title,
                'version'        => $version,
                'weight'         => $weight,
                'path'           => (string)$info->path,

                'is_system'      => $cat === 'system',
                'is_installed'   => $isInstalled,
                'is_enabled'     => $isEnabled,

                'installed_at'   => is_array($db) ? ($db['installed_at'] ?? null) : null,
                'updated_at'     => is_array($db) ? ($db['updated_at'] ?? null) : null,

                'broken'         => $broken,
                'broken_reason'  => $brokenReason,
                'state'          => $state,
            ];
        }

        // Route conflict scan (enabled + installed modules only)
        try {
            $enabledMap = [];
            foreach ($rows as $r) {
                if (!empty($r['is_installed']) && !empty($r['is_enabled']) && empty($r['broken'])) {
                    $enabledMap[(string)$r['name']] = true;
                }
            }
            if (!empty($enabledMap) && class_exists(RouteConflictDetector::class)) {
                $conf = RouteConflictDetector::scanEnabled($this->registry, $enabledMap);
                if (!empty($conf)) {
                    foreach ($rows as &$r2) {
                        $n = (string)($r2['name'] ?? '');
                        if (isset($conf[$n]) && empty($r2['broken'])) {
                            $r2['broken'] = true;
                            $r2['broken_reason'] = 'route conflict';
                            $r2['route_conflicts'] = $conf[$n];
                            $r2['state'] = 'broken';
                        }
                    }
                    unset($r2);
                }
            }
        } catch (\Throwable $e) {}

        // Deterministic ordering: system -> pages -> blocks, then by weight, then by name
        $catOrder = ['system' => 0, 'pages' => 1, 'blocks' => 2];
        usort($rows, static function($a, $b) use ($catOrder) {
            $ca = $catOrder[$a['category']] ?? 99;
            $cb = $catOrder[$b['category']] ?? 99;
            if ($ca !== $cb) return $ca <=> $cb;

            $wa = (int)($a['weight'] ?? 0);
            $wb = (int)($b['weight'] ?? 0);
            if ($wa !== $wb) return $wa <=> $wb;

            return strcmp((string)$a['name'], (string)$b['name']);
        });

        if ($cache) {
            try { $cache->save($this->listCacheKey, $rows, $this->listCacheTtl); } catch (\Throwable $e) {}
        }

        return $rows;
    }

    public function rescan(): void
    {
        try { \Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry::clearCache(); } catch (\Throwable $e) {}
        try { $this->registry->rescan(); } catch (\Throwable $e) {}
        $this->invalidateCaches();
    }

    /**
     * Install (first time) or Update (if already installed).
     */
    public function installOrUpdate(string $moduleName): bool
    {
        $this->lastError = null;
        $moduleName = mb_strtolower($moduleName);

        $info = $this->registry->byName($moduleName);
        if (!$info) {
            $this->lastError = 'Module not found on disk';
            return false;
        }

        $cat = mb_strtolower((string)$info->category);
        if ($cat === 'system') {
            // system modules are protected; still allow install/update hooks if needed
        }

        // load Info (metadata)
        $meta = null;
        try { $meta = $this->registry->info($info); } catch (\Throwable $e) {}

        $title = $moduleName;
        $version = '';
        if ($meta) {
            if (property_exists($meta, 'title')) $title = (string)$meta->title;
            if (property_exists($meta, 'version')) $version = (string)$meta->version;
            if (property_exists($meta, 'name') && trim((string)$meta->name) !== '') $moduleName = mb_strtolower(trim((string)$meta->name));
        }

        $existing = null;
        try { $existing = $this->model->find($moduleName); } catch (\Throwable $e) { $existing = null; }

        $from = is_array($existing) ? (string)($existing['version'] ?? '') : '';
        $to   = $version;

        $ok = false;
        if (!is_array($existing)) {
            $ok = $this->runInstallHook($info);
            if (!$ok) return false;

            // write DB row ONLY if hook succeeded
            $data = [
                'name'         => $moduleName,
                'title'        => $title,
                'version'      => $version,
                'is_enabled'   => 1,
                'installed_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            if (!$this->model->insert($data)) {
                $this->lastError = 'DB write failed';
                return false;
            }
        } else {
            $ok = $this->runUpdateHook($info, $from, $to);
            if (!$ok) return false;

            $data = [
                'title'      => $title,
                'version'    => $version,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if (!$this->model->update($moduleName, $data)) {
                $this->lastError = 'DB update failed';
                return false;
            }
        }

        $this->invalidateCaches();
        return true;
    }

    public function uninstall(string $moduleName, bool $dryRun = false): array
    {
        $this->lastError = null;
        $moduleName = mb_strtolower($moduleName);

        $info = $this->registry->byName($moduleName);
        if (!$info) {
            return ['ok' => false, 'error' => 'Module not found on disk'];
        }

        $cat = mb_strtolower((string)$info->category);
        if ($cat === 'system') {
            return ['ok' => false, 'error' => 'System modules are protected'];
        }

        if ($dryRun) {
            return ['ok' => true, 'dry_run' => true];
        }

        if (!$this->runUninstallHook($info)) {
            return ['ok' => false, 'error' => $this->lastError ?? 'Uninstall hook failed'];
        }

        // delete DB row
        try {
            $this->model->delete($moduleName);
        } catch (\Throwable $e) {}

// delete directory on disk
try {
    $this->rrmdir((string)$info->path);
} catch (\Throwable $e) {}


        $this->invalidateCaches();
        return ['ok' => true];
    }

    public function enable(string $moduleName): bool
    {
        $this->lastError = null;
        $moduleName = mb_strtolower($moduleName);
        if (!$this->model->find($moduleName)) {
            $this->lastError = 'Module not installed';
            return false;
        }
        if (!$this->model->update($moduleName, ['is_enabled' => 1, 'updated_at' => date('Y-m-d H:i:s')])) {
            $this->lastError = 'DB update failed';
            return false;
        }
        $this->invalidateCaches();
        return true;
    }

    public function disable(string $moduleName): bool
    {
        $this->lastError = null;
        $moduleName = mb_strtolower($moduleName);
        if (!$this->model->find($moduleName)) {
            $this->lastError = 'Module not installed';
            return false;
        }
        if (!$this->model->update($moduleName, ['is_enabled' => 0, 'updated_at' => date('Y-m-d H:i:s')])) {
            $this->lastError = 'DB update failed';
            return false;
        }
        $this->invalidateCaches();
        return true;
    }

    /**
     * Map of installed+enabled modules (lowercase => true).
     *
     * @return array<string,bool>
     */
    public function enabledMap(): array
    {
        $map = [];
        try {
            foreach ($this->model->findAll() as $r) {
                $name = mb_strtolower((string)($r['name'] ?? ''));
                if ($name === '') continue;
                if ((int)($r['is_enabled'] ?? 0) === 1) {
                    $map[$name] = true;
                }
            }
        } catch (\Throwable $e) {}

        // System modules: always enabled if present on disk
        try {
            foreach ($this->registry->all() as $info) {
                if (mb_strtolower((string)$info->category) === 'system') {
                    // nameKey: prefer Info->name, fallback to dir
                    $nameKey = mb_strtolower((string)$info->module);
                    try {
                        $meta = $this->registry->info($info);
                        if (property_exists($meta, 'name') && trim((string)$meta->name) !== '') {
                            $nameKey = mb_strtolower(trim((string)$meta->name));
                        }
                    } catch (\Throwable $e) {}
                    $map[$nameKey] = true;
                }
            }
        } catch (\Throwable $e) {}

        return $map;
    }

    private function runInstallHook($info): bool
    {
        $ctl = $info->namespace . '\\Controllers\\InstallController';
        $file = rtrim($info->path, '/\\') . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'InstallController.php';
        if (is_file($file)) {
            try { (static function(string $__f){ require_once $__f; })($file); } catch (\Throwable $e) {}
        }
        if (!class_exists($ctl)) {
            $this->lastError = 'InstallController not found';
            return false;
        }
        try {
            $obj = new $ctl();
            if (!method_exists($obj, 'install')) {
                $this->lastError = 'InstallController::install not found';
                return false;
            }
            $ok = (bool)$obj->install();
            if (!$ok) $this->lastError = 'Install hook returned false';
            return $ok;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function runUninstallHook($info): bool
    {
        $ctl = $info->namespace . '\\Controllers\\InstallController';
        $file = rtrim($info->path, '/\\') . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'InstallController.php';
        if (is_file($file)) {
            try { (static function(string $__f){ require_once $__f; })($file); } catch (\Throwable $e) {}
        }
        if (!class_exists($ctl)) {
            $this->lastError = 'InstallController not found';
            return false;
        }
        try {
            $obj = new $ctl();
            if (!method_exists($obj, 'uninstall')) {
                $this->lastError = 'InstallController::uninstall not found';
                return false;
            }
            $ok = (bool)$obj->uninstall();
            if (!$ok) $this->lastError = 'Uninstall hook returned false';
            return $ok;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function runUpdateHook($info, string $from, string $to): bool
    {
        $ctl = $info->namespace . '\\Controllers\\InstallController';
        $file = rtrim($info->path, '/\\') . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'InstallController.php';
        if (is_file($file)) {
            try { (static function(string $__f){ require_once $__f; })($file); } catch (\Throwable $e) {}
        }
        if (!class_exists($ctl)) {
            // If no update hook - treat as ok
            return true;
        }
        try {
            $obj = new $ctl();
            if (!method_exists($obj, 'update')) {
                return true;
            }
            $ok = (bool)$obj->update($from, $to);
            if (!$ok) $this->lastError = 'Update hook returned false';
            return $ok;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function invalidateCaches(): void
    {
        // list cache
        try {
            $cache = \Config\Services::cache();
            $cache->delete($this->listCacheKey);
        } catch (\Throwable $e) {}

        // discovery cache
        try { \Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry::clearCache(); } catch (\Throwable $e) {}

        if (function_exists('apcu_clear_cache')) @apcu_clear_cache();
        if (function_exists('opcache_reset')) @opcache_reset();
    }


    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;

        $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($ri as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) @rmdir($path);
            else @unlink($path);
        }
        @rmdir($dir);
    }
}
