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
		$this->activityPatrolModel = new ActivityPatrolModel;
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
		
		// Get last 5 attendance records for display
		$last5Riwayat = $this->model->getLast5RiwayatPresensi($id_user);
		$this->data['last5_riwayat'] = $last5Riwayat;
		
		$companies = $userCompanyModel->getActiveCompanyByUser($id_user);
		
		// Load company settings for each company and check patrol status
		$companyModel = new \App\Models\CompanyModel;
		$activityPatrolModel = new ActivityPatrolModel;
		$patrol_status = [];
		
		// Get current shift's tgl_masuk if user has active shift
		$lastPresensi = $this->presensiModel->getLastPresensi($id_user);
		$tgl_masuk = null;
		if ($lastPresensi && !empty($lastPresensi['tgl_masuk']) && empty($lastPresensi['tgl_keluar'])) {
			// User has active shift, use its tgl_masuk for patrol validation
			$tgl_masuk = $lastPresensi['tgl_masuk'];
		}
		
		foreach ($companies as $company) {
			$company->setting_data = $companyModel->getCompanySetting($company->id_company);
			
			// Check if patrol is required for this company
			$is_patrol_mode = $company->setting_data['is_patrol_mode'] ?? 'N';
			$isPatrolRequired = ($is_patrol_mode == 'Y' && isset($company->isPatrolRequired) && $company->isPatrolRequired == 1);
			
			if ($isPatrolRequired) {
				// Check if all patrols are completed (within current shift if active)
				$allCompleted = $activityPatrolModel->areAllPatrolsCompleted($id_user, $company->id_company, $tgl_masuk);
				$uncompletedPatrols = [];
				
				if (!$allCompleted) {
					$uncompletedPatrols = $activityPatrolModel->getUncompletedPatrols($id_user, $company->id_company, $tgl_masuk);
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
		
		// Get active shift patrol data if user has active shift
		$active_shift_patrol = null;
		if ($lastPresensi && !empty($lastPresensi['tgl_masuk']) && empty($lastPresensi['tgl_keluar'])) {
			// User has active shift
			$id_user_presensi = $lastPresensi['id'] ?? null;
			$active_company_id = $lastPresensi['id_company'] ?? null;
			$tgl_masuk = $lastPresensi['tgl_masuk'] ?? null;
			
			if ($id_user_presensi && $active_company_id && $tgl_masuk) {
				// Get patrol progress for this active shift
				// Pass tgl_masuk and id_user to count patrols from activities with NULL id_user_presensi created after shift start
				$patrolProgress = $activityPatrolModel->getPatrolProgressByPresensi($id_user_presensi, $active_company_id, $tgl_masuk, $id_user);
				
				// Get last patrol data for this active shift
				$lastPatrolData = $activityPatrolModel->getLastPatrolDataByPresensi($id_user_presensi, $tgl_masuk, $id_user, $active_company_id);
				
				// Get next patrol (first uncompleted)
				$nextPatrolData = $activityPatrolModel->getNextPatrolByPresensi($id_user_presensi, $active_company_id, $tgl_masuk, $id_user);
				
				// Check if patrol is required for this company
				$isPatrolRequired = false;
				if (isset($patrol_status[$active_company_id])) {
					$isPatrolRequired = $patrol_status[$active_company_id]['is_required'] ?? false;
				}
				
				$active_shift_patrol = [
					'id_user_presensi' => $id_user_presensi,
					'id_company' => $active_company_id,
					'is_required' => $isPatrolRequired,
					'progress' => $patrolProgress,
					'last_patrol' => $lastPatrolData,
					'next_patrol' => $nextPatrolData,
					'is_complete' => $patrolProgress['percentage'] >= 100
				];
			}
		}
		
		$this->data['active_shift_patrol'] = $active_shift_patrol;
		
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
		try {
			// Decode and validate input data
			if (!isset($_POST['data']) || empty($_POST['data'])) {
				throw new \Exception('Data presensi tidak ditemukan');
			}
			
			$data = base64_decode($_POST['data']);
			$data = json_decode($data, true);
			
			if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
				throw new \Exception('Data presensi tidak valid: ' . json_last_error_msg());
			}
			
			// Validate required fields
			if (!isset($data['jenis_presensi']) || !in_array($data['jenis_presensi'], ['masuk', 'pulang'])) {
				throw new \Exception('Jenis presensi tidak valid');
			}
			
			$setting = $this->data['setting_presensi'] ?? [];
			$today = date('Y-m-d');
			$error = [];
			
			// Debug: Log received data
			log_message('debug', 'Presensi Data: ' . json_encode($data));
			
			// Validate user session
			$userSession = $this->session->get('user');
			if (!$userSession || !isset($userSession['id_user'])) {
				throw new \Exception('Session user tidak valid');
			}
			
			$id_user = $userSession['id_user'];
			
			// Validate user ID
			if (empty($id_user) || !is_numeric($id_user)) {
				throw new \Exception('ID user tidak valid: ' . $id_user);
			}
			
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
				// Validate company ID before query
				if (!is_numeric($id_company)) {
					$error[] = 'ID company tidak valid';
				} else {
					$userCompanyModel = new UserCompanyModel;
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
								// Validate location data exists before checking radius
								if (!isset($data['location']['coords']['latitude']) || !isset($data['location']['coords']['longitude'])) {
									$error[] = 'Data lokasi tidak ditemukan';
								} else {
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
							}
							// If gunakan_radius_lokasi = 'N', skip radius validation
						}
					}
				}
			}
			
			// Validate photo format if provided (photo is optional)
			if (isset($data['foto']) && !empty($data['foto'])) {
				$image = explode('data:image/jpeg;base64,', $data['foto']);
				if (!isset($image[1]) || empty($image[1])) {
					$error[] = 'Format foto tidak valid';
				} else {
					$size = getimagesizefromstring(base64_decode(trim($image[1])));
					if (!$size) {
						$error[] = 'Foto tidak valid';
					}
				}
			}
			
			// Check if patrol is required for pulang
			if ($data['jenis_presensi'] == 'pulang' && $id_company) {
				// Get current shift's tgl_masuk to check patrols done AFTER shift start
				$lastPresensi = $this->presensiModel->getLastPresensi($id_user);
				$tgl_masuk = null;
				if ($lastPresensi && !empty($lastPresensi['tgl_masuk']) && empty($lastPresensi['tgl_keluar'])) {
					// User has active shift, use its tgl_masuk
					$tgl_masuk = $lastPresensi['tgl_masuk'];
				}
				
				// Get user-company assignment to check isPatrolRequired
				// Check company patrol mode setting
				if ($assignment) {
					$companyModel = new CompanyModel;
					$companySetting = $companyModel->getCompanySetting($id_company);
					$is_patrol_mode = $companySetting['is_patrol_mode'] ?? 'N';
					
					// Combined: company patrol mode AND user's patrol requirement
					$isPatrolRequired = ($is_patrol_mode == 'Y' && isset($assignment->isPatrolRequired) && $assignment->isPatrolRequired == 1);
					
					if ($isPatrolRequired) {
						// Get patrol progress directly using getPatrolProgressByPresensi
						$activityPatrolModel = new ActivityPatrolModel;
						$id_user_presensi = null;
						if ($lastPresensi && isset($lastPresensi['id'])) {
							$id_user_presensi = $lastPresensi['id'];
						}
						
						if ($id_user_presensi && $tgl_masuk) {
							// Get patrol progress for this active shift
							// Pass tgl_masuk and id_user to count patrols from activities with NULL id_user_presensi created after shift start
							$patrolProgress = $activityPatrolModel->getPatrolProgressByPresensi($id_user_presensi, $id_company, $tgl_masuk, $id_user);
							$completed = $patrolProgress['completed'] ?? 0;
							$total = $patrolProgress['total'] ?? 0;
							$percentage = $patrolProgress['percentage'] ?? 0;
							
							// Check if completed < required
							if ($completed < $total) {
								$remaining = $total - $completed;
								// Format: "Patrol has been performed (X%). You still need <required - completed> more patrol(s)."
								$error[] = 'Patrol has been performed (' . $percentage . '%). You still need ' . $remaining . ' more patrol' . ($remaining != 1 ? 's' : '') . '.';
							}
							// If completed == required (percentage == 100), continue with check-out (no error)
						} else {
							// Fallback if id_user_presensi or tgl_masuk not available
							$error[] = 'Patrol has been performed (0%). You still need more patrols.';
						}
					}
				}
			}
			
			if ($error) {
				$result = ['status' => 'error', 'message' => $error];
			} else {
				// Use new duration-based methods
				if ($data['jenis_presensi'] == 'masuk') {
					// Use transaction with row lock to prevent race conditions
					$db = \Config\Database::connect();
					$db->transStart();
					
					try {
						// Validate: Check if user already has an active shift (tgl_keluar IS NULL)
						// Use row lock to prevent concurrent requests from both passing the check
						$last = $this->presensiModel->getLastPresensiWithLock($id_user);
						if ($last && empty($last['tgl_keluar'])) {
							$db->transRollback();
							$result = ['status' => 'error', 'message' => 'Anda sudah melakukan presensi masuk. Silakan lakukan presensi pulang terlebih dahulu.'];
							echo json_encode($result);
							return;
						}
						
						// Clock-in: Create new record
						$insertResult = $this->presensiModel->insertMasuk($id_user, $id_company);
						
						if (!$insertResult || !is_numeric($insertResult)) {
							$db->transRollback();
							log_message('error', 'Presensi masuk: Insert gagal. User ID: ' . $id_user . ', Company ID: ' . $id_company);
							$result = ['status' => 'error', 'message' => 'Data gagal disimpan. Silakan coba lagi.'];
							echo json_encode($result);
							return;
						}
						
						// Double-check: Verify record was created before updating
						$presensiRecord = $this->presensiModel->find($insertResult);
						if (!$presensiRecord) {
							$db->transRollback();
							log_message('error', 'Presensi masuk: Record tidak ditemukan setelah insert. Insert ID: ' . $insertResult);
							$result = ['status' => 'error', 'message' => 'Data gagal disimpan. Record tidak ditemukan.'];
							echo json_encode($result);
							return;
						}
						
						// Save photo and location if provided
						if (isset($data['foto']) && $data['foto'] && isset($data['location']['coords'])) {
							try {
								$nama_file = str_replace(' ', '_', $userSession['nama']) . '_' . date('Ymd_His_') . gettimeofday()['usec'] . '.jpeg';
								$exp = explode(',', $data['foto']);
								
								if (isset($exp[1]) && !empty($exp[1])) {
									$imageData = base64_decode($exp[1]);
									$uploadPath = ROOTPATH . 'public/images/presensi/';
									
									// Ensure directory exists
									if (!is_dir($uploadPath)) {
										mkdir($uploadPath, 0755, true);
									}
									
									$fileSaved = file_put_contents($uploadPath . $nama_file, $imageData);
									
									if ($fileSaved === false) {
										log_message('error', 'Presensi masuk: Gagal menyimpan foto. Path: ' . $uploadPath . $nama_file);
									} else {
										// Double-check record still exists before update
										$presensiRecordCheck = $this->presensiModel->find($insertResult);
										if ($presensiRecordCheck) {
											$updateData = [
												'foto' => $nama_file,
												'latitude' => $data['location']['coords']['latitude'],
												'longitude' => $data['location']['coords']['longitude']
											];
											
											$updateResult = $this->presensiModel->update($insertResult, $updateData);
											if (!$updateResult) {
												log_message('error', 'Presensi masuk: Gagal update foto/lokasi. Record ID: ' . $insertResult);
											}
										} else {
											log_message('error', 'Presensi masuk: Record tidak ditemukan saat update foto. Record ID: ' . $insertResult);
										}
									}
								}
							} catch (\Exception $e) {
								log_message('error', 'Presensi masuk: Error saat menyimpan foto/lokasi: ' . $e->getMessage());
								// Don't fail the entire operation if photo save fails
							}
						}
						
						$db->transComplete();
						
						if ($db->transStatus() === false) {
							log_message('error', 'Presensi masuk: Transaction gagal. User ID: ' . $id_user);
							$result = ['status' => 'error', 'message' => 'Data gagal disimpan. Silakan coba lagi.'];
						} else {
							$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
						}
					} catch (\Exception $e) {
						$db->transRollback();
						log_message('error', 'Presensi masuk error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
						$result = ['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan data presensi masuk'];
					}
				} else if ($data['jenis_presensi'] == 'pulang') {
					// Use transaction for pulang as well
					$db = \Config\Database::connect();
					$db->transStart();
					
					try {
						// Validate: Check if user has an active shift (must have masuk first)
						$last = $this->presensiModel->getLastPresensi($id_user);
						
						if (!$last) {
							$db->transRollback();
							log_message('error', 'Presensi pulang: Tidak ada record presensi untuk user ID: ' . $id_user);
							$result = ['status' => 'error', 'message' => 'Anda belum melakukan presensi masuk. Silakan lakukan presensi masuk terlebih dahulu.'];
							echo json_encode($result);
							return;
						}
						
					// Convert to array if object for consistent access
					if (is_object($last)) {
						$last = (array) $last;
					}
					
					// Check if tgl_keluar has a value (shift already completed) - block check-out
					// If tgl_keluar is NULL/empty, active shift exists - allow check-out
					if (!empty($last['tgl_keluar'])) {
						$db->transRollback();
						log_message('error', 'Presensi pulang: User sudah melakukan pulang atau tidak ada shift aktif. User ID: ' . $id_user);
						$result = ['status' => 'error', 'message' => 'Anda belum melakukan presensi masuk. Silakan lakukan presensi masuk terlebih dahulu.'];
						echo json_encode($result);
						return;
					}
						
						// Validate assignment exists before accessing properties
						if (!$assignment) {
							$db->transRollback();
							log_message('error', 'Presensi pulang: Assignment tidak ditemukan. User ID: ' . $id_user . ', Company ID: ' . $id_company);
							$result = ['status' => 'error', 'message' => 'Assignment tidak ditemukan'];
							echo json_encode($result);
							return;
						}
						
						// Clock-out: Update latest clock-in record
						$jamKerjaTarget = 12; // Default
						if (is_object($assignment)) {
							$jamKerjaTarget = !empty($assignment->jam_kerja_target) ? intval($assignment->jam_kerja_target) : 12;
						} else if (is_array($assignment)) {
							$jamKerjaTarget = !empty($assignment['jam_kerja_target']) ? intval($assignment['jam_kerja_target']) : 12;
						}
						
						$updateResult = $this->presensiModel->insertPulang($id_user, $jamKerjaTarget);
						
						if (!$updateResult) {
							$db->transRollback();
							log_message('error', 'Presensi pulang: Update gagal. User ID: ' . $id_user);
							$result = ['status' => 'error', 'message' => 'Gagal menyimpan data presensi pulang. Silakan coba lagi.'];
							echo json_encode($result);
							return;
						}
						
						// Double-check: Verify record was updated before proceeding
						$latestMasuk = $this->presensiModel
							->where('id_user', $id_user)
							->where('tgl_keluar IS NOT NULL')
							->orderBy('id', 'DESC')
							->first();
						
						if (!$latestMasuk) {
							$db->transRollback();
							log_message('error', 'Presensi pulang: Record tidak ditemukan setelah update. User ID: ' . $id_user);
							$result = ['status' => 'error', 'message' => 'Data gagal disimpan. Record tidak ditemukan.'];
							echo json_encode($result);
							return;
						}
						
						// Convert to array if object
						if (is_object($latestMasuk)) {
							$latestMasuk = (array) $latestMasuk;
						}
						
						// Validate latestMasuk has ID
						if (!isset($latestMasuk['id']) || empty($latestMasuk['id'])) {
							$db->transRollback();
							log_message('error', 'Presensi pulang: Record ID tidak valid. User ID: ' . $id_user);
							$result = ['status' => 'error', 'message' => 'Data gagal disimpan. ID record tidak valid.'];
							echo json_encode($result);
							return;
						}
						
						$latestMasukId = $latestMasuk['id'];
						
						// Save photo and location if provided
						if (isset($data['foto']) && $data['foto'] && isset($data['location']['coords'])) {
							try {
								$nama_file = str_replace(' ', '_', $userSession['nama']) . '_' . date('Ymd_His_') . gettimeofday()['usec'] . '.jpeg';
								$exp = explode(',', $data['foto']);
								
								if (isset($exp[1]) && !empty($exp[1])) {
									$imageData = base64_decode($exp[1]);
									$uploadPath = ROOTPATH . 'public/images/presensi/';
									
									// Ensure directory exists
									if (!is_dir($uploadPath)) {
										mkdir($uploadPath, 0755, true);
									}
									
									$fileSaved = file_put_contents($uploadPath . $nama_file, $imageData);
									
									if ($fileSaved === false) {
										log_message('error', 'Presensi pulang: Gagal menyimpan foto. Path: ' . $uploadPath . $nama_file);
									} else {
										// Double-check record still exists before update
										$presensiRecordCheck = $this->presensiModel->find($latestMasukId);
										if ($presensiRecordCheck) {
											$updateData = [
												'foto' => $nama_file,
												'latitude' => $data['location']['coords']['latitude'],
												'longitude' => $data['location']['coords']['longitude']
											];
											
											$photoUpdateResult = $this->presensiModel->update($latestMasukId, $updateData);
											if (!$photoUpdateResult) {
												log_message('error', 'Presensi pulang: Gagal update foto/lokasi. Record ID: ' . $latestMasukId);
											}
										} else {
											log_message('error', 'Presensi pulang: Record tidak ditemukan saat update foto. Record ID: ' . $latestMasukId);
										}
									}
								}
							} catch (\Exception $e) {
								log_message('error', 'Presensi pulang: Error saat menyimpan foto/lokasi: ' . $e->getMessage());
								// Don't fail the entire operation if photo save fails
							}
						}
						
						$db->transComplete();
						
						if ($db->transStatus() === false) {
							log_message('error', 'Presensi pulang: Transaction gagal. User ID: ' . $id_user);
							$result = ['status' => 'error', 'message' => 'Data gagal disimpan. Silakan coba lagi.'];
						} else {
							// Pastikan kita punya ID presensi terbaru setelah pulang
							$presensiId = $latestMasukId;
							
							// Double-check presensiId is valid
							if (!$presensiId || !is_numeric($presensiId)) {
								// Fallback: ambil last presensi dari model
								$lastPresensiCheck = $this->presensiModel->getLastPresensi($id_user);
								if ($lastPresensiCheck && isset($lastPresensiCheck['id'])) {
									$presensiId = $lastPresensiCheck['id'];
								}
							}
							
							$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
							
							// Kirim email notifikasi setelah presensi pulang berhasil disimpan
							// Only send email if user is not required to do patrol OR all patrols are completed
							if ($presensiId && is_numeric($presensiId)) {
								// Get tgl_masuk from the completed shift to check patrols
								$tgl_masuk = null;
								if (isset($latestMasuk['tgl_masuk']) && !empty($latestMasuk['tgl_masuk'])) {
									$tgl_masuk = $latestMasuk['tgl_masuk'];
								}
								
								// Check if patrol is required
								$isPatrolRequired = false;
								if ($assignment) {
									$companyModel = new CompanyModel;
									$companySetting = $companyModel->getCompanySetting($id_company);
									$is_patrol_mode = $companySetting['is_patrol_mode'] ?? 'N';
									
									// Combined: company patrol mode AND user's patrol requirement
									$isPatrolRequired = ($is_patrol_mode == 'Y' && isset($assignment->isPatrolRequired) && $assignment->isPatrolRequired == 1);
								}
								
							// Determine if email should be sent
							$shouldSendEmail = false;
							if (!$isPatrolRequired) {
								// User is not required to do patrol - always send email
								$shouldSendEmail = true;
							} else {
								// User is required to do patrol - check if all patrols are completed using getPatrolProgressByPresensi
								if ($presensiId && $tgl_masuk) {
									$activityPatrolModel = new ActivityPatrolModel;
									$patrolProgress = $activityPatrolModel->getPatrolProgressByPresensi($presensiId, $id_company, $tgl_masuk, $id_user);
									$completed = $patrolProgress['completed'] ?? 0;
									$total = $patrolProgress['total'] ?? 0;
									
									if ($completed == $total && $total > 0) {
										// All patrols completed - send email
										$shouldSendEmail = true;
									} else {
										// Not all patrols completed - don't send email
										log_message('info', 'Presensi email (pulang) tidak dikirim: patrol belum selesai. User ID: ' . $id_user . ', Completed: ' . $completed . ', Total: ' . $total);
									}
								} else {
									// Fallback: if presensiId or tgl_masuk not available, don't send email
									log_message('warning', 'Presensi email (pulang) tidak dikirim: presensiId atau tgl_masuk tidak tersedia. User ID: ' . $id_user);
								}
							}
							
							if ($shouldSendEmail) {
								try {
									$this->sendPresensiEmail($presensiId, $id_company, 'pulang');
								} catch (\Throwable $e) {
									log_message('error', 'Presensi email (pulang) gagal: ' . $e->getMessage());
								}
							}
							}
						}
					} catch (\Exception $e) {
						$db->transRollback();
						log_message('error', 'Presensi pulang error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
						$result = ['status' => 'error', 'message' => 'Terjadi kesalahan saat menyimpan data presensi pulang'];
					}
				} else {
					$result = ['status' => 'error', 'message' => 'Jenis presensi tidak valid'];
				}
			}
			
			echo json_encode($result);
			
		} catch (\Exception $e) {
			log_message('error', 'ajaxSaveData error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
			$result = ['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
			echo json_encode($result);
		}
	}
	
	/**
	 * Kirim email ringkasan presensi ke alamat email company
	 * Mengikuti pola konfigurasi email di Mobile_activity.
	 * Untuk "pulang", kirim email patrol recap dengan format tabel.
	 */
	private function sendPresensiEmail($presensiId, $idCompany, $jenisPresensi)
	{
		// Load email helper
		helper('email_registrasi');
		
		// Ambil data user dari session
		$user = $this->session->get('user');
		$userName = $user['nama'] ?? 'Unknown';
		$userEmail = $user['email'] ?? null;
		
		// Ambil data company
		$companyModel = new \App\Models\CompanyModel;
		$company = $companyModel->find($idCompany);
		if (!$company) {
			log_message('warning', 'Presensi email: Company tidak ditemukan untuk id_company=' . $idCompany);
			return;
		}
		$companyName = $company->nama_company ?? 'Unknown Company';
		
		// Ambil data presensi
		$presensi = $this->presensiModel->find($presensiId);
		if (!$presensi) {
			log_message('warning', 'Presensi email: Data presensi tidak ditemukan untuk id=' . $presensiId);
			return;
		}
		
		// Convert to array if object
		if (is_object($presensi)) {
			$presensi = (array) $presensi;
		}
		
		// Siapkan daftar penerima dari field email company (semicolon-separated)
		$recipientEmails = [];
		if (!empty($company->email)) {
			$emails = explode(';', $company->email);
			foreach ($emails as $email) {
				$email = trim($email);
				if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$recipientEmails[] = $email;
				}
			}
		}
		
		// Fallback ke env config (mengikuti pola Mobile_activity)
		if (empty($recipientEmails)) {
			$fallbackEmail = env('email.activity.recipient', '');
			if (empty($fallbackEmail) && !empty($userEmail)) {
				$fallbackEmail = $userEmail;
			}
			if (!empty($fallbackEmail) && filter_var($fallbackEmail, FILTER_VALIDATE_EMAIL)) {
				$recipientEmails[] = $fallbackEmail;
			}
		}
		
		if (empty($recipientEmails)) {
			log_message('warning', 'Presensi email: Tidak ada email penerima yang valid');
			return;
		}
		
		// For "pulang", send patrol recap email
		if ($jenisPresensi === 'pulang' && !empty($presensi['tgl_masuk']) && !empty($presensi['tgl_keluar'])) {
			// Get activities with patrol scans for this shift
			$activityModel = new \App\Models\ActivityModel;
			$activitiesList = $activityModel->getActivitiesWithPatrolsByPresensi(
				$presensiId,
				$presensi['tgl_masuk'],
				$presensi['tgl_keluar']
			);
			
			// Generate email content using patrol recap template
			$body = email_patrol_recap_content($presensi, $activitiesList, $companyName, $userName);
			
			// Collect all activity photos for attachments
			$attachments = [];
			$upload_path = ROOTPATH . 'public/images/activity/';
			
			foreach ($activitiesList as $activity) {
				if (!empty($activity['foto_activity'])) {
					$photos_array = json_decode($activity['foto_activity'], true);
					
					if (json_last_error() === JSON_ERROR_NONE && is_array($photos_array)) {
						foreach ($photos_array as $index => $photo) {
							if (isset($photo['file_name'])) {
								$file_path = $upload_path . $photo['file_name'];
								
								if (file_exists($file_path)) {
									$attachments[] = [
										'path' => $file_path,
										'name' => 'patrol_photo_' . $activity['id_activity'] . '_' . ($index + 1) . '_' . $photo['file_name']
									];
								}
							}
						}
					}
				}
			}
			
			// Format subject: "Rekap Aktifitas Patroli - User Name - Start Date - End Date"
			$start_date = date('d/m/Y', strtotime($presensi['tgl_masuk']));
			$end_date = date('d/m/Y', strtotime($presensi['tgl_keluar']));
			$subject = 'Rekap Aktifitas Patroli - ' . $userName . ' - ' . $start_date . ' - ' . $end_date;
			
		} else {
			// For "masuk" or other types, use simple email format
			$tanggal = null;
			$waktu = null;
			if (!empty($presensi['tgl_keluar']) && $jenisPresensi === 'pulang') {
				$tanggal = date('Y-m-d', strtotime($presensi['tgl_keluar']));
				$waktu = date('H:i:s', strtotime($presensi['tgl_keluar']));
			} else {
				$tanggal = date('Y-m-d', strtotime($presensi['tgl_masuk']));
				$waktu = date('H:i:s', strtotime($presensi['tgl_masuk']));
			}
			
			// Koordinat GPS (jika ada)
			$latitude = $presensi['latitude'] ?? null;
			$longitude = $presensi['longitude'] ?? null;
			$gpsUrl = null;
			if ($latitude && $longitude) {
				$gpsUrl = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
			}
			
			// Siapkan konten email sederhana
			$jenisLabel = strtoupper($jenisPresensi);
			$subject = 'Presensi ' . $jenisLabel . ' - ' . $userName . ' - ' . $tanggal;
			
			$body  = '<html><body>';
			$body .= '<p>Yth. Bapak/Ibu,<br><br>';
			$body .= 'Berikut ringkasan presensi yang baru saja tercatat di sistem:</p>';
			$body .= '<table cellpadding="4" cellspacing="0" border="0">';
			$body .= '<tr><td><strong>Karyawan</strong></td><td>: ' . htmlspecialchars($userName) . '</td></tr>';
			$body .= '<tr><td><strong>Perusahaan</strong></td><td>: ' . htmlspecialchars($companyName) . '</td></tr>';
			$body .= '<tr><td><strong>Jenis Presensi</strong></td><td>: ' . htmlspecialchars($jenisLabel) . '</td></tr>';
			$body .= '<tr><td><strong>Tanggal</strong></td><td>: ' . htmlspecialchars($tanggal) . '</td></tr>';
			$body .= '<tr><td><strong>Waktu</strong></td><td>: ' . htmlspecialchars($waktu) . '</td></tr>';
			
			if ($latitude && $longitude) {
				$body .= '<tr><td><strong>Koordinat GPS</strong></td><td>: ' . htmlspecialchars($latitude) . ', ' . htmlspecialchars($longitude) . '</td></tr>';
				if ($gpsUrl) {
					$body .= '<tr><td><strong>Lokasi</strong></td><td>: <a href="' . htmlspecialchars($gpsUrl) . '" target="_blank">Lihat di Google Maps</a></td></tr>';
				}
			}
			
			$body .= '</table>';
			$body .= '<p style="margin-top:16px;">Email ini dikirim otomatis oleh sistem presensi dan tidak perlu dibalas.</p>';
			$body .= '</body></html>';
			
			$attachments = [];
		}
		
		// Kirim email ke masing-masing penerima
		$emailConfig = new \Config\EmailConfig;
		$emailLib = new \App\Libraries\SendEmail;
		$emailLib->init();
		$emailLib->setProvider($emailConfig->provider);
		
		foreach ($recipientEmails as $recipient) {
			$emailData = [
				'from_email'     => $emailConfig->from,
				'from_title'     => $emailConfig->fromTitle,
				'to_email'       => $recipient,
				'to_name'        => $companyName,
				'email_subject'  => $subject,
				'email_content'  => $body,
				'attachments'    => $attachments,
			];
			
			$result = $emailLib->send($emailData);
			if ($result['status'] == 'ok') {
				log_message('info', 'Presensi email terkirim ke: ' . $recipient);
			} else {
				log_message('error', 'Gagal mengirim presensi email ke ' . $recipient . ': ' . $result['message']);
			}
		}
	}
	
	/**
	 * AJAX endpoint to refresh presensi buttons and history sections
	 * Returns HTML for both sections as JSON
	 */
	public function ajaxRefreshSections() {
		// Prepare data same as index() method
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
		
		// Get active companies for this user
		$userCompanyModel = new UserCompanyModel;
		$id_user = $this->session->get('user')['id_user'];
		
		// Get last 5 attendance records for display
		$last5Riwayat = $this->model->getLast5RiwayatPresensi($id_user);
		
		$companies = $userCompanyModel->getActiveCompanyByUser($id_user);
		
		// Load company settings for each company and check patrol status
		$companyModel = new \App\Models\CompanyModel;
		$activityPatrolModel = new ActivityPatrolModel;
		$patrol_status = [];
		
		// Get current shift's tgl_masuk if user has active shift
		$lastPresensi = $this->presensiModel->getLastPresensi($id_user);
		$tgl_masuk = null;
		if ($lastPresensi && !empty($lastPresensi['tgl_masuk']) && empty($lastPresensi['tgl_keluar'])) {
			// User has active shift, use its tgl_masuk for patrol validation
			$tgl_masuk = $lastPresensi['tgl_masuk'];
		}
		
		foreach ($companies as $company) {
			$company->setting_data = $companyModel->getCompanySetting($company->id_company);
			
			// Check if patrol is required for this company
			$is_patrol_mode = $company->setting_data['is_patrol_mode'] ?? 'N';
			$isPatrolRequired = ($is_patrol_mode == 'Y' && isset($company->isPatrolRequired) && $company->isPatrolRequired == 1);
			
			if ($isPatrolRequired) {
				// Check if all patrols are completed (within current shift if active)
				$allCompleted = $activityPatrolModel->areAllPatrolsCompleted($id_user, $company->id_company, $tgl_masuk);
				$uncompletedPatrols = [];
				
				if (!$allCompleted) {
					$uncompletedPatrols = $activityPatrolModel->getUncompletedPatrols($id_user, $company->id_company, $tgl_masuk);
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
		
		// Get active shift patrol data if user has active shift
		$active_shift_patrol = null;
		if ($lastPresensi && !empty($lastPresensi['tgl_masuk']) && empty($lastPresensi['tgl_keluar'])) {
			// User has active shift
			$id_user_presensi = $lastPresensi['id'] ?? null;
			$active_company_id = $lastPresensi['id_company'] ?? null;
			$tgl_masuk = $lastPresensi['tgl_masuk'] ?? null;
			
			if ($id_user_presensi && $active_company_id && $tgl_masuk) {
				// Get patrol progress for this active shift
				// Pass tgl_masuk and id_user to count patrols from activities with NULL id_user_presensi created after shift start
				$patrolProgress = $activityPatrolModel->getPatrolProgressByPresensi($id_user_presensi, $active_company_id, $tgl_masuk, $id_user);
				
				// Get last patrol data for this active shift
				$lastPatrolData = $activityPatrolModel->getLastPatrolDataByPresensi($id_user_presensi, $tgl_masuk, $id_user, $active_company_id);
				
				// Get next patrol (first uncompleted)
				$nextPatrolData = $activityPatrolModel->getNextPatrolByPresensi($id_user_presensi, $active_company_id, $tgl_masuk, $id_user);
				
				// Check if patrol is required for this company
				$isPatrolRequired = false;
				if (isset($patrol_status[$active_company_id])) {
					$isPatrolRequired = $patrol_status[$active_company_id]['is_required'] ?? false;
				}
				
				$active_shift_patrol = [
					'id_user_presensi' => $id_user_presensi,
					'id_company' => $active_company_id,
					'is_required' => $isPatrolRequired,
					'progress' => $patrolProgress,
					'last_patrol' => $lastPatrolData,
					'next_patrol' => $nextPatrolData,
					'is_complete' => $patrolProgress['percentage'] >= 100
				];
			}
		}
		
		// Get latest presensi record
		$last_presensi = $lastPresensi;
		
		// Get company-specific settings
		$company_setting = null;
		if (!empty($companies)) {
			$company_setting = $companies[0]->setting_data ?? null;
		}
		
		// Fallback to global setting if no company setting
		if (!$company_setting) {
			$company_setting = [
				'hari_kerja' => json_decode($this->data['setting_presensi']['hari_kerja'], true) ?: [1,2,3,4,5],
				'gunakan_foto_selfi' => $this->data['setting_presensi']['gunakan_foto_selfi'] ?? 'Y',
				'gunakan_radius_lokasi' => $this->data['setting_presensi']['gunakan_radius_lokasi'] ?? 'Y',
				'latitude' => $this->data['setting_presensi']['latitude'] ?? '-7.797068',
				'longitude' => $this->data['setting_presensi']['longitude'] ?? '110.370529',
				'radius_nilai' => $this->data['setting_presensi']['radius_nilai'] ?? '1.00',
				'radius_satuan' => $this->data['setting_presensi']['radius_satuan'] ?? 'km'
			];
		}
		
		// Determine waktu_masuk and waktu_pulang from latest record
		$waktu_masuk = $waktu_pulang = 'Belum absen';
		$tanggal_masuk = $tanggal_pulang = '';
		
		$last = $lastPresensi;
		if ($last && is_object($last)) {
			$last = (array) $last;
		}
		
		if ($last) {
			if (empty($last['tgl_keluar'])) {
				if (!empty($last['tgl_masuk'])) {
					$waktu_masuk = date('H:i', strtotime($last['tgl_masuk']));
					$tanggal_masuk = date('d/m/Y', strtotime($last['tgl_masuk']));
				}
			}
		}
		
		// Check if today is a working day
		$today_day_of_week = date('w');
		$is_today_working_day = in_array($today_day_of_week, $company_setting['hari_kerja']);
		
		// Render buttons section HTML
		ob_start();
		if ($is_today_working_day) {
			?>
			<div id="presensi-buttons-container">
				<div class="bg-light rounded-3 shadow-sm p-3 mb-3">
					<div class="row g-2">
						<div class="col-6">
							<a id="presensi-masuk" href="#" class="presensi-container box-absen-masuk d-flex rounded-3 px-3 py-3 w-100">
								<div class="d-flex align-items-center w-100">
									<i class="bi bi-box-arrow-in-right me-3 text-success icon-box-presensi" style="font-size:30px"></i>
									<div class="w-100">
										<div class="d-flex justify-content-between align-items-center">
											<h6 class="m-0 fw-semibold">Masuk</h6>
										</div>
										<p class="mt-1 mb-0 waktu-presensi fs-5 fw-semibold"><?=$waktu_masuk?></p>
										<?php if ($tanggal_masuk): ?>
										<p class="mt-1 mb-0 text-muted small"><?=$tanggal_masuk?></p>
										<?php endif; ?>
									</div>
								</div>
							</a>
						</div>
						<div class="col-6">
							<a id="presensi-pulang" href="#" class="bg-light presensi-container box-absen-pulang rounded-3 px-3 py-3 d-block" style="background:#fff6e8 !important;">
								<div class="d-flex align-items-center">
									<i class="bi bi-box-arrow-right me-3 text-warning icon-box-presensi" style="font-size:27px"></i>
									<div class="w-100">
										<div class="d-flex justify-content-between align-items-center">
											<h6 class="m-0 fw-semibold">Pulang</h6>
										</div>
										<p class="mt-1 mb-0 waktu-presensi fs-5 fw-semibold"><?=$waktu_pulang?></p>
										<?php if ($tanggal_pulang): ?>
										<p class="mt-1 mb-0 text-muted small"><?=$tanggal_pulang?></p>
										<?php endif; ?>
										<?php
										// Check patrol status for active shift
										if (isset($active_shift_patrol) && $active_shift_patrol && $active_shift_patrol['is_required']):
											$patrolProgress = $active_shift_patrol['progress'] ?? ['percentage' => 0, 'completed' => 0, 'total' => 0];
											$percentage = $patrolProgress['percentage'] ?? 0;
											$isComplete = $active_shift_patrol['is_complete'] ?? false;
											$nextPatrol = $active_shift_patrol['next_patrol'] ?? null;
										?>
											<div class="mt-2">
												<?php if (!$isComplete): ?>
													<p class="mb-1 text-danger small fw-semibold">
														<i class="fas fa-exclamation-circle me-1"></i>Patroli belum lengkap
													</p>
												<?php endif; ?>
												<div class="d-flex align-items-center mb-1">
													<div class="progress flex-grow-1 me-2" style="height: 8px;">
														<div class="progress-bar <?= $isComplete ? 'bg-success' : 'bg-warning' ?>" 
															role="progressbar" 
															style="width: <?= $percentage ?>%"
															aria-valuenow="<?= $percentage ?>" 
															aria-valuemin="0" 
															aria-valuemax="100">
														</div>
													</div>
													<small class="text-muted fw-semibold"><?= $percentage ?>%</small>
												</div>
												<?php if ($nextPatrol): ?>
													<p class="mb-0 text-muted small">
														<strong>Patroli berikutnya:</strong><br>
														<?= htmlspecialchars($nextPatrol['nama_patrol'] ?? 'Unknown') ?>
														<?php if (isset($nextPatrol['urutan'])): ?>
															<br><small>Urutan: <?= $nextPatrol['urutan'] ?></small>
														<?php endif; ?>
													</p>
												<?php endif; ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</a>
						</div>
					</div>
				</div>
				<div id="alert-lokasi"></div>
			</div>
			<?php
		}
		$buttons_html = ob_get_clean();
		
		// Render history section HTML
		ob_start();
		$no = 1;
		?>
		<div id="presensi-history-container">
			<div class="d-flex align-items-center justify-content-between mt-4 mb-2">
				<p class="text-light mb-0 fw-semibold">
					Riwayat Presensi
				</p>
			</div>
			<div class="bg-light p-3 rounded-3 shadow-sm">
				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered table-sm mb-0 align-middle">
						<thead class="table-dark">
							<tr>
								<th class="text-center" style="width: 50px;">No</th>
								<th>Tanggal</th>
								<th class="text-center">Masuk</th>
								<th class="text-center">Pulang</th>
								<th class="text-center">Jam Kerja</th>
							</tr>
						</thead>
						<tbody>
							<?php
							if (empty($last5Riwayat)) {
								echo '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data presensi untuk periode ini</td></tr>';
							} else {
								foreach ($last5Riwayat as $record) {
									// Format waktu masuk
									$waktu_masuk_display = '-';
									if (!empty($record['presensi_masuk'])) {
										$waktu_masuk_time = substr($record['presensi_masuk'], 0, 5);
										$waktu_masuk_display = '<span>' . $waktu_masuk_time . '</span>';
									}
									
									// Format waktu pulang
									$waktu_pulang_display = '-';
									if (!empty($record['presensi_pulang'])) {
										$waktu_pulang_time = substr($record['presensi_pulang'], 0, 5);
										$waktu_pulang_display = '<span>' . $waktu_pulang_time . '</span>';
									}
									
									// Determine date display
									$shift_date = $record['shift_date'] ?? null;
									$tanggal_display = '-';
									if ($shift_date) {
										$tanggal_display = date('d/m/Y', strtotime($shift_date));
										
										// Check if pulang is on next day
										if (!empty($record['tgl_masuk']) && !empty($record['tgl_keluar'])) {
											$masuk_timestamp = strtotime($record['tgl_masuk']);
											$pulang_timestamp = strtotime($record['tgl_keluar']);
											if ($pulang_timestamp < $masuk_timestamp || date('Y-m-d', $pulang_timestamp) != date('Y-m-d', $masuk_timestamp)) {
												$pulang_date = date('Y-m-d', $pulang_timestamp);
												$tanggal_display = date('d/m/Y', strtotime($shift_date)) . ' - ' . date('d/m/Y', strtotime($pulang_date));
											}
										}
									}
									
									// Format jam kerja
									$jam_kerja_display = '-';
									if (isset($record['durasi']) && $record['durasi'] !== null && $record['durasi'] > 0) {
										$durasi_formatted = number_format($record['durasi'], 2);
										$durasi_formatted = rtrim(rtrim($durasi_formatted, '0'), '.');
										$jam_kerja_display = $durasi_formatted . ' jam';
										
										$is_valid = $record['is_valid'] ?? 0;
										$valid_class = $is_valid ? 'bg-success' : 'bg-warning';
										$valid_text = $is_valid ? 'Valid' : 'Tidak Valid';
										$jam_kerja_display .= ' <span class="badge ' . $valid_class . ' ms-1">' . $valid_text . '</span>';
									}
									
									echo '<tr>';
									echo '<td class="text-center fw-semibold">' . $no . '</td>';
									echo '<td class="fw-medium">' . $tanggal_display . '</td>';
									echo '<td class="text-center">' . $waktu_masuk_display . '</td>';
									echo '<td class="text-center">' . $waktu_pulang_display . '</td>';
									echo '<td class="text-center">' . $jam_kerja_display . '</td>';
									echo '</tr>';
									
									$no++;
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
		$history_html = ob_get_clean();
		
		// Return JSON
		$this->response->setContentType('application/json');
		echo json_encode([
			'status' => 'ok',
			'buttons_html' => $buttons_html,
			'history_html' => $history_html
		]);
	}
}
