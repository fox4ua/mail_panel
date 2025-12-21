<?php

declare(strict_types=1);

namespace Modules\System\Settings\Libraries;

use CodeIgniter\Cache\CacheInterface;
use Modules\System\Settings\Config\Settings as SettingsConfig;
use Modules\System\Settings\Models\SettingModel;

/**
 * SettingsStore — низкоуровневое key/value хранилище (с кешем).
 * Без схемы. Для "нормальной" админки используйте SettingsManager.
 */
class SettingsStore
{
    private SettingModel $model;
    private CacheInterface $cache;
    private SettingsConfig $cfg;

    public function __construct(?SettingModel $model = null, ?CacheInterface $cache = null, ?SettingsConfig $cfg = null)
    {
        $this->model = $model ?? new SettingModel();
        $this->cache = $cache ?? cache();
        $this->cfg   = $cfg ?? config(SettingsConfig::class);
    }

    public function getRaw(string $key): ?array
    {
        return $this->model->findByKey($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = trim($key);
        if ($key === '') {
            return $default;
        }

        $cacheKey = $this->cacheKey($key);

        if ($this->cfg->cacheEnabled) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached) && array_key_exists('type', $cached)) {
                return $this->cast((string) $cached['type'], $cached['value'] ?? null, $default);
            }
        }

        $row = $this->model->findByKey($key);
        if (!$row) {
            return $default;
        }

        $type  = (string) ($row['type'] ?? 'string');
        $value = $row['setting_value'] ?? null;

        if ($this->cfg->cacheEnabled) {
            $this->cache->save($cacheKey, ['type' => $type, 'value' => $value], $this->cfg->cacheTtl);
        }

        return $this->cast($type, $value, $default);
    }

    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general',
        int $autoload = 1,
        ?string $description = null
    ): int {
        $key = trim($key);
        if ($key === '') {
            throw new \InvalidArgumentException('Setting key is empty');
        }

        $stored = $this->serializeValue($type, $value);

        $id = $this->model->upsertByKey($key, $stored, $type, $group, $autoload, $description);

        $this->clear($key);
        $this->clearAutoload();

        return $id;
    }

    public function autoload(): array
    {
        $cacheKey = 'settings_autoload_list';

        if ($this->cfg->cacheEnabled) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $rows = $this->model->listAutoload();
        $out  = [];

        foreach ($rows as $r) {
            $k = (string) ($r['setting_key'] ?? '');
            if ($k === '') {
                continue;
            }
            $out[$k] = $this->cast((string) ($r['type'] ?? 'string'), $r['setting_value'] ?? null, null);
        }

        if ($this->cfg->cacheEnabled) {
            $this->cache->save($cacheKey, $out, $this->cfg->autoloadCacheTtl);
        }

        return $out;
    }

    public function clear(string $key): void
    {
        if (!$this->cfg->cacheEnabled) {
            return;
        }
        $this->cache->delete($this->cacheKey($key));
    }

    public function clearAutoload(): void
    {
        if (!$this->cfg->cacheEnabled) {
            return;
        }
        $this->cache->delete('settings_autoload_list');
    }

    private function cacheKey(string $key): string
    {
        return 'settings_' . sha1($key);
    }

    private function cast(string $type, mixed $value, mixed $default): mixed
    {
        if ($value === null) {
            return $default;
        }

        return match ($type) {
            'int'   => (int) $value,
            'bool'  => ($value === '1' || $value === 1 || $value === true || $value === 'true'),
            'float' => (float) $value,
            'json'  => $this->decodeJson((string) $value, $default),
            'text', 'string', 'select' => (string) $value,
            default => (string) $value,
        };
    }

    private function serializeValue(string $type, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'bool'  => ($value ? '1' : '0'),
            'int'   => (string) ((int) $value),
            'float' => (string) ((float) $value),
            'json'  => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'text', 'string', 'select' => (string) $value,
            default => (string) $value,
        };
    }

    private function decodeJson(string $json, mixed $default): mixed
    {
        $json = trim($json);
        if ($json === '') {
            return $default;
        }

        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }

        return $decoded;
    }
}
