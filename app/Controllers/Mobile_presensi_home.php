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
use App\Models\ActivityPatrolModel;
use App\Models\CompanyModel;
use App\Models\UserPresensiModel;

class Mobile_presensi_home extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new MobilePresensiHomeModel;	
		$this->presensiModel = new UserPresensiModel;
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
				$shiftDate = $val['shift_date'] ?? null;
				if (!$shiftDate) {
					continue;
				}
				$riwayat_presensi[$shiftDate]['masuk'] = [
					'presensi_masuk' => $val['presensi_masuk']
				];
				$riwayat_presensi[$shiftDate]['pulang'] = [
					'presensi_pulang' => $val['presensi_pulang'] ?? null,
					'batas_presensi_pulang' => null
				];
				$riwayat_presensi[$shiftDate]['id_company'] = $val['id_company'] ?? null;
				$riwayat_presensi[$shiftDate]['durasi'] = $val['durasi'] ?? null;
				$riwayat_presensi[$shiftDate]['is_valid'] = $val['is_valid'] ?? 0;
			}
		}
		
		$this->data['riwayat_presensi'] = $riwayat_presensi;
		
		// Get active companies for this user
		$userCompanyModel = new UserCompanyModel;
		$id_user = $this->session->get('user')['id_user'];
		$companies = $userCompanyModel->getActiveCompanyByUser($id_user);
		
		// Load company settings for each company and check patrol status
		$companyModel = new \App\Models\CompanyModel;
		$activityPatrolModel = new ActivityPatrolModel;
		$patrol_status = [];
		
		foreach ($companies as $company) {
			$company->setting_data = $companyModel->getCompanySetting($company->id_company);
			
			// Check if patrol is required for this company
			$is_patrol_mode = $company->setting_data['is_patrol_mode'] ?? 'N';
			$isPatrolRequired = ($is_patrol_mode == 'Y' && isset($company->isPatrolRequired) && $company->isPatrolRequired == 1);
			
			if ($isPatrolRequired) {
				// Check if all patrols are completed
				$allCompleted = $activityPatrolModel->areAllPatrolsCompleted($id_user, $company->id_company);
				$uncompletedPatrols = [];
				
				if (!$allCompleted) {
					$uncompletedPatrols = $activityPatrolModel->getUncompletedPatrols($id_user, $company->id_company);
				}
				
				$patrol_status[$company->id_company] = [
					'is_required' => true,
					'all_completed' => $allCompleted,
					'uncompleted' => $uncompletedPatrols
				];
			} else {
				$patrol_status[$company->id_company] = [
					'is_required' => false,
					'all_completed' => true,
					'uncompleted' => []
				];
			}
		}
		
		$this->data['patrol_status'] = $patrol_status;
		
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
		
		// Get latest presensi record for current status (no date filtering)
		$last_presensi = $this->presensiModel->getLastPresensi($id_user);
		$this->data['last_presensi'] = $last_presensi;
	
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
		$today = date('Y-m-d');
		$error = [];
		
		// Debug: Log received data
		log_message('debug', 'Presensi Data: ' . json_encode($data));
		
		// Validate company assignment
		$userCompanyModel = new UserCompanyModel;
		$id_user = $this->session->get('user')['id_user'];
		
		// Try multiple ways to get id_company
		$id_company = $data['id_company'] ?? $_POST['id_company'] ?? null;
		
		// Debug log
		log_message('debug', 'ID Company from data: ' . ($data['id_company'] ?? 'NULL'));
		log_message('debug', 'ID Company from POST: ' . ($_POST['id_company'] ?? 'NULL'));
		log_message('debug', 'ID Company final: ' . ($id_company ?? 'NULL'));
		
		$assignment = null;
		if (!$id_company || $id_company == '' || $id_company == 'null') {
			$error[] = 'Company harus dipilih. Pastikan Anda berada di lokasi company yang di-assign.';
		} else {
			$sqlAssignment = 'SELECT uc.*, 
									uc.jam_kerja_target,
									swp.waktu_masuk_awal, swp.waktu_masuk_akhir,
									swp.waktu_pulang_awal, swp.waktu_pulang_akhir,
									swp.batas_waktu_masuk, swp.batas_waktu_pulang
								FROM user_company uc
								LEFT JOIN setting_waktu_presensi swp 
									ON uc.id_setting_waktu_presensi = swp.id_setting_waktu_presensi
								WHERE uc.id_user = ? 
									AND uc.id_company = ? 
									AND uc.status = "active"
									AND (uc.tanggal_mulai IS NULL OR uc.tanggal_mulai <= ?)
									AND (uc.tanggal_selesai IS NULL OR uc.tanggal_selesai >= ?)
								LIMIT 1';
			$assignment = $this->model->db->query($sqlAssignment, [$id_user, $id_company, $today, $today])->getRow();
			
			if (!$assignment) {
				$error[] = 'Anda tidak memiliki akses ke company ini';
			} else {
				// Get company location and radius
				$sql = 'SELECT * FROM company WHERE id_company = ?';
				$company = $this->model->db->query($sql, [$id_company])->getRow();
				
				if (!$company) {
					$error[] = 'Company tidak ditemukan';
				} else {
					// Load company settings
					$companyModel = new \App\Models\CompanyModel;
					$companySetting = $companyModel->getCompanySetting($id_company);
					$gunakanRadiusLokasi = $companySetting['gunakan_radius_lokasi'] ?? 'Y';
					
					// Only check radius if enabled in company settings
					if ($gunakanRadiusLokasi === 'Y') {
						// Check radius based on company location
						$dist = $this->getDistance(
							$company->latitude, 
							$company->longitude, 
							$data['location']['coords']['latitude'], 
							$data['location']['coords']['longitude']
						);
						
						// Use radius from company settings if available, otherwise use company table
						$radius = isset($companySetting['radius_nilai']) ? $companySetting['radius_nilai'] : $company->radius_nilai;
						$radiusSatuan = isset($companySetting['radius_satuan']) ? $companySetting['radius_satuan'] : $company->radius_satuan;
						
						if ($radiusSatuan == 'km') {
							$radius = $radius * 1000;
						}
						$dist = $dist * 1000;
						
						if ($radius < $dist) {
							$error[] = 'Lokasi Anda diluar radius lokasi absen yang diperbolehkan. Radius lokasi absen adalah ' . $radius . ($radiusSatuan == 'km' ? 'km' : 'm') . ' dari ' . $company->nama_company; 
						}
					}
					// If gunakan_radius_lokasi = 'N', skip radius validation
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
		
		// Check if patrol is required for pulang
		if ($data['jenis_presensi'] == 'pulang' && $id_company) {
			// Get user-company assignment to check isPatrolRequired
			// Check company patrol mode setting
			$companyModel = new CompanyModel;
			$companySetting = $companyModel->getCompanySetting($id_company);
			$is_patrol_mode = $companySetting['is_patrol_mode'] ?? 'N';
			
			// Combined: company patrol mode AND user's patrol requirement
			$isPatrolRequired = ($is_patrol_mode == 'Y' && $assignment && isset($assignment->isPatrolRequired) && $assignment->isPatrolRequired == 1);
			
			if ($isPatrolRequired) {
				// Check if all patrols are completed
				$activityPatrolModel = new ActivityPatrolModel;
				$allCompleted = $activityPatrolModel->areAllPatrolsCompleted($id_user, $id_company);
				
				if (!$allCompleted) {
					// Get list of uncompleted patrols
					$uncompletedPatrols = $activityPatrolModel->getUncompletedPatrols($id_user, $id_company);
					$patrolNames = array_map(function($p) { return $p->nama_patrol; }, $uncompletedPatrols);
					
					$error[] = 'Anda belum menyelesaikan semua patrol yang wajib. Patrol yang belum di-scan: ' . implode(', ', $patrolNames) . '. Silakan selesaikan semua patrol terlebih dahulu sebelum melakukan absen pulang.';
				}
			}
		}
		
		if ($error) {
			$result = ['status' => 'error', 'message' => $error];
		} else {
			// Use new duration-based methods
			if ($data['jenis_presensi'] == 'masuk') {
				// Validate: Check if user already has an active shift (tgl_keluar IS NULL)
				$last = $this->presensiModel->getLastPresensi($id_user);
				if ($last && empty($last['tgl_keluar'])) {
					$result = ['status' => 'error', 'message' => 'Anda sudah melakukan presensi masuk. Silakan lakukan presensi pulang terlebih dahulu.'];
					echo json_encode($result);
					return;
				}
				
				// Clock-in: Create new record
				$insertResult = $this->presensiModel->insertMasuk($id_user, $id_company);
				
				if ($insertResult) {
					// Save photo and location if provided
					if (isset($data['foto']) && $data['foto']) {
						$presensiRecord = $this->presensiModel->find($insertResult);
						if ($presensiRecord) {
							$nama_file = str_replace(' ', '_', $this->session->get('user')['nama']) . '_' . date('Ymd_His_') . gettimeofday()['usec'] . '.jpeg';
							$exp = explode(',', $data['foto']);
							file_put_contents(ROOTPATH . 'public/images/presensi/' . $nama_file, base64_decode($exp[1]));
							
							$this->presensiModel->update($insertResult, [
								'foto' => $nama_file,
								'latitude' => $data['location']['coords']['latitude'],
								'longitude' => $data['location']['coords']['longitude']
							]);
						}
					}
					$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
				} else {
					$result = ['status' => 'error', 'message' => 'Data gagal disimpan'];
				}
			} else if ($data['jenis_presensi'] == 'pulang') {
				// Clock-out: Update latest clock-in record
				$jamKerjaTarget = 12; // Default
				if ($assignment) {
					if (is_object($assignment)) {
						$jamKerjaTarget = !empty($assignment->jam_kerja_target) ? intval($assignment->jam_kerja_target) : 12;
					} else if (is_array($assignment)) {
						$jamKerjaTarget = !empty($assignment['jam_kerja_target']) ? intval($assignment['jam_kerja_target']) : 12;
					}
				}
				$updateResult = $this->presensiModel->insertPulang($id_user, $jamKerjaTarget);
				
				if ($updateResult) {
					// Save photo and location if provided
					$latestMasuk = $this->presensiModel
						->where('id_user', $id_user)
						->where('tgl_keluar IS NOT NULL')
						->orderBy('id', 'DESC')
						->first();
					
					// Convert to array if object
					if ($latestMasuk && is_object($latestMasuk)) {
						$latestMasuk = (array) $latestMasuk;
					}
					
					if ($latestMasuk && isset($data['foto']) && $data['foto']) {
						$nama_file = str_replace(' ', '_', $this->session->get('user')['nama']) . '_' . date('Ymd_His_') . gettimeofday()['usec'] . '.jpeg';
						$exp = explode(',', $data['foto']);
						file_put_contents(ROOTPATH . 'public/images/presensi/' . $nama_file, base64_decode($exp[1]));
						
						$latestMasukId = is_array($latestMasuk) ? $latestMasuk['id'] : $latestMasuk->id;
						$this->presensiModel->update($latestMasukId, [
							'foto' => $nama_file,
							'latitude' => $data['location']['coords']['latitude'],
							'longitude' => $data['location']['coords']['longitude']
						]);
					}
					$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
				} else {
					$result = ['status' => 'error', 'message' => 'Anda belum melakukan presensi masuk. Silakan lakukan presensi masuk terlebih dahulu.'];
				}
			} else {
				$result = ['status' => 'error', 'message' => 'Jenis presensi tidak valid'];
			}
		}
		echo json_encode($result);
	}
}
