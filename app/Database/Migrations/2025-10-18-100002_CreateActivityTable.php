<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_activity' => [
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
            'id_user_presensi' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Reference to attendance record',
            ],
            'tanggal' => [
                'type' => 'DATE',
            ],
            'waktu' => [
                'type' => 'TIME',
            ],
            'judul_activity' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'deskripsi_activity' => [
                'type' => 'TEXT',
            ],
            'foto_activity' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'latitude' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'longitude' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'approved', 'rejected'],
                'default'    => 'pending',
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'rejection_reason' => [
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
        
        $this->forge->addKey('id_activity', true);
        $this->forge->addKey('id_user');
        $this->forge->addKey('id_company');
        $this->forge->addKey('tanggal');
        $this->forge->addKey('status');
        
        $this->forge->addForeignKey('id_user', 'user', 'id_user', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_company', 'company', 'id_company', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_user_presensi', 'user_presensi', 'id_user_presensi', 'SET NULL', 'CASCADE');
        
        $this->forge->createTable('activity');
    }

    public function down()
    {
        $this->forge->dropTable('activity');
    }
}

