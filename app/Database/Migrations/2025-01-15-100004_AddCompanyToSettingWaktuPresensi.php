<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyToSettingWaktuPresensi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('setting_waktu_presensi', [
            'id_company' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'after' => 'nama_setting'
            ]
        ]);

        // Add foreign key constraint
        $this->forge->addForeignKey('id_company', 'company', 'id_company', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        // Drop foreign key first
        $this->forge->dropForeignKey('setting_waktu_presensi', 'setting_waktu_presensi_id_company_foreign');
        
        // Drop the column
        $this->forge->dropColumn('setting_waktu_presensi', 'id_company');
    }
}
