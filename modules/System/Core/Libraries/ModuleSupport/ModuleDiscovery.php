<?php

namespace Modules\System\Core\Libraries\ModuleSupport;

use RuntimeException;

class ModuleDiscovery
{
    private array $allowCategories = ['system', 'pages', 'blocks'];

    public function discover(string $modulesRoot): array
    {
        $modulesRoot = rtrim($modulesRoot, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($modulesRoot)) return [];

        $categoryDirs = glob($modulesRoot . '*', GLOB_ONLYDIR) ?: [];

        $found = [];
        foreach ($categoryDirs as $catPath) {
            $catName = basename($catPath);
            if (!in_array(mb_strtolower($catName), $this->allowCategories, true)) continue;

            $moduleDirs = glob($catPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
            foreach ($moduleDirs as $modPath) {
                $modName = basename($modPath);

                // NEW: Only Config/Info.php is a valid module descriptor.
                $infoFile = $modPath . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Info.php';
                if (!is_file($infoFile)) {
                    continue;
                }

                $nsCategory = $this->studly($catName);
                $nsModule   = $this->studly($modName);

                $namespace = 'Modules\\' . $nsCategory . '\\' . $nsModule;
                $infoClass = $namespace . '\\Config\\Info';

                $found[] = new ModuleInfo([
                    'category'  => mb_strtolower($catName),
                    'module'    => $modName,
                    'path'      => $modPath,
                    'namespace' => $namespace,
                    'infoClass' => $infoClass,
                ]);
            }
        }

        return $found;
    }

    public function loadInfo(ModuleInfo $info): object
    {
        // Load the file explicitly (do not rely on Composer/PSR-4 correctness)
        $file = rtrim($info->path, '/\\') . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Info.php';
        if (!is_file($file)) {
            throw new RuntimeException('Info file not found: ' . $file);
        }

        try {
            (static function(string $__file) { require_once $__file; })($file);
        } catch (\Throwable $e) {
            throw new RuntimeException('Info file load failed: ' . $file . ' | ' . $e->getMessage());
        }

        if (!class_exists($info->infoClass)) {
            throw new RuntimeException('Info class not found: ' . $info->infoClass);
        }

        $info->info = new ($info->infoClass)();
        return $info->info;
    }

    private function studly(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9]+/', ' ', $name);
        $name = str_replace(' ', '', ucwords(trim((string)$name)));
        return $name === '' ? 'Module' : $name;
    }
}
