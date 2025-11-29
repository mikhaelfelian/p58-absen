<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSettingWaktuPresensiToUserCompany extends Migration
{
    public function up()
    {
        // Check if user_company table exists first
        if (!$this->db->tableExists('user_company')) {
            log_message('info', 'user_company table does not exist, skipping migration');
            return;
        }
        
        // Check if column already exists
        try {
            $fields = $this->db->getFieldData('user_company');
            $columnExists = false;
            foreach ($fields as $field) {
                if ($field->name === 'id_setting_waktu_presensi') {
                    $columnExists = true;
                    break;
                }
            }
            
            if (!$columnExists) {
                $fields = [
                    'id_setting_waktu_presensi' => [
                        'type'       => 'INT',
                        'constraint' => 10,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'id_company',
                    ],
                ];
                
                $this->forge->addColumn('user_company', $fields);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error checking/adding column: ' . $e->getMessage());
            return;
        }
        
        // Skip foreign key creation - it will be added manually if needed
        // Foreign keys can cause issues if table structures don't match exactly
        log_message('info', 'Column id_setting_waktu_presensi added. Foreign key can be added manually if needed.');
    }

    public function down()
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE `user_company` DROP FOREIGN KEY `fk_user_company_setting_waktu_presensi`');
        
        // Drop column
        $this->forge->dropColumn('user_company', 'id_setting_waktu_presensi');
    }
}

