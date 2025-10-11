<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\MobilePresensiHomeModel;

class Mobile_presensi_home extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new MobilePresensiHomeModel;	
		$this->data['title'] = 'Presensi';
	}
	
	public function index() {
		
		$this->addStyle ( $this->config->baseURL . 'public/themes/modern/css/mobile-presensi-home.css');
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/mobile-presensi-home.js');
		
		$end_date = date('Y-m-d');
		$start_date = date('Y-m-d', strtotime('-' . $this->data['setting_presensi']['jml_riwayat_presensi_home'] . ' days', strtotime($end_date)));
		$query_result = $this->model->getRiwayatPresensi($start_date, $end_date);
		
		$riwayat_presensi = [];
		if ($query_result) {
			foreach ($query_result as $val) {
				$riwayat_presensi[$val['tanggal']]['masuk'] = $val;
				$riwayat_presensi[$val['tanggal']]['pulang'] = $val;
			}
		}
		
		$this->data['riwayat_presensi'] = $riwayat_presensi;
	
		echo view('themes/modern/mobile-presensi-home.php', $this->data);
	}
	
	public function getDistance($lat1, $long1, $lat2, $long2) 
	{
		$theta = $long1 - $long2; 
		$distance = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta))); 
		$distance = acos($distance); 
		$distance = rad2deg($distance); 
		$distance = $distance * 60 * 1.1515; 
		$distance = $distance * 1.609344;  
		return $distance; //Kilometer
	}
	
	public function ajaxSaveData() 
	{
		$data = base64_decode($_POST['data']);
		$data = json_decode($data, true);
		$setting = $this->data['setting_presensi'];
		$error = [];
		
		if ($setting['gunakan_radius_lokasi'] == 'Y') {
			$dist = $this->getDistance($setting['latitude'], $setting['longitude'], $data['location']['coords']['latitude'], $data['location']['coords']['longitude']);
			
			$radius = $setting['radius_nilai'];
			if ($setting['radius_satuan'] == 'km') {
				$radius = $radius * 1000;
			}
			$dist = $dist * 1000;
			if ($radius < $dist) {
				$error[] = 'Lokasi Anda diluar radius lokasi absen yang diperbolehkan. Radius lokasi absen adalah ' . $setting['radius_nilai'] . $setting['radius_satuan'] . ' dari kantor (' . $setting['latitude'] . ', ' . $setting['longitude']; 
			}
		}
		
		if ($setting['gunakan_foto_selfi'] == 'Y') {
			$image = explode('data:image/jpeg;base64,', $data['foto']);
			$size= getimagesizefromstring(base64_decode(trim($image[1])));
			if (!$size) {
				$error[] = 'Foto tidak valid';
			}
		}
		
		if ($data['jenis_presensi'] == 'masuk') {
			$waktu = date('H:i:s');
			if ($waktu < $setting['waktu_masuk_awal'] || $waktu > $setting['waktu_masuk_akhir']) {
				$error[] = 'Waktu presensi masuk mulai pukul ' . $setting['waktu_masuk_awal'] . ' hingga pukul ' . $setting['waktu_masuk_akhir'];
			}
		}
		
		if ($data['jenis_presensi'] == 'pulang') {
			$waktu = date('H:i:s');
			if ($waktu < $setting['waktu_pulang_awal'] || $waktu > $setting['waktu_pulang_akhir']) {
				$error[] = 'Waktu presensi pulang mulai pukul ' . $setting['waktu_pulang_awal'] . ' hingga pukul ' . $setting['waktu_pulang_akhir'];
			}
		}
		
		if ($error) {
			$result = ['status' => 'error', 'message' => $error];
		} else {
		
			$data['id_user'] = $this->session->get('user')['id_user'];
			
			$data_inserted = $this->model->saveDataPresensi($data);
			// $data_inserted = true;
			if ($data_inserted) {
				$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan', 'data' => $data_inserted];
			} else {
				$result = ['status' => 'error', 'message' => 'Data gagal disimpan'];
			}
		}
		echo json_encode($result);
	}
}
