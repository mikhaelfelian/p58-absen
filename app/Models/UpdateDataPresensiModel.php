<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class UpdateDataPresensiModel extends \App\Models\BaseModel
{

	public function __construct() {
		parent::__construct();
	}
	
	public function getTanggalMulaiPresensi() {
		$sql = 'SELECT MIN(tanggal) as tanggal FROM user_presensi';
		$result = $this->db->query($sql)->getRowArray();
		$tanggal = $result['tanggal'];
		return $tanggal;
	}
	
	public function updateDataPresensi() {

		$result_setting = $this->getSetting('presensi');
		$setting = [];
		foreach ($result_setting as $val) {
			$setting[$val['param']] = $val['value'];
		}
		$hari_kerja = json_decode($setting['hari_kerja'], true);
		
		$sql = 'SELECT * FROM setting_waktu_presensi WHERE gunakan = "Y"';
		$setting_waktu = $this->db->query($sql)->getRowArray();
		$batas_presensi['masuk'] = $setting_waktu['batas_waktu_masuk'];
		$batas_presensi['masuk'] = $setting_waktu['batas_waktu_masuk'];
		
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		
		$sql = 'SELECT * FROM user_presensi WHERE tanggal >= "' . $start_date . '" AND tanggal <= "' . $end_date . '"';
		$result = $this->db->query($sql)->getResultArray();
		$data_presensi = [];
		foreach ($result as $val) {
			$data_presensi[$val['id_user']][$val['tanggal']][$val['jenis_presensi']] = $val['waktu'];
		}
		
		$jumlah_data = 0;
		$data_db = [];
		for ($i = $start_date; $i <= $end_date; $i = date('Y-m-d', strtotime('+1 day', strtotime($i))) ) 
		{
			$day = date('w', strtotime($i));
			if (in_array($day, $hari_kerja)) {
				foreach ($data_presensi as $id_user => $presensi_user) {
					
					if (key_exists($i, $presensi_user)) 
					{
						if (!key_exists('masuk', $presensi_user[$i])) {

							$data_db[] = [
											'id_user' => $id_user
											, 'tanggal' => $i
											, 'waktu' => null
											, 'jenis_presensi' => 'masuk'
											, 'batas_waktu_presensi' => $setting_waktu['batas_waktu_masuk']
											, 'keterangan' => 'sistem'
										];
							$jumlah_data++;
						}
						
						if (!key_exists('pulang', $presensi_user[$i])) {

							$data_db[] = [
											'id_user' => $id_user
											, 'tanggal' => $i
											, 'waktu' => null
											, 'jenis_presensi' => 'pulang'
											, 'batas_waktu_presensi' => $setting_waktu['batas_waktu_pulang']
											, 'keterangan' => 'sistem'
										];
							$jumlah_data++;
						}
						
					} else {

						$data_db[] = [
											'id_user' => $id_user
											, 'tanggal' => $i
											, 'waktu' => null
											, 'jenis_presensi' => 'masuk'
											, 'batas_waktu_presensi' => $setting_waktu['batas_waktu_masuk']
											, 'keterangan' => 'sistem'
										];
						
						$data_db[] = [
											'id_user' => $id_user
											, 'tanggal' => $i
											, 'waktu' => null
											, 'jenis_presensi' => 'pulang'
											, 'batas_waktu_presensi' => $setting_waktu['batas_waktu_pulang']
											, 'keterangan' => 'sistem'
										];
										
						$jumlah_data++;
						$jumlah_data++;
					}
				}
			}
		}
		
		if ($data_db) {
			$this->db->table('user_presensi')->insertBatch($data_db);
		}
		
		$this->db->table('setting')->update(['value' => date('Y-m-d')],['param' => 'last_update_data_presensi']);
		return $jumlah_data;

	}
}
?>