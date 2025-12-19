<?php

declare(strict_types=1);

namespace Modules\Pages\HelloWorld\Controllers;

use Modules\System\Core\Controllers\CoreBaseController;

final class Hello extends CoreBaseController
{
    public function index(): string
    {

      $this->render->addTitle('HelloWorld');
    // или:
    // $this->render->addTitle('Домены')->addTitle('Mail Admin', true);

          $this->render
        ->addCss('/assets/helloworld.css')
        ->addJs('/assets/shared.js', 'body', ['defer' => true]);
        return $this->render->view('hello', [
            'name'  => 'World',
        ]);
    }
}
