<?php

declare(strict_types=1);

namespace Modules\System\Settings\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'group_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'default'    => 'general',
            ],
            'setting_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'setting_value' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'string',
            ],
            'autoload' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('setting_key', 'uniq_settings_key');
        $this->forge->createTable('settings', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('settings', true);
    }
}
