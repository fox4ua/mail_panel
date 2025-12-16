<?php

namespace Modules\System\Blocks\Libraries\Blocks;

use Config\Database;

class BlocksInstaller
{
    public function install(): bool
    {
        $db = Database::connect();
        $forge = Database::forge($db);

        if (! $db->tableExists('block_instances')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'type' => ['type' => 'VARCHAR', 'constraint' => 80],
                'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'settings_json' => ['type' => 'TEXT', 'null' => true],
                'is_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $forge->addKey('id', true);
            $forge->addKey('type');
            $forge->createTable('block_instances', true);
        }

        if (! $db->tableExists('block_placements')) {
            $forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'instance_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
                'area' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'admin'],
                'theme' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'default'],
                'region' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => 'sidebar'],
                'weight' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'conditions_json' => ['type' => 'TEXT', 'null' => true],
                'is_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            ]);
            $forge->addKey('id', true);
            $forge->addKey(['area','theme','region']);
            $forge->addKey('instance_id');
            $forge->createTable('block_placements', true);
        }

        return true;
    }

    public function uninstall(): bool
    {
        $db = Database::connect();
        $forge = Database::forge($db);

        if ($db->tableExists('block_placements')) $forge->dropTable('block_placements', true);
        if ($db->tableExists('block_instances')) $forge->dropTable('block_instances', true);

        return true;
    }
}
