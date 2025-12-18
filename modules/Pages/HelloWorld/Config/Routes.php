<?php

declare(strict_types=1);

namespace Modules\Pages\HelloWorld\Config;

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('', ['namespace' => 'Modules\Pages\HelloWorld\Controllers'], static function ($routes) {
    $routes->get('hello', 'Hello::index', ['as' => 'hello.index']);
});
