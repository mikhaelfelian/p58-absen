<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserCompanyTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_user_company' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'id_company' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'tanggal_mulai' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'tanggal_selesai' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive', 'completed'],
                'default'    => 'active',
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'id_user_input' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tgl_input' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'id_user_update' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'tgl_update' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id_user_company', true);
        $this->forge->addKey('id_user');
        $this->forge->addKey('id_company');
        $this->forge->addKey('status');
        
        $this->forge->addForeignKey('id_user', 'user', 'id_user', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_company', 'company', 'id_company', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('user_company');
    }

    public function down()
    {
        $this->forge->dropTable('user_company');
    }
}

