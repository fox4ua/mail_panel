<?php

declare(strict_types=1);

namespace Modules\System\Core\Libraries;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Throwable;

/**
 * Module Services autoloader:
 * - Сканирует modules/<Category>/<Module>/Config/Services.php по allowlist (в заданном порядке)
 * - Строит карту serviceName => [FQCN, method]
 * - Кэширует карту в WRITEPATH/cache/module_services_map.php
 *
 * Инвалидация кэша (оптимальный подход):
 * - production: НЕ делает glob/filemtime на каждый запрос (максимальная производительность)
 *   => кэш обновляется только при деплое/очистке cache или если cache отсутствует
 * - non-production: проверяет актуальность (signature по списку Services.php + mtime)
 *
 * Рекомендация для продакшна:
 * - На деплое вызывайте ServiceAutoloader::clearCache() или просто удаляйте cache-файл
 * - И прогревайте один раз (ServiceAutoloader::warmUp()).
 */
final class ServiceAutoloader
{
    /** @var array<string, array{0:string,1:string}>|null */
    private static ?array $map = null;

    /** Файл кэша (php-return массив) */
    private const CACHE_FILE = 'module_services_map.php';

    /**
     * Allowlist модулей в фиксированном порядке.
     * Ключ: Category, значение: список модулей (['*'] = все модули в категории).
     *
     * ПОРЯДОК ЭТОГО МАССИВА = ПРИОРИТЕТ (раньше = выше).
     */
    private static array $allowlist = [
        'System' => ['*'],
        'Pages'  => ['*'],
        'Blocks' => ['*'],
    ];

    /**
     * В non-production логируем детально, в production — минимально.
     * Логирование делается через error_log (без зависимости от CI Services).
     */
    private static bool $logErrors = true;

    /**
     * Для dev удобно ловить дубли сервисов.
     * В production лучше не падать из-за дублей — оставляем false.
     */
    private static bool $throwOnDuplicateInDev = true;

    public static function resolve(string $name, array $arguments = [])
    {
        $map = self::map();

        if (!isset($map[$name])) {
            throw new RuntimeException('Service not found in modules: ' . $name);
        }

        [$class, $method] = $map[$name];

        if (!class_exists($class)) {
            throw new RuntimeException('Service class not found: ' . $class);
        }
        if (!is_callable([$class, $method])) {
            throw new RuntimeException('Service is not callable: ' . $class . '::' . $method);
        }

        // Передаём исходные аргументы (CI4 сервисы обычно имеют bool $getShared=true)
        return $class::$method(...$arguments);
    }

    /**
     * Принудительная очистка кэша.
     */
    public static function clearCache(): void
    {
        self::$map = null;
        $file = self::cachePath();
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Прогрев карты (полезно на деплое).
     */
    public static function warmUp(): void
    {
        self::$map = null;
        self::map(); // построит и сохранит кэш
    }

    /**
     * @return array<string, array{0:string,1:string}>
     */
    private static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        // 1) пробуем кэш
        $cached = self::loadCache();
        if (is_array($cached)) {
            self::$map = $cached;
            return self::$map;
        }

        // 2) строим заново
        try {
            $files = self::discoverServiceFilesDeterministic();
            $map   = self::buildMapFromFiles($files);

            self::$map = $map;

            // 3) сохраняем кэш
            self::saveCache($map, $files);

            return self::$map;
        } catch (Throwable $e) {
            self::log('error', 'ServiceAutoloader map() failed: ' . $e->getMessage(), $e);

            // В production лучше не маскировать проблему: сервисы не найдутся
            throw $e;
        }
    }

    /**
     * Детерминированный список Services.php:
     * - порядок категорий/модулей задан allowlist
     * - внутри категории файлы сортируются
     *
     * @return string[]
     */
    private static function discoverServiceFilesDeterministic(): array
    {
        $base = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules';
        if (!is_dir($base)) {
            return [];
        }

        $out = [];

        foreach (self::$allowlist as $category => $modules) {
            $category = trim((string) $category);
            if ($category === '') {
                continue;
            }

            $mods = is_array($modules) ? $modules : ['*'];
            if ($mods === []) {
                continue;
            }

            // '*' означает: все модули в категории
            if (in_array('*', $mods, true)) {
                $pattern = $base
                    . DIRECTORY_SEPARATOR . $category
                    . DIRECTORY_SEPARATOR . '*'
                    . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'Services.php';

                $files = glob($pattern) ?: [];
                sort($files, SORT_STRING);
                foreach ($files as $f) {
                    $out[] = $f;
                }
                continue;
            }

            // иначе: только перечисленные модули (в указанном порядке)
            foreach ($mods as $mod) {
                $mod = trim((string) $mod);
                if ($mod === '') {
                    continue;
                }

                $path = $base
                    . DIRECTORY_SEPARATOR . $category
                    . DIRECTORY_SEPARATOR . $mod
                    . DIRECTORY_SEPARATOR . 'Config'
                    . DIRECTORY_SEPARATOR . 'Services.php';

                if (is_file($path)) {
                    $out[] = $path;
                }
            }
        }

        // на всякий случай: убираем дубли
        $out = array_values(array_unique($out));

        return $out;
    }

