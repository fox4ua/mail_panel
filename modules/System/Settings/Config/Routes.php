<?php

declare(strict_types=1);

namespace Modules\System\Settings\Config;

use CodeIgniter\Router\RouteCollection;

/**
 * Settings routes
 *
 * URLs:
 * - GET  /admin/system/settings
 * - POST /admin/system/settings/save
 */
return static function (RouteCollection $routes): void {
    $routes->group('admin/system', [
        'namespace' => 'Modules\System\Settings\Controllers',
        // Замените на ваш реальный фильтр админки/авторизации:
    ], static function (RouteCollection $routes): void {
        $routes->get('settings', 'SettingsController::index');
        $routes->post('settings/save', 'SettingsController::save');

        // (опционально) алиас, если хотите открывать настройки по /admin/system
        // $routes->get('', 'SettingsController::index');
    });
};
