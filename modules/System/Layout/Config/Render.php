<?php
declare(strict_types=1);

namespace Modules\System\Layout\Config;

use CodeIgniter\Config\BaseConfig;

class Render extends BaseConfig
{
    /**
     * Полный путь к общему шаблону.
     * По умолчанию: modules/System/Layout/Views/layouts/main.php
     */
    public string $layoutFile = ROOTPATH . 'modules/System/Layout/Views/layouts/main.php';

    /**
     * Если контроллер находится в modules/<cat>/<Module>/Controllers/*,
     * то view будет искаться в modules/<cat>/<Module>/Views/<view>.php
     */
    public bool $useModuleViews = true;

    /**
     * Если контроллер не модульный или view не найдено в модуле —
     * разрешить fallback в app/Views/<view>.php
     */
    public bool $fallbackToAppViews = true;

    /**
     * Дедуп ассетов:
     * true  => /app.css?v=1 и /app.css?v=2 считаются одним (по пути без query/hash)
     * false => считаются разными
     */
    public bool $dedupeIgnoreQuery = true;

    /**
     * подключение CSS и JS файлов по HTTP / HTTPS:
     * true  => разрешить HTTP
     * false => запретить HTTP
     */
    public bool $assetsAllowHttp = false;

    public array $layoutModules = [
        'menu' => 'Blocks/Menu:menu',
    ];
}
