# System/Settings module (CI4 HMVC) — normal "Site Settings"

## What this module gives
- Таблица `settings` (key/value)

> Note: cache keys are safe for CI4 handlers (no reserved characters).
- Админ-страница настроек с типовыми параметрами:
  - Site title, tagline, items per page
  - SEO meta (description/keywords/robots/OG image)
  - Email defaults (from/reply-to)
  - System (timezone, locale, CDN base URL)
  - Maintenance mode + message
- Кеширование значений (SettingsStore)
- Схема настроек (SettingsSchema): дефолты, группы, валидация, типы
- SettingsManager: удобный доступ + saveFromArray + shareToRenderer()

## Installation
1) Copy:
`ROOTPATH/modules/System/Settings`

2) PSR-4 autoload for modules (пример):
`app/Config/Autoload.php`
```php
public $psr4 = [
  'Modules' => ROOTPATH . 'modules',
];
```

3) Подключите роуты модуля (если у вас авто-подхват Routes.php из modules — просто проверьте путь):
`modules/System/Settings/Config/Routes.php`

4) Создайте таблицу (без Spark):
```sql
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_name` VARCHAR(64) NOT NULL DEFAULT 'general',
  `setting_key` VARCHAR(191) NOT NULL,
  `setting_value` LONGTEXT NULL,
  `type` VARCHAR(16) NOT NULL DEFAULT 'string',
  `autoload` TINYINT(1) NOT NULL DEFAULT 1,
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## URLs
- GET  /admin/system/settings
- POST /admin/system/settings/save

Filter in Routes.php set to `auth` by default — replace with your admin filter.

## Usage in code
```php
use Modules\System\Settings\Libraries\SettingsManager;

$settings = new SettingsManager();

$title = $settings->get('site.title');
$tz    = $settings->get('app.timezone');
```

## Share to your Layout Renderer
```php
use Modules\System\Settings\Libraries\SettingsManager;

$settings = new SettingsManager();
$settings->shareToRenderer($this->render); // or $render
```

Then in layout:
- `$siteTitle`, `$metaDescription`, `$maintenanceEnabled` etc. (see SettingsSchema::$shareMap)
