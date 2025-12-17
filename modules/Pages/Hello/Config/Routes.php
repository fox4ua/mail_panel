<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('', ['namespace' => 'Modules\\Pages\\Hello\\Controllers'], static function ($routes) {
    $routes->get('hello', 'HelloController::index', ['as' => 'hello.index']);
});
