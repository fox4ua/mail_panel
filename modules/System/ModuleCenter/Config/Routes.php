<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('admin/system/modules', [
    'namespace' => 'Modules\\System\\ModuleCenter\\Controllers',
], static function ($routes) {
    $routes->get('/', 'ModulesController::index', ['as' => 'modules.index']);
    $routes->post('rescan', 'ModulesController::rescan', ['as' => 'modules.rescan']);
    // страница формы загрузки
    $routes->get('upload', 'ModuleInstallController::uploadForm');
    $routes->post('enable/(:segment)',  'ModulesController::enable/$1',  ['as' => 'modules.enable']);
    $routes->post('disable/(:segment)', 'ModulesController::disable/$1', ['as' => 'modules.disable']);

    $routes->post('upload', 'ModuleInstallController::upload', ['as' => 'modules.upload']);
    $routes->post('install/(:segment)',   'ModuleInstallController::install/$1',   ['as' => 'modules.install']);
    $routes->post('uninstall/(:segment)', 'ModuleInstallController::uninstall/$1', ['as' => 'modules.uninstall']);
});
