<?php

namespace Modules\System\ModuleCenter\Libraries\ModuleCenter;

use CodeIgniter\HTTP\Files\UploadedFile;
use Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry;

class ModulePackageService
{
    private array $categories = ['system', 'pages', 'blocks'];

    private array $denyBasenames = ['.env', '.htaccess', 'php.ini'];
    private array $denyExt = ['phar', 'so', 'sh', 'bat', 'exe'];

    public function deployFromZip(UploadedFile $zip): array
    {
        $tmpPath = $zip->getTempName();
        if (!$tmpPath || !is_file($tmpPath)) {
            return ['ok' => false, 'error' => 'Missing temp file'];
        }

        $v = $this->validateZip($tmpPath);
        if (!$v['ok']) {
            return $v;
        }

        $category      = $v['category'];
        $moduleDirName = $v['moduleDirName']; // preserve case
        $moduleKey     = $v['moduleKey'];     // lowercase (directory key)
        $prefix        = $v['prefix'];

        // Protect System modules from upload/overwrite
        if ($category === 'system') {
            return ['ok' => false, 'error' => 'Uploading System modules is forbidden'];
        }

        $catDir    = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $category;
        $targetDir = $catDir . DIRECTORY_SEPARATOR . $moduleDirName;

        $mode = is_dir($targetDir) ? 'update' : 'install';

        // Stage in same directory (same filesystem) for atomic rename
        $stagingDir = $catDir . DIRECTORY_SEPARATOR . '.__staging_' . $moduleDirName . '_' . date('YmdHis');
        $backupDir  = null;

        try {
            if (!@mkdir($stagingDir, 0775, true) && !is_dir($stagingDir)) {
                return ['ok' => false, 'error' => 'Cannot create staging directory'];
            }

            // Extract to staging
            $this->extractZip($tmpPath, $prefix, $stagingDir);

            // Required descriptor
            $infoPhp = $stagingDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Info.php';
            if (!is_file($infoPhp)) {
                $this->rrmdir($stagingDir);
                return ['ok' => false, 'error' => 'Config/Info.php is required'];
            }

            // Required install controller
            $installCtl = $stagingDir . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'InstallController.php';
            if (!is_file($installCtl)) {
                $this->rrmdir($stagingDir);
                return ['ok' => false, 'error' => 'Controllers/InstallController.php is required'];
            }

            // Health-check: load Info.php (must be executable)
            try {
                (static function(string $__file) { require $__file; })($infoPhp);
            } catch (\Throwable $e) {
                $this->rrmdir($stagingDir);
                return ['ok' => false, 'error' => 'Info health-check failed: ' . $e->getMessage()];
            }

            // Health-check: route conflicts (only if module provides Config/Routes.php)
            $routesFile = $stagingDir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
            if (is_file($routesFile)) {
                $svc = new ModuleService();
                $enabled = $svc->enabledMap();
                if (!empty($enabled)) {
                    $exclude = ($mode === 'update') ? $moduleKey : null;
                    $conf = RouteConflictDetector::scanWithExtra(new ModuleRegistry(), $enabled, $exclude, $routesFile, $moduleKey);
                    if (!empty($conf[$moduleKey])) {
                        $this->rrmdir($stagingDir);
                        return ['ok' => false, 'error' => 'Route conflict detected', 'details' => $conf[$moduleKey]];
                    }
                }
            }

            // Commit
            if (is_dir($targetDir)) {
                $backupDir = $targetDir . '.__backup_' . date('YmdHis');
                if (!@rename($targetDir, $backupDir)) {
                    $this->rrmdir($stagingDir);
                    return ['ok' => false, 'error' => 'Cannot backup existing module directory'];
                }
            }

            if (!@rename($stagingDir, $targetDir)) {
                if ($backupDir && is_dir($backupDir)) {
                    @rename($backupDir, $targetDir);
                }
                $this->rrmdir($stagingDir);
                return ['ok' => false, 'error' => 'Cannot commit staged module directory'];
            }

            // Install/update hooks + DB write (ModuleService handles DB row write after successful hook)
            $svc = new ModuleService();
            if (!$svc->installOrUpdate($moduleKey)) {
                // rollback
                $this->rrmdir($targetDir);
                if ($backupDir && is_dir($backupDir)) {
                    @rename($backupDir, $targetDir);
                }
                return ['ok' => false, 'error' => $svc->getLastError() ?? 'Install/Update failed'];
            }

            // Remove backup
            if ($backupDir && is_dir($backupDir)) {
                $this->rrmdir($backupDir);
            }

            $this->invalidateCaches();

            return ['ok' => true, 'mode' => $mode, 'moduleKey' => $moduleKey, 'category' => $category];
        } catch (\Throwable $e) {
            // guaranteed rollback on any exception
            if (is_dir($stagingDir)) {
                $this->rrmdir($stagingDir);
            }
            if (is_dir($targetDir) && $backupDir && is_dir($backupDir)) {
                $this->rrmdir($targetDir);
                @rename($backupDir, $targetDir);
            }
            return ['ok' => false, 'error' => 'Deploy failed: ' . $e->getMessage()];
        }
    }

