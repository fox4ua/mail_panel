<?php

namespace Modules\System\Blocks\Controllers;

use Modules\System\Blocks\Models\InstallModel;

class InstallController
{
    public function install(): bool
    {
        // 1) DB/structure setup (if any)
        if (!(new \Modules\System\Blocks\Libraries\Blocks\BlocksInstaller())->install()) { return false; }

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
        return (new \Modules\System\Blocks\Libraries\Blocks\BlocksInstaller())->uninstall();
    }

    public function update(string $from, string $to): bool
    {
        // Keep menu in sync on update
        return (new InstallModel())->addMenu();
    }
}
