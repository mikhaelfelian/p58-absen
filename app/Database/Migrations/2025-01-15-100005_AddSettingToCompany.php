<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSettingToCompany extends Migration
{
    public function up()
    {
        $this->forge->addColumn('company', [
            'setting' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'keterangan',
                'comment' => 'JSON setting data for presensi configuration'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('company', 'setting');
    }
}
