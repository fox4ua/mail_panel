<?php

declare(strict_types=1);

namespace Modules\System\Profile\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optional profile table migration.
 * If you already have user_profile table in your UserManagement module, you can ignore this.
 */
class CreateUserProfileTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('user_profile')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 160,
                'null'       => true,
            ],
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addUniqueKey('user_id', 'uniq_user_profile_user');

        $this->forge->createTable('user_profile', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('user_profile', true);
    }
}
