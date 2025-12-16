<?php

namespace Modules\Pages\HelloWorld\Config;

class Module
{
    public string $name   = 'helloworld';
    public string $title  = 'Hello World';
    public int    $weight = 100;

    public function menu(): array
    {
        return [
            [
                'group'  => 'pages',
                'icon'   => 'bi bi-emoji-smile',
                'label'  => 'Hello World',

                // ВАЖНО:
                // В вашей текущей реализации меню поле "route" используется как URI-путь.
                // Поэтому здесь должен быть путь, а не имя алиаса.
                'route'  => 'hello',

                // (опционально на будущее) имя алиаса, если захотите:
                // 'route_name' => 'helloworld.index',

                'weight' => 100,
            ],
        ];
    }

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }
}
