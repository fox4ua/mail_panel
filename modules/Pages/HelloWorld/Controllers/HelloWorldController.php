<?php

namespace Modules\Pages\HelloWorld\Controllers;

use CodeIgniter\Controller;

class HelloWorldController extends Controller
{
    public function index()
    {
        // Namespaced view из модуля
        return view('\Modules\Pages\HelloWorld\Views\hello', [
            'title' => 'Hello World',
            'time'  => date('Y-m-d H:i:s'),
        ]);
    }
}
