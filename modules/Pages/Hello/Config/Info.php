<?php

namespace Modules\Pages\Hello\Config;

class Info
{
    public string $name    = 'hello';
    public string $title   = 'Hello Page';
    public string $version = '1.0.0';
    public int    $weight  = 100;

    /** @var string[] */
    public array $requires = ['system/core', 'system/layout', 'system/menu'];
}
