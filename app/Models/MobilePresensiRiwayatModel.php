<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class MobilePresensiRiwayatModel extends \App\Models\BaseModel
{
	public function __construct() {
		parent::__construct();
	}
	
	public function getRiwayatPresensi($start_date, $end_date) {
		$sql = 'SELECT *, MIN(IF(jenis_presensi = "masuk", waktu, null)) AS presensi_masuk,
						MAX(IF(jenis_presensi = "pulang", waktu, null)) AS presensi_pulang,
						MAX(IF(jenis_presensi = "masuk", batas_waktu_presensi, null)) AS batas_presensi_masuk,
						MIN(IF(jenis_presensi = "pulang", batas_waktu_presensi, null)) AS batas_presensi_pulang
				FROM user_presensi 
				WHERE tanggal >= "' . $start_date . '" AND tanggal <= "' . $end_date . '"
				GROUP BY tanggal';

		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
}
?>