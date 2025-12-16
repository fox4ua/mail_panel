<?php

namespace Modules\System\Core\Libraries\ModuleSupport;

final class ManifestResolver
{
    public static function wrap(object $manifest): ModuleManifestInterface
    {
        if ($manifest instanceof ModuleManifestInterface) {
            return $manifest;
        }
        return new LegacyManifestAdapter($manifest);
    }
}
