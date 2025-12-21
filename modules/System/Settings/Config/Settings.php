<?php

declare(strict_types=1);

namespace Modules\System\Settings\Config;

use CodeIgniter\Config\BaseConfig;

class Settings extends BaseConfig
{
    /**
     * Включить кеш значений (SettingsStore)
     */
    public bool $cacheEnabled = true;

    /**
     * TTL кеша одиночных значений (сек)
     */
    public int $cacheTtl = 300;

    /**
     * TTL кеша autoload списка (сек)
     */
    public int $autoloadCacheTtl = 300;

    /**
     * Максимальная длина ключа
     */
    public int $maxKeyLength = 191;
}
