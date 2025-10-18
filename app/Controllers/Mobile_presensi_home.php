<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\MobilePresensiHomeModel;
use App\Models\UserCompanyModel;

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
		
		// Get active companies for this user
		$userCompanyModel = new UserCompanyModel;
		$id_user = $this->session->get('user')['id_user'];
		$companies = $userCompanyModel->getActiveCompanyByUser($id_user);
		
		// Debug: Check if query returns data
		if (empty($companies)) {
			// Try to get all assignments without date/status filters for debugging
			$sql_debug = 'SELECT user_company.*, company.nama_company, company.status as company_status
						FROM user_company
						LEFT JOIN company USING(id_company)
						WHERE id_user = ?';
			$debug_result = $this->model->db->query($sql_debug, [$id_user])->getResult();
			
			// Store debug info to show in view
			$this->data['debug_info'] = [
				'total_assignments' => count($debug_result),
				'active_companies' => count($companies),
				'assignments' => $debug_result,
				'today' => date('Y-m-d')
			];
			
			if (!empty($debug_result)) {
				foreach ($debug_result as $row) {
					log_message('debug', 'Company: ' . $row->nama_company . 
								', Status: ' . $row->status . 
								', Company Status: ' . ($row->company_status ?? 'NULL') .
								', Start: ' . ($row->tanggal_mulai ?? 'NULL') . 
								', End: ' . ($row->tanggal_selesai ?? 'NULL'));
				}
			}
		} else {
			$this->data['debug_info'] = null;
		}
		
		$this->data['companies'] = $companies;
	
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
		
		// Validate company assignment
		$userCompanyModel = new UserCompanyModel;
		$id_user = $this->session->get('user')['id_user'];
		$id_company = $data['id_company'] ?? null;
		
		if (!$id_company) {
			$error[] = 'Company harus dipilih';
		} else {
			// Check if user has access to this company
			$hasAccess = $userCompanyModel->checkUserCompanyAccess($id_user, $id_company);
			if (!$hasAccess) {
				$error[] = 'Anda tidak memiliki akses ke company ini';
			} else {
				// Get company location and radius
				$sql = 'SELECT * FROM company WHERE id_company = ?';
				$company = $this->model->db->query($sql, [$id_company])->getRow();
				
				if (!$company) {
					$error[] = 'Company tidak ditemukan';
				} else {
					// Check radius based on company location
					$dist = $this->getDistance(
						$company->latitude, 
						$company->longitude, 
						$data['location']['coords']['latitude'], 
						$data['location']['coords']['longitude']
					);
					
					$radius = $company->radius_nilai;
					if ($company->radius_satuan == 'km') {
						$radius = $radius * 1000;
					}
					$dist = $dist * 1000;
					
					if ($radius < $dist) {
						$error[] = 'Lokasi Anda diluar radius lokasi absen yang diperbolehkan. Radius lokasi absen adalah ' . $company->radius_nilai . $company->radius_satuan . ' dari ' . $company->nama_company . ' (' . $company->latitude . ', ' . $company->longitude . ')'; 
					}
				}
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
			$data['id_company'] = $id_company;
			
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
