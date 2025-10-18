<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class MobilePresensiHomeModel extends \App\Models\BaseModel
{
	public function getPresensiByIdUserAndDate($id_user, $date) {
		$sql = 'SELECT * FROM user_presensi WHERE id_user = ? AND tanggal = ?';
		$result = $this->db->query($sql,[$id_user, $date])->getResultArray();
		return $result;
	}
	
	public function getRiwayatPresensi($start_date, $end_date) {
		$sql = 'SELECT *, MIN(IF(jenis_presensi = "masuk", waktu, null)) AS presensi_masuk,
						MAX(IF(jenis_presensi = "pulang", waktu, null)) AS presensi_pulang,
						MAX(IF(jenis_presensi = "masuk", batas_waktu_presensi, null)) AS batas_presensi_masuk,
						MIN(IF(jenis_presensi = "pulang", batas_waktu_presensi, null)) AS batas_presensi_pulang,
						MAX(IF(jenis_presensi = "masuk", id_company, null)) AS id_company
				FROM user_presensi 
				WHERE tanggal >= "' . $start_date . '" AND tanggal <= "' . $end_date . '" AND id_user = ' . service('session')->get('user')['id_user'] . '
				GROUP BY tanggal';
		// echo $sql; die;
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function saveDataPresensi($data) 
	{
		$data_db = ['id_user' => $data['id_user'],
					'tanggal' => date('Y-m-d'),
					'waktu' => date('H:i:s'),
					'latitude' => $data['location']['coords']['latitude'],
					'longitude' => $data['location']['coords']['longitude'],
					'jenis_presensi' => $data['jenis_presensi'],
					'raw_lokasi' => json_encode($data['location'])
				];
		
		// Add company ID if provided
		if (isset($data['id_company'])) {
			$data_db['id_company'] = $data['id_company'];
		}
		
		if ($data['foto']) {
			$nama_file = str_replace(' ', '_', $this->session->get('user')['nama']) . '_' . date('Ymd_His_') . gettimeofday()['usec'] . '.jpeg';
			$data_db['foto'] = $nama_file;
			$exp = explode(',', $data['foto']);
			file_put_contents(ROOTPATH . 'public/images/presensi/' . $nama_file, base64_decode($exp[1]));
		}
		
		$query_result = $this->db->table('user_presensi')->insert($data_db);
		$result = '';
		if ($query_result) {
			$id = $this->db->insertID();
			$sql = 'SELECT * FROM user_presensi WHERE id_user_presensi = ?';
			$result = $this->db->query($sql, $id)->getRowArray();
		}
		return $result;
	}
}
?>