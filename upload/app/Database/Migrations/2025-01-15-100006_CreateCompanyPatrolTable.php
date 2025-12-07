<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompanyPatrolTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_patrol' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id_company' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'nama_patrol' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'latitude' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
            ],
            'longitude' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
            ],
            'radius_nilai' => [
                'type' => 'DECIMAL',
                'constraint' => '8,2',
                'default' => 1.00,
            ],
            'radius_satuan' => [
                'type' => 'ENUM',
                'constraint' => ['m', 'km'],
                'default' => 'km',
            ],
            'urutan' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 1,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default' => 'active',
            ],
            'keterangan' => [
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

        $this->forge->addKey('id_patrol', true);
        $this->forge->addKey('id_company');
        $this->forge->addForeignKey('id_company', 'company', 'id_company', 'CASCADE', 'CASCADE');
        $this->forge->createTable('company_patrol');
    }

    public function down()
    {
        $this->forge->dropTable('company_patrol');
    }
}
