<?php

declare(strict_types=1);

namespace Modules\System\Menu\Config;

use CodeIgniter\Router\RouteCollection;

/**
 * Menu management routes
 *
 * URLs:
 * - GET  /admin/system/menus
 * - GET  /admin/system/menus/create
 * - POST /admin/system/menus/store
 * - GET  /admin/system/menus/edit/{id}
 * - POST /admin/system/menus/update/{id}
 * - POST /admin/system/menus/delete/{id}
 *
 * Items:
 * - GET  /admin/system/menus/{menuId}/items
 * - GET  /admin/system/menus/{menuId}/items/create
 * - POST /admin/system/menus/{menuId}/items/store
 * - GET  /admin/system/menus/{menuId}/items/edit/{itemId}
 * - POST /admin/system/menus/{menuId}/items/update/{itemId}
 * - POST /admin/system/menus/{menuId}/items/delete/{itemId}
 */
return static function (RouteCollection $routes): void {
    $routes->group('admin/system', [
        'namespace' => 'Modules\System\Menu\Controllers',
        // Replace with your real admin/auth filter:
    ], static function (RouteCollection $routes): void {

        $routes->get('menus', 'MenusController::index');
        $routes->get('menus/create', 'MenusController::create');
        $routes->post('menus/store', 'MenusController::store');
        $routes->get('menus/edit/(:num)', 'MenusController::edit/$1');
        $routes->post('menus/update/(:num)', 'MenusController::update/$1');
        $routes->post('menus/delete/(:num)', 'MenusController::delete/$1');

        $routes->get('menus/(:num)/items', 'MenuItemsController::index/$1');
        $routes->get('menus/(:num)/items/create', 'MenuItemsController::create/$1');
        $routes->post('menus/(:num)/items/store', 'MenuItemsController::store/$1');
        $routes->get('menus/(:num)/items/edit/(:num)', 'MenuItemsController::edit/$1/$2');
        $routes->post('menus/(:num)/items/update/(:num)', 'MenuItemsController::update/$1/$2');
        $routes->post('menus/(:num)/items/delete/(:num)', 'MenuItemsController::delete/$1/$2');

        // Quick toggles (AJAX optional)
        $routes->post('menus/(:num)/items/toggle/(:num)', 'MenuItemsController::toggle/$1/$2');
    });
};
