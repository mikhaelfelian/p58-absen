<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJamKerjaTargetToUserCompany extends Migration
{
    public function up()
    {
        $this->forge->addColumn('user_company', [
            'jam_kerja_target' => [
                'type'    => 'INT',
                'default' => 12,
                'null'    => false,
                'after'   => 'tgl_update'
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
    }

    public function down()
    {
        $this->forge->dropColumn('user_company', [
            'jam_kerja_target',
            'created_at',
            'updated_at'
        ]);
    }
}
