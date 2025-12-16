<?php

namespace Modules\System\Core\Controllers;

use CodeIgniter\Controller;

abstract class AdminBaseController extends Controller
{
    protected array $data = [];

    protected function render(string $view, array $data = [], array $options = []): string
    {
        $data = array_merge($this->data, $data);

        $layoutClass = 'Modules\\System\\Layout\\Libraries\\Layout\\LayoutManager';
        if (class_exists($layoutClass)) {
            $layout = new $layoutClass();
            return $layout->render($view, $data, array_merge(['area' => 'admin'], $options));
        }

        return view($view, $data);
    }
}
