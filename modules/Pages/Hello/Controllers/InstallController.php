<?php

namespace Modules\Pages\Hello\Controllers;

use Modules\Pages\Hello\Models\InstallModel;

class InstallController
{
    public function install(): bool
    {
        return (new InstallModel())->addMenu();
    }

    public function uninstall(): bool
    {
        return (new InstallModel())->delMenu();
    }

    public function update(string $from, string $to): bool
    {
        return (new InstallModel())->addMenu();
    }
}
