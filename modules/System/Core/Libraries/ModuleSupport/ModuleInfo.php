<?php

namespace Modules\System\Core\Libraries\ModuleSupport;

class ModuleInfo
{
    public string $category;
    public string $module;
    public string $path;
    public string $namespace;
    public string $infoClass;

    public ?object $info = null;

    public function __construct(array $data)
    {
        $this->category  = $data['category'];
        $this->module    = $data['module'];
        $this->path      = $data['path'];
        $this->namespace = $data['namespace'];
        $this->infoClass = $data['infoClass'];
    }
}
