<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class SettingPresensiModel extends \App\Models\BaseModel
{
	public function saveSetting() 
	{
		$result = [];
		
		$data_db[] = ['type' => 'presensi', 'param' => 'gunakan_foto_selfi', 'value' => $_POST['gunakan_foto_selfi']];
		$data_db[] = ['type' => 'presensi', 'param' => 'gunakan_radius_lokasi', 'value' => $_POST['gunakan_radius_lokasi']];
		$data_db[] = ['type' => 'presensi', 'param' => 'latitude', 'value' => $_POST['latitude']];
		$data_db[] = ['type' => 'presensi', 'param' => 'longitude', 'value' => $_POST['longitude']];
		$data_db[] = ['type' => 'presensi', 'param' => 'radius_satuan', 'value' => $_POST['radius_satuan']];
		$data_db[] = ['type' => 'presensi', 'param' => 'radius_nilai', 'value' => $_POST['radius_nilai']];
		$data_db[] = ['type' => 'presensi', 'param' => 'jml_riwayat_presensi_home', 'value' => $_POST['jml_riwayat_presensi_home']];
		$data_db[] = ['type' => 'presensi', 'param' => 'hari_kerja', 'value' => json_encode($_POST['hari_kerja'])];
		// $data_db[] = ['type' => 'presensi', 'param' => 'data_setelah_nama_pegawai', 'value' => json_encode($_POST['data_setelah_nama_pegawai'])];
	
		$this->db->transStart();
		$this->db->table('setting')->delete(['type' => 'presensi']);
		$this->db->table('setting')->insertBatch($data_db);
		$this->db->transComplete();
		
		if ($this->db->transStatus()) {
			$result['status'] = 'ok';
			$result['message'] = 'Data berhasil disimpan';
		} else {
			$result['status'] = 'error';
			$result['message'] = 'Data gagal disimpan';
		}
		
		return $result;
	}
}
?>