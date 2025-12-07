<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDurationColumnsToUserPresensi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('user_presensi', [
            'waktu_pulang' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'durasi' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'is_valid' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_presensi', [
            'waktu_pulang',
            'durasi',
            'is_valid'
        ]);
    }
}
