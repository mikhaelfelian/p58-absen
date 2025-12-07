<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityPatrolTable extends Migration
{
    public function up()
    {
        // Check if table already exists
        if ($this->db->tableExists('activity_patrol')) {
            log_message('info', 'activity_patrol table already exists, skipping migration');
            return;
        }
        
        $this->forge->addField([
            'id_activity_patrol' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_activity' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'id_patrol' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'barcode_scanned' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'scan_time' => [
                'type' => 'DATETIME',
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        
        $this->forge->addKey('id_activity_patrol', true);
        $this->forge->addKey('id_activity');
        $this->forge->addKey('id_patrol');
        $this->forge->addKey('scan_time');
        
        // Create table first
        $this->forge->createTable('activity_patrol');
        
        // Add foreign keys using raw SQL for better control
        // Check if activity table exists and add foreign key
        $activityTableExists = $this->db->tableExists('activity');
        if ($activityTableExists) {
            // Check if foreign key already exists
            $fkExists = $this->db->query("SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'activity_patrol' 
                AND CONSTRAINT_NAME = 'fk_activity_patrol_activity'")->getResult();
            
            if (empty($fkExists)) {
                try {
                    $this->db->query('ALTER TABLE `activity_patrol` 
                        ADD CONSTRAINT `fk_activity_patrol_activity` 
                        FOREIGN KEY (`id_activity`) 
                        REFERENCES `activity` (`id_activity`) 
                        ON DELETE CASCADE 
                        ON UPDATE CASCADE');
                } catch (\Exception $e) {
                    log_message('warning', 'Failed to add foreign key fk_activity_patrol_activity: ' . $e->getMessage());
                }
            }
        }
        
        // Check if company_patrol table exists and add foreign key
        $patrolTableExists = $this->db->tableExists('company_patrol');
        if ($patrolTableExists) {
            // Check if foreign key already exists
            $fkExists = $this->db->query("SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'activity_patrol' 
                AND CONSTRAINT_NAME = 'fk_activity_patrol_patrol'")->getResult();
            
            if (empty($fkExists)) {
                try {
                    $this->db->query('ALTER TABLE `activity_patrol` 
                        ADD CONSTRAINT `fk_activity_patrol_patrol` 
                        FOREIGN KEY (`id_patrol`) 
                        REFERENCES `company_patrol` (`id_patrol`) 
                        ON DELETE CASCADE 
                        ON UPDATE CASCADE');
                } catch (\Exception $e) {
                    log_message('warning', 'Failed to add foreign key fk_activity_patrol_patrol: ' . $e->getMessage());
                }
            }
        }
    }

    public function down()
    {
        // Drop foreign keys first
        try {
            $this->db->query('ALTER TABLE `activity_patrol` DROP FOREIGN KEY `fk_activity_patrol_activity`');
        } catch (\Exception $e) {
            // Ignore if doesn't exist
        }
        
        try {
            $this->db->query('ALTER TABLE `activity_patrol` DROP FOREIGN KEY `fk_activity_patrol_patrol`');
        } catch (\Exception $e) {
            // Ignore if doesn't exist
        }
        
        $this->forge->dropTable('activity_patrol');
    }
}
