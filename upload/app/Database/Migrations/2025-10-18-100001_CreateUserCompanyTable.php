<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserCompanyTable extends Migration
{
    public function up()
    {
        // Check if table already exists
        if ($this->db->tableExists('user_company')) {
            log_message('info', 'user_company table already exists, skipping migration');
            return;
        }
        
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
        
        // Create table first
        $this->forge->createTable('user_company');
        
        // Add foreign keys using raw SQL for better control
        // Check if user table exists and add foreign key
        $userTableExists = $this->db->tableExists('user');
        if ($userTableExists) {
            // Check if foreign key already exists
            $fkExists = $this->db->query("SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'user_company' 
                AND CONSTRAINT_NAME = 'fk_user_company_user'")->getResult();
            
            if (empty($fkExists)) {
                try {
                    $this->db->query('ALTER TABLE `user_company` 
                        ADD CONSTRAINT `fk_user_company_user` 
                        FOREIGN KEY (`id_user`) 
                        REFERENCES `user` (`id_user`) 
                        ON DELETE CASCADE 
                        ON UPDATE CASCADE');
                } catch (\Exception $e) {
                    log_message('warning', 'Failed to add foreign key fk_user_company_user: ' . $e->getMessage());
                }
            }
        }
        
        // Check if company table exists and add foreign key
        $companyTableExists = $this->db->tableExists('company');
        if ($companyTableExists) {
            // Check if foreign key already exists
            $fkExists = $this->db->query("SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'user_company' 
                AND CONSTRAINT_NAME = 'fk_user_company_company'")->getResult();
            
            if (empty($fkExists)) {
                try {
                    $this->db->query('ALTER TABLE `user_company` 
                        ADD CONSTRAINT `fk_user_company_company` 
                        FOREIGN KEY (`id_company`) 
                        REFERENCES `company` (`id_company`) 
                        ON DELETE CASCADE 
                        ON UPDATE CASCADE');
                } catch (\Exception $e) {
                    log_message('warning', 'Failed to add foreign key fk_user_company_company: ' . $e->getMessage());
                }
            }
        }
    }

    public function down()
    {
        // Drop foreign keys first
        try {
            $this->db->query('ALTER TABLE `user_company` DROP FOREIGN KEY `fk_user_company_user`');
        } catch (\Exception $e) {
            // Ignore if doesn't exist
        }
        
        try {
            $this->db->query('ALTER TABLE `user_company` DROP FOREIGN KEY `fk_user_company_company`');
        } catch (\Exception $e) {
            // Ignore if doesn't exist
        }
        
        $this->forge->dropTable('user_company');
    }
}

