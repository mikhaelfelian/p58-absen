<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyToUserPresensi extends Migration
{
    public function up()
    {
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
        
        // Add foreign key
        $this->db->query('ALTER TABLE user_presensi ADD CONSTRAINT fk_user_presensi_company FOREIGN KEY (id_company) REFERENCES company(id_company) ON DELETE SET NULL ON UPDATE CASCADE');
        
        // Add index
        $this->db->query('ALTER TABLE user_presensi ADD INDEX idx_company (id_company)');
    }

    public function down()
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE user_presensi DROP FOREIGN KEY fk_user_presensi_company');
        
        // Drop column
        $this->forge->dropColumn('user_presensi', 'id_company');
    }
}

