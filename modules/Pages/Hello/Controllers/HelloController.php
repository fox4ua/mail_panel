<?php

namespace Modules\Pages\Hello\Controllers;

use CodeIgniter\Controller;

class HelloController extends Controller
{
    public function index()
    {
        return view('\\Modules\\Pages\\Hello\\Views\\hello', [
            'title' => 'Hello',
            'time'  => date('Y-m-d H:i:s'),
        ]);
    }
}
