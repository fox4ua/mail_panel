<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'Modules\Pages\HelloWorld\Controllers'], static function($routes) {
    $routes->get('hello', 'HelloWorldController::index', ['as' => 'helloworld.index']);
});
