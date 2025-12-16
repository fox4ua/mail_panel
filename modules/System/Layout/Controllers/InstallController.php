<?php

namespace Modules\System\Layout\Controllers;

use Modules\System\Layout\Models\InstallModel;

class InstallController
{
    public function install(): bool
    {
        // 1) DB/structure setup (if any)
        return true;

        // 2) Menu in DB (if module provides system admin entry)
        return (new InstallModel())->addMenu();
    }

    public function uninstall(): bool
    {
        // 1) Remove menu entry (if any)
        if (!(new InstallModel())->delMenu()) {
            return false;
        }

        // 2) DB/structure teardown (if any)
        return true;
    }

    public function update(string $from, string $to): bool
    {
        // Keep menu in sync on update
        return (new InstallModel())->addMenu();
    }
}
