<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('admin/system', ['namespace' => 'Modules\System\Blocks\Controllers'], static function($routes) {
    $routes->get('blocks', 'BlocksController::index');
    $routes->get('blocks/create', 'BlocksController::create');
    $routes->post('blocks/create', 'BlocksController::store');
    $routes->get('blocks/edit/(:num)', 'BlocksController::edit/$1');
    $routes->post('blocks/edit/(:num)', 'BlocksController::update/$1');
    $routes->post('blocks/delete/(:num)', 'BlocksController::delete/$1');

    $routes->get('blocks/placements', 'PlacementsController::index');
    $routes->post('blocks/placements/add', 'PlacementsController::add');
    $routes->post('blocks/placements/delete/(:num)', 'PlacementsController::delete/$1');
});
