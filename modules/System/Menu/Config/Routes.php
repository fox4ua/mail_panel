<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('admin/system', ['namespace' => 'Modules\System\Menu\Controllers'], static function($routes) {
    $routes->get('menu', 'MenuController::index');
    $routes->get('menu/create', 'MenuController::create');
    $routes->post('menu/create', 'MenuController::store');
    $routes->get('menu/edit/(:num)', 'MenuController::edit/$1');
    $routes->post('menu/edit/(:num)', 'MenuController::update/$1');
    $routes->post('menu/delete/(:num)', 'MenuController::delete/$1');
    $routes->post('menu/toggle/(:num)', 'MenuController::toggle/$1');
    $routes->post('menu/sync', 'MenuController::sync');
});
