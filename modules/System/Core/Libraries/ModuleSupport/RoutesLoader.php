<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$modulesRoot = rtrim(ROOTPATH . 'modules', '/\\') . DIRECTORY_SEPARATOR;
if (!is_dir($modulesRoot)) return;

$allow = ['system', 'pages', 'blocks'];

$enabledMap = null;
try {
    if (class_exists('Modules\\System\\ModuleCenter\\Models\\ModuleModel')) {
        $mm = new \Modules\System\ModuleCenter\Models\ModuleModel();
        $rows = $mm->findAll();
        $enabledMap = [];
        foreach ($rows as $r) {
            $enabledMap[mb_strtolower($r['name'])] = (int)$r['is_enabled'] === 1;
        }
    }
} catch (\Throwable $e) {
    $enabledMap = null;
}

$routeFiles = [];
$categoryDirs = glob($modulesRoot . '*', GLOB_ONLYDIR) ?: [];
foreach ($categoryDirs as $catPath) {
    $cat = basename($catPath);
    if (!in_array(mb_strtolower($cat), $allow, true)) continue;

    $pattern = $catPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php';
    $routeFiles = array_merge($routeFiles, glob($pattern) ?: []);
}

sort($routeFiles, SORT_STRING);

foreach ($routeFiles as $file) {
    if (!is_file($file)) continue;

    $moduleDir  = basename(dirname(dirname($file)));
    $moduleName = mb_strtolower($moduleDir);

    $categoryDir = basename(dirname(dirname(dirname($file))));
    $category    = mb_strtolower($categoryDir);

    // System modules always load routes
    if ($category !== 'system') {
        // If ModuleCenter is present (enabledMap is array) -> require installed+enabled
        if (is_array($enabledMap)) {
            if (!array_key_exists($moduleName, $enabledMap)) {
                continue; // not installed
            }
            if ($enabledMap[$moduleName] === false) {
                continue; // disabled
            }
        }
    } else {
        // If explicitly disabled in DB (legacy), respect it
        if (is_array($enabledMap) && array_key_exists($moduleName, $enabledMap) && $enabledMap[$moduleName] === false) {
            continue;
        }
    }

if ($category !== 'system') {
    // Guard module routes as a second line of defense (requires filter alias "moduleEnabled")
    $routes->group('', ['filter' => 'moduleEnabled'], static function($routes) use ($file) {
        require $file;
    });
} else {
    require $file;
}
}
