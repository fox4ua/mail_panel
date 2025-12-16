<?php

namespace Modules\System\Menu\Libraries\Menu;

use Config\Database;

class MenuInstaller
{
    public function install(): bool
    {
        $db = Database::connect();
        $forge = Database::forge($db);

        if ($db->tableExists('menu_items')) return true;

        $forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'menu_key' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'sidebar'],
            'area' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'admin'],
            'parent_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'item_key' => ['type' => 'VARCHAR', 'constraint' => 140],
            'label' => ['type' => 'VARCHAR', 'constraint' => 255],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'weight' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_system' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'module' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $forge->addKey('id', true);
        $forge->addKey(['area','menu_key']);
        $forge->addKey('parent_id');
        $forge->addKey('module');
        $forge->addKey('item_key', false, true); // unique

        return (bool)$forge->createTable('menu_items', true);
    }

    public function uninstall(): bool
    {
        $db = Database::connect();
        $forge = Database::forge($db);
        if (! $db->tableExists('menu_items')) return true;
        return (bool)$forge->dropTable('menu_items', true);
    }
}
