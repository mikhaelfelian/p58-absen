<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyToUserPresensi extends Migration
{
    public function up()
    {
        // Check if user_presensi table exists
        if (!$this->db->tableExists('user_presensi')) {
            log_message('info', 'user_presensi table does not exist, skipping migration');
            return;
        }
        
        // Check if column already exists
        try {
            $fields = $this->db->getFieldData('user_presensi');
            $columnExists = false;
            foreach ($fields as $field) {
                if ($field->name === 'id_company') {
                    $columnExists = true;
                    break;
                }
            }
            
            if (!$columnExists) {
                $fields = [
                    'id_company' => [
                        'type'       => 'INT',
                        'constraint' => 10,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'id_user',
                    ],
                ];
                
                $this->forge->addColumn('user_presensi', $fields);
            } else {
                log_message('info', 'id_company column already exists in user_presensi table');
            }
        } catch (\Exception $e) {
            log_message('error', 'Error checking/adding column: ' . $e->getMessage());
        }
        
        // Add foreign key if it doesn't exist
        try {
            $fkExists = $this->db->query("SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'user_presensi' 
                AND CONSTRAINT_NAME = 'fk_user_presensi_company'")->getResult();
            
            if (empty($fkExists) && $this->db->tableExists('company')) {
                $this->db->query('ALTER TABLE user_presensi ADD CONSTRAINT fk_user_presensi_company FOREIGN KEY (id_company) REFERENCES company(id_company) ON DELETE SET NULL ON UPDATE CASCADE');
            }
        } catch (\Exception $e) {
            log_message('warning', 'Failed to add foreign key fk_user_presensi_company: ' . $e->getMessage());
        }
        
        // Add index if it doesn't exist
        try {
            $indexExists = $this->db->query("SHOW INDEX FROM user_presensi WHERE Key_name = 'idx_company'")->getResult();
            if (empty($indexExists)) {
                $this->db->query('ALTER TABLE user_presensi ADD INDEX idx_company (id_company)');
            }
        } catch (\Exception $e) {
            log_message('warning', 'Failed to add index idx_company: ' . $e->getMessage());
        }
    }

    public function down()
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE user_presensi DROP FOREIGN KEY fk_user_presensi_company');
        
        // Drop column
        $this->forge->dropColumn('user_presensi', 'id_company');
    }
}

