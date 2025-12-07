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
		$id_user = service('session')->get('user')['id_user'];
		$sql = 'SELECT 
					DATE(tgl_masuk) AS shift_date,
					TIME(tgl_masuk) AS presensi_masuk,
					TIME(tgl_keluar) AS presensi_pulang,
					durasi,
					is_valid,
					id_company
				FROM user_presensi 
				WHERE DATE(tgl_masuk) >= "' . $start_date . '" 
					AND DATE(tgl_masuk) <= "' . $end_date . '"
					AND id_user = ' . $id_user . '
				ORDER BY tgl_masuk DESC';

		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
}
?>