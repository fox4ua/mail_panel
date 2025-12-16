<?php

namespace Modules\System\ModuleCenter\Libraries\ModuleCenter;

use Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry;
use CodeIgniter\Router\RouteCollection;

class RouteConflictDetector
{
    public static function scanEnabled(ModuleRegistry $registry, array $enabledMap, ?string $excludeModule = null): array
    {
        return self::scan($registry, $enabledMap, $excludeModule, null, null);
    }

    public static function scanWithExtra(ModuleRegistry $registry, array $enabledMap, ?string $excludeModule, string $extraRoutesFile, string $extraModuleKey): array
    {
        return self::scan($registry, $enabledMap, $excludeModule, $extraRoutesFile, $extraModuleKey);
    }

    private static function scan(ModuleRegistry $registry, array $enabledMap, ?string $excludeModule, ?string $extraRoutesFile, ?string $extraModuleKey): array
    {
        $excludeModule  = $excludeModule ? mb_strtolower($excludeModule) : null;
        $extraModuleKey = $extraModuleKey ? mb_strtolower($extraModuleKey) : null;

        $routes = null;
        try {
            $routes = \Config\Services::routes(false);
        } catch (\Throwable $e) {
            try { $routes = service('routes'); } catch (\Throwable $e2) { $routes = null; }
        }
        if (!$routes instanceof RouteCollection) {
            return [];
        }

        $conflicts = [];
        $ownerUri  = [];
        $ownerName = [];

        $infos = $registry->all();
        usort($infos, static fn($a, $b) => strcmp(mb_strtolower($a->module), mb_strtolower($b->module)));

        foreach ($infos as $info) {
            $mKey = mb_strtolower($info->module);
            if (!isset($enabledMap[$mKey])) continue;
            if ($excludeModule && $mKey === $excludeModule) continue;

            $file = rtrim($info->path, '/\\') . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
            if (!is_file($file)) continue;

            self::requireRoutesFile($routes, $file, $mKey, $conflicts, $ownerUri, $ownerName);
        }

        if ($extraRoutesFile && is_file($extraRoutesFile) && $extraModuleKey) {
            self::requireRoutesFile($routes, $extraRoutesFile, $extraModuleKey, $conflicts, $ownerUri, $ownerName);
        }

        return $conflicts;
    }

    private static function requireRoutesFile(RouteCollection $routes, string $file, string $moduleKey, array &$conflicts, array &$ownerUri, array &$ownerName): void
    {
        $before = self::snapshot($routes);

        (static function(RouteCollection $routes, string $__file) {
            require $__file;
        })($routes, $file);

        $after = self::snapshot($routes);

        foreach ($after['flat'] as $k => $v) {
            if (!array_key_exists($k, $before['flat'])) {
                if (!isset($ownerUri[$k])) $ownerUri[$k] = $moduleKey;
                continue;
            }
            if ($before['flat'][$k] !== $v) {
                $conflicts[$moduleKey]['uri'][] = [
                    'key' => $k,
                    'prev_owner' => $ownerUri[$k] ?? 'unknown',
                ];
            }
        }

        foreach ($after['named'] as $name => $route) {
            if (!array_key_exists($name, $before['named'])) {
                if (!isset($ownerName[$name])) $ownerName[$name] = $moduleKey;
                continue;
            }
            if ($before['named'][$name] !== $route) {
                $conflicts[$moduleKey]['name'][] = [
                    'key' => $name,
                    'prev_owner' => $ownerName[$name] ?? 'unknown',
                ];
            }
        }
    }

    private static function snapshot(RouteCollection $routes): array
    {
        $flat  = [];
        $named = [];

        try {
            $ref = new \ReflectionClass($routes);

            if ($ref->hasProperty('routes')) {
                $p = $ref->getProperty('routes');
                $p->setAccessible(true);
                $raw = $p->getValue($routes);
                if (is_array($raw)) {
                    foreach ($raw as $method => $map) {
                        if (!is_array($map)) continue;
                        foreach ($map as $from => $to) {
                            $key = strtoupper((string)$method) . ' ' . (string)$from;
                            $flat[$key] = $to;
                        }
                    }
                }
            }

            if ($ref->hasProperty('namedRoutes')) {
                $p = $ref->getProperty('namedRoutes');
                $p->setAccessible(true);
                $raw = $p->getValue($routes);
                if (is_array($raw)) {
                    $named = $raw;
                }
            }
        } catch (\Throwable $e) {}

        return ['flat' => $flat, 'named' => $named];
    }
}
