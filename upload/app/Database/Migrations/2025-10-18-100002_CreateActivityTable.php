<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityTable extends Migration
{
    public function up()
    {
        // Check if table already exists
        if ($this->db->tableExists('activity')) {
            log_message('info', 'activity table already exists, skipping migration');
            return;
        }
        
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
        
        // Create table first
        $this->forge->createTable('activity');
        
        // Add foreign keys using raw SQL for better control
        // Check if user table exists and add foreign key
        if ($this->db->tableExists('user')) {
            try {
                $this->db->query('ALTER TABLE `activity` 
                    ADD CONSTRAINT `fk_activity_user` 
                    FOREIGN KEY (`id_user`) 
                    REFERENCES `user` (`id_user`) 
                    ON DELETE CASCADE 
                    ON UPDATE CASCADE');
            } catch (\Exception $e) {
                log_message('warning', 'Failed to add foreign key fk_activity_user: ' . $e->getMessage());
            }
        }
        
        // Check if company table exists and add foreign key
        if ($this->db->tableExists('company')) {
            try {
                $this->db->query('ALTER TABLE `activity` 
                    ADD CONSTRAINT `fk_activity_company` 
                    FOREIGN KEY (`id_company`) 
                    REFERENCES `company` (`id_company`) 
                    ON DELETE CASCADE 
                    ON UPDATE CASCADE');
            } catch (\Exception $e) {
                log_message('warning', 'Failed to add foreign key fk_activity_company: ' . $e->getMessage());
            }
        }
        
        // Check if user_presensi table exists and add foreign key
        if ($this->db->tableExists('user_presensi')) {
            try {
                $this->db->query('ALTER TABLE `activity` 
                    ADD CONSTRAINT `fk_activity_user_presensi` 
                    FOREIGN KEY (`id_user_presensi`) 
                    REFERENCES `user_presensi` (`id_user_presensi`) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE');
            } catch (\Exception $e) {
                log_message('warning', 'Failed to add foreign key fk_activity_user_presensi: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('activity');
    }
}

