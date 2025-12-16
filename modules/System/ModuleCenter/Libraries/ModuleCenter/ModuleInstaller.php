<?php

namespace Modules\System\ModuleCenter\Libraries\ModuleCenter;

use Config\Database;

class ModuleInstaller
{
    public function installSelf(): bool
    {
        $db = Database::connect();
        $forge = Database::forge($db);

        if ($db->tableExists('modules')) return true;

        $forge->addField([
            'name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'version' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'is_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'installed_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $forge->addKey('name', true);
        return (bool)$forge->createTable('modules', true);
    }

    public function uninstallSelf(): bool
    {
        $db = Database::connect();
        $forge = Database::forge($db);
        if (! $db->tableExists('modules')) return true;
        return (bool)$forge->dropTable('modules', true);
    }
}
