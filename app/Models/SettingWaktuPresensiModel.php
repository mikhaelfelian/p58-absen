<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class SettingWaktuPresensiModel extends \App\Models\BaseModel
{

	public function __construct() {
		parent::__construct();
	}
	
	public function deleteData($id) 
	{
		$delete = $this->db->table('setting_waktu_presensi')->delete(['id_setting_waktu_presensi' => $id]);
		if ($delete) {
			$sql = 'SELECT * FROM setting_waktu_presensi WHERE gunakan = "Y"';
			$result = $this->db->query($sql)->getRowArray();
			if (!$result) {
				$sql = 'SELECT * FROM setting_waktu_presensi ORDER BY id_setting_waktu_presensi ASC LIMIT 1';
				$result = $this->db->query($sql)->getRowArray();
				$this->db->table('setting_waktu_presensi')->update(['gunakan' => 'Y'], ['id_setting_waktu_presensi' => $result['id_setting_waktu_presensi']]);
			}
		}
		
		return $delete;
	}
	
	public function getSettingWaktuPresensiById($id) {
		$sql = 'SELECT * FROM setting_waktu_presensi WHERE id_setting_waktu_presensi = ?';
		$result = $this->db->query($sql, $id)->getRowArray();
		return $result;
	}
	
	public function switchDefault($id) {

		$this->db->table('setting_waktu_presensi')->update(['gunakan' => 'N']);
		$update = $this->db->table('setting_waktu_presensi')->update(['gunakan' => 'Y'], ['id_setting_waktu_presensi' => $id]);
		return $update;
	}
	
	public function saveData() {
		$result = [];
		$batas_waktu_masuk = $_POST['batas_waktu_masuk_jam'] . ':' . $_POST['batas_waktu_masuk_menit'] . ':' . $_POST['batas_waktu_masuk_detik'];
		$batas_waktu_pulang = $_POST['batas_waktu_pulang_jam'] . ':' . $_POST['batas_waktu_pulang_menit'] . ':' . $_POST['batas_waktu_pulang_detik'];
		
		$waktu_masuk_awal = $_POST['waktu_masuk_awal_jam'] . ':' . $_POST['waktu_masuk_awal_menit'] . ':' . $_POST['waktu_masuk_awal_detik'];
		$waktu_masuk_akhir = $_POST['waktu_masuk_akhir_jam'] . ':' . $_POST['waktu_masuk_akhir_menit'] . ':' . $_POST['waktu_masuk_akhir_detik'];
		$waktu_pulang_awal = $_POST['waktu_pulang_awal_jam'] . ':' . $_POST['waktu_pulang_awal_menit'] . ':' . $_POST['waktu_pulang_awal_detik'];
		$waktu_pulang_akhir = $_POST['waktu_pulang_akhir_jam'] . ':' . $_POST['waktu_pulang_akhir_menit'] . ':' . $_POST['waktu_pulang_akhir_detik'];
		
		$data_db['nama_setting'] = $_POST['nama_setting'];
		$data_db['batas_waktu_masuk'] = $batas_waktu_masuk;
		$data_db['batas_waktu_pulang'] = $batas_waktu_pulang;
		$data_db['waktu_masuk_awal'] = $waktu_masuk_awal;
		$data_db['waktu_masuk_akhir'] = $waktu_masuk_akhir;
		$data_db['waktu_pulang_awal'] = $waktu_pulang_awal;
		$data_db['waktu_pulang_akhir'] = $waktu_pulang_akhir;
		$data_db['hari_pulang'] = 'sama_hari_masuk';
		
		$this->db->transStart();
			
		if ($_POST['id']) {
			$save = $this->db->table('setting_waktu_presensi')->update($data_db, ['id_setting_waktu_presensi' => $_POST['id']]);
			$id = $_POST['id'];
		} else {
			$save = $this->db->table('setting_waktu_presensi')->insert($data_db);
			$id = $this->db->insertID();
		}
		
		$this->db->transComplete();
		if ($this->db->transStatus()) {
			$result['status'] = 'ok';
			$result['message'] = 'Data berhasil disimpan';
			$result['id'] = $id;
		} else {
			$result['status'] = 'error';
			$result['message'] = 'Data gagal disimpan';
		}
		
		return $result;
	}

	public function countAllData() {
		$sql = 'SELECT COUNT(*) AS jml FROM setting_waktu_presensi';
		$result = $this->db->query($sql)->getRow();
		return $result->jml;
	}
	
	public function getListData() {

		$columns = $this->request->getPost('columns');

		// Search
		$search_all = @$this->request->getPost('search')['value'];
		
		$where = ' WHERE 1 = 1 ';
		if ($search_all) {
			foreach ($columns as $val) {
				
				if (strpos($val['data'], 'ignore_search') !== false) 
					continue;
				
				if (strpos($val['data'], 'ignore') !== false)
					continue;
				
				$where_col[] = $val['data'] . ' LIKE "%' . $search_all . '%"';
			}
			 $where .= ' AND (' . join(' OR ', $where_col) . ') ';
		}
		
		// Order		
		$order_data = $this->request->getPost('order');
		$order = '';
		if (strpos($_POST['columns'][$order_data[0]['column']]['data'], 'ignore_search') === false) {
			$order_by = $columns[$order_data[0]['column']]['data'] . ' ' . strtoupper($order_data[0]['dir']);
			$order = ' ORDER BY ' . $order_by;
		}

		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml_data FROM setting_waktu_presensi' . $where;
				
		$query = $this->db->query($sql)->getRowArray();
		$total_filtered = $query['jml_data'];
							
		
		// Query Data
		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		$sql = 'SELECT * FROM setting_waktu_presensi' . $where . $order  . ' LIMIT ' . $start . ', ' . $length;
		
		$data = $this->db->query($sql)->getResultArray();
				
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
}
?>