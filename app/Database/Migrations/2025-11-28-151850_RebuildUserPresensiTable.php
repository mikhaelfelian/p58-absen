<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RebuildUserPresensiTable extends Migration
{
    public function up()
    {
        $this->forge->dropTable('user_presensi', true);

        $this->forge->addField([
            'id_user_presensi' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'id_company' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'tgl_masuk' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'tgl_keluar' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'durasi' => [
                'type'    => 'DOUBLE',
                'null'    => true,
                'default' => null,
            ],
            'is_valid' => [
                'type'    => 'TINYINT',
                'constraint' => 4,
                'null'    => false,
                'default' => 0,
            ],
            'latitude' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'collate'    => 'utf8mb4_general_ci',
            ],
            'longitude' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'collate'    => 'utf8mb4_general_ci',
            ],
            'foto' => [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'collate'    => 'utf8mb4_general_ci',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        // Add primary key
        $this->forge->addKey('id_user_presensi', true, true, 'BTREE');
        // Add secondary indexes
        $this->forge->addKey('id_user', false, false, 'idx_user');
        $this->forge->addKey('id_company', false, false, 'idx_company');

        // Add foreign keys with ON UPDATE CASCADE, ON DELETE CASCADE and custom constraint names
        $this->forge->addForeignKey(
            'id_user',
            'user',
            'id_user',
            'CASCADE',
            'CASCADE',
            'fk_user_presensi_user'
        );
        $this->forge->addForeignKey(
            'id_company',
            'company',
            'id_company',
            'CASCADE',
            'CASCADE',
            'fk_user_presensi_company'
        );

        // Table options (charset/collation/engine)
        $this->forge->createTable('user_presensi', false, [
            'ENGINE'  => 'InnoDB',
            'DEFAULT CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('user_presensi', true);
    }
}