    private function invalidateCaches(): void
    {
        // list cache
        try {
            $cache = \Config\Services::cache();
            $cache->delete('modulecenter:listModules:v8_info_only');
        } catch (\Throwable $e) {}

        // discovery cache
        try { \Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry::clearCache(); } catch (\Throwable $e) {}

        if (function_exists('apcu_clear_cache')) @apcu_clear_cache();
        if (function_exists('opcache_reset')) @opcache_reset();
    }

    private function validateZip(string $zipPath): array
    {
        $za = new \ZipArchive();
        if ($za->open($zipPath) !== true) {
            return ['ok' => false, 'error' => 'Cannot open ZIP'];
        }

        $firstFile = null;
        for ($i = 0; $i < $za->numFiles; $i++) {
            $st = $za->statIndex($i);
            if (!$st) continue;
            $name = (string)($st['name'] ?? '');
            if ($name === '' || substr($name, -1) === '/') continue;
            $firstFile = $name;
            break;
        }

        if (!$firstFile) {
            $za->close();
            return ['ok' => false, 'error' => 'Empty ZIP'];
        }

        $parts = array_values(array_filter(explode('/', str_replace('\\', '/', $firstFile)), 'strlen'));

        $category = '';
        $moduleDirName = '';
        $prefix = '';

        if (($parts[0] ?? '') === 'modules') {
            $category = mb_strtolower($parts[1] ?? '');
            $moduleDirName = $parts[2] ?? '';
            $prefix = 'modules/' . $category . '/' . $moduleDirName;
        } else {
            $category = mb_strtolower($parts[0] ?? '');
            $moduleDirName = $parts[1] ?? '';
            $prefix = $category . '/' . $moduleDirName;
        }

        if (!in_array($category, $this->categories, true)) {
            $za->close();
            return ['ok' => false, 'error' => 'Invalid category in ZIP'];
        }
        if ($moduleDirName === '') {
            $za->close();
            return ['ok' => false, 'error' => 'Invalid module name in ZIP'];
        }

        $moduleKey = mb_strtolower($moduleDirName);

        $hasInfo = false;
        $hasInstallCtl = false;

        for ($i = 0; $i < $za->numFiles; $i++) {
            $st = $za->statIndex($i);
            if (!$st) continue;

            $name = (string)($st['name'] ?? '');
            if ($name === '') continue;

            $norm = str_replace('\\', '/', $name);

            if (strpos($norm, '../') !== false || str_starts_with($norm, '/') || preg_match('~^[A-Za-z]:/~', $norm)) {
                $za->close();
                return ['ok' => false, 'error' => 'Disallowed path in ZIP'];
            }

            if (substr($norm, -1) !== '/' && strpos($norm, $prefix . '/') !== 0) {
                $za->close();
                return ['ok' => false, 'error' => 'ZIP must contain a single module root'];
            }

            if (substr($norm, -1) === '/') continue;

            $base = basename($norm);
            $ext  = strtolower(pathinfo($norm, PATHINFO_EXTENSION));

            if (in_array($base, $this->denyBasenames, true) || in_array($ext, $this->denyExt, true)) {
                $za->close();
                return ['ok' => false, 'error' => 'Forbidden file in ZIP: ' . $base];
            }

            if (preg_match('~Config/Info\.php$~i', $norm)) {
                $hasInfo = true;
            }
            if (preg_match('~Controllers/InstallController\.php$~i', $norm)) {
                $hasInstallCtl = true;
            }
        }

        $za->close();

        if (!$hasInfo) {
            return ['ok' => false, 'error' => 'Config/Info.php is required'];
        }
        if (!$hasInstallCtl) {
            return ['ok' => false, 'error' => 'Controllers/InstallController.php is required'];
        }

        return [
            'ok' => true,
            'category' => $category,
            'moduleDirName' => $moduleDirName,
            'moduleKey' => $moduleKey,
            'prefix' => $prefix,
        ];
    }

    private function extractZip(string $zipPath, string $prefix, string $targetDir): void
    {
        $za = new \ZipArchive();
        if ($za->open($zipPath) !== true) {
            throw new \RuntimeException('Cannot open ZIP');
        }

        for ($i = 0; $i < $za->numFiles; $i++) {
            $st = $za->statIndex($i);
            if (!$st) continue;

            $name = (string)($st['name'] ?? '');
            if ($name === '') continue;

            $norm = str_replace('\\', '/', $name);

            if (substr($norm, -1) === '/') continue;
            if (strpos($norm, $prefix . '/') !== 0) continue;

            $rel = substr($norm, strlen($prefix) + 1);
            if ($rel === '' || strpos($rel, '../') !== false) continue;

            $out = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            $outDir = dirname($out);

            if (!is_dir($outDir) && !@mkdir($outDir, 0775, true) && !is_dir($outDir)) {
                $za->close();
                throw new \RuntimeException('Cannot create directory: ' . $outDir);
            }

            $stream = $za->getStream($name);
            if (!$stream) {
                $za->close();
                throw new \RuntimeException('Cannot read file from ZIP: ' . $name);
            }

            $data = stream_get_contents($stream);
            fclose($stream);

            if ($data === false) {
                $za->close();
                throw new \RuntimeException('Cannot extract file: ' . $name);
            }

            if (@file_put_contents($out, $data) === false) {
                $za->close();
                throw new \RuntimeException('Cannot write file: ' . $out);
            }
        }

        $za->close();
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