    /**
     * @param string[] $files
     * @return array<string, array{0:string,1:string}>
     */
    private static function buildMapFromFiles(array $files): array
    {
        $modulesBase = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'modules';
        $map = [];

        foreach ($files as $file) {
            // modules/<Cat>/<Mod>/Config/Services.php
            $relative = str_replace($modulesBase, '', $file);
            $parts = preg_split('~[/\\\\]+~', $relative, -1, PREG_SPLIT_NO_EMPTY);

            if (!$parts || count($parts) < 4) {
                continue;
            }

            $cat = $parts[0];
            $mod = $parts[1];

            $class = 'Modules\\' . $cat . '\\' . $mod . '\\Config\\Services';

            if (!class_exists($class)) {
                // либо нет PSR-4 Modules => ROOTPATH/modules, либо модуль невалидный
                continue;
            }

            try {
                $ref = new ReflectionClass($class);
                $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
            } catch (Throwable $e) {
                self::log('error', 'Failed to reflect services class: ' . $class . ' (' . $e->getMessage() . ')', $e);
                continue;
            }

            foreach ($methods as $m) {
                $mn = $m->getName();

                if (str_starts_with($mn, '__')) {
                    continue;
                }

                // детерминированно: первый найденный выигрывает (порядок файлов уже зафиксирован)
                if (!isset($map[$mn])) {
                    $map[$mn] = [$class, $mn];
                    continue;
                }

                // дубликат
                if (!self::isProduction() && self::$throwOnDuplicateInDev) {
                    $prev = $map[$mn][0] . '::' . $map[$mn][1];
                    $cur  = $class . '::' . $mn;
                    throw new RuntimeException('Duplicate service "' . $mn . '": ' . $prev . ' vs ' . $cur);
                }

                self::log('warning', 'Duplicate service ignored: ' . $mn . ' (kept ' . $map[$mn][0] . '::' . $map[$mn][1] . ', skipped ' . $class . '::' . $mn . ')');
            }
        }

        return $map;
    }

    /**
     * Production-оптимизация:
     * - production: если кэш есть — используем без проверки свежести (O(1) на запрос)
     * - non-production: проверяем signature (список Services.php + mtime), иначе пересобираем
     *
     * @return array<string, array{0:string,1:string}>|null
     */
    private static function loadCache(): ?array
    {
        $file = self::cachePath();
        if (!is_file($file)) {
            return null;
        }

        try {
            /** @var mixed $payload */
            $payload = include $file;
        } catch (Throwable $e) {
            self::log('error', 'Failed to include cache file: ' . $file . ' (' . $e->getMessage() . ')', $e);
            return null;
        }

        if (!is_array($payload) || !isset($payload['map'])) {
            return null;
        }
        if (!is_array($payload['map'])) {
            return null;
        }

        // production: не делаем glob/filemtime на каждый запрос
        if (self::isProduction()) {
            /** @var array<string, array{0:string,1:string}> $map */
            $map = $payload['map'];
            return $map;
        }

        // non-production: проверяем свежесть
        $sigCached = isset($payload['signature']) ? (string) $payload['signature'] : '';
        if ($sigCached === '') {
            return null;
        }

        $filesNow = self::discoverServiceFilesDeterministic();
        $sigNow   = self::signature($filesNow);

        if (!hash_equals($sigCached, $sigNow)) {
            return null;
        }

        /** @var array<string, array{0:string,1:string}> $map */
        $map = $payload['map'];
        return $map;
    }

    /**
     * @param array<string, array{0:string,1:string}> $map
     * @param string[] $files
     */
    private static function saveCache(array $map, array $files): void
    {
        $dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            self::log('warning', 'Cache directory not writable: ' . $dir);
            return;
        }

        $cacheFile = self::cachePath();

        $payload = [
            'built_at'  => time(),
            // signature нужна только для non-production, но хранить можно всегда
            'signature' => self::signature($files),
            'map'       => $map,
        ];

        $export = var_export($payload, true);
        $php = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . $export . ";\n";

        $tmp = $cacheFile . '.tmp.' . bin2hex(random_bytes(6));

        if (@file_put_contents($tmp, $php, LOCK_EX) === false) {
            @unlink($tmp);
            self::log('warning', 'Failed to write cache tmp file: ' . $tmp);
            return;
        }

        @rename($tmp, $cacheFile);
    }

    /**
     * Сигнатура = sha1 от списка файлов и их mtime в фиксированном порядке.
     * (Используется только в non-production для авто-инвалидации.)
     *
     * @param string[] $files
     */
    private static function signature(array $files): string
    {
        $chunks = [];
        foreach ($files as $f) {
            $mt = @filemtime($f) ?: 0;
            $chunks[] = $f . ':' . $mt;
        }
        return sha1(implode('|', $chunks));
    }

    private static function cachePath(): string
    {
        return rtrim(WRITEPATH, '/\\')
            . DIRECTORY_SEPARATOR . 'cache'
            . DIRECTORY_SEPARATOR . self::CACHE_FILE;
    }

    private static function isProduction(): bool
    {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'production';
    }

    private static function log(string $level, string $message, ?Throwable $e = null): void
    {
        if (!self::$logErrors) {
            return;
        }

        $isProd = self::isProduction();

        // production: минимально
        if ($isProd) {
            // warning/error только
            if ($level !== 'error' && $level !== 'warning') {
                return;
            }
            error_log('[ServiceAutoloader][' . $level . '] ' . $message);
            return;
        }

        // non-production: детально
        $extra = '';
        if ($e) {
            $extra = ' | ' . get_class($e) . ': ' . $e->getMessage();
        }
        error_log('[ServiceAutoloader][' . $level . '] ' . $message . $extra);
    }
}
