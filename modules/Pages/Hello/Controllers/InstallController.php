<?php

namespace Modules\Pages\Hello\Controllers;

use Modules\Pages\Hello\Models\InstallModel;

class InstallController
{
    private InstallModel $installModel;

    public function __construct(?InstallModel $installModel = null)
    {
        $this->installModel = $installModel ?? new InstallModel();
    }

    public function install(): bool
    {
        return $this->installModel->addMenu();
    }

    public function uninstall(): bool
    {
        return $this->installModel->delMenu();
    }

    public function update(string $from, string $to): bool
    {
        return $this->installModel->addMenu();
    }
}
