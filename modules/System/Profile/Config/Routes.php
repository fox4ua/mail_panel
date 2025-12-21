<?php

declare(strict_types=1);

namespace Modules\System\Profile\Config;

use CodeIgniter\Router\RouteCollection;

/**
 * User Profile routes
 *
 * URLs:
 * - GET  /account/profile
 * - POST /account/profile/save
 *
 * Adjust filters to your project.
 */
return static function (RouteCollection $routes): void {
    $routes->group('account', [
        'namespace' => 'Modules\System\Profile\Controllers',
        // Replace with your real auth filter:
    ], static function (RouteCollection $routes): void {
        $routes->get('profile', 'ProfileController::index');
        $routes->post('profile/save', 'ProfileController::save');
    });
};
