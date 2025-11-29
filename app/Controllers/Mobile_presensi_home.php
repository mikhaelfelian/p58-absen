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
					
					// Kirim email notifikasi setelah presensi masuk berhasil disimpan
					try {
						$this->sendPresensiEmail($insertResult, $id_company, 'masuk');
					} catch (\Throwable $e) {
						// Jangan ganggu flow utama jika email gagal
						log_message('error', 'Presensi email (masuk) gagal: ' . $e->getMessage());
					}
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
					
					// Pastikan kita punya ID presensi terbaru setelah pulang
					$presensiId = null;
					if (isset($latestMasukId)) {
						$presensiId = $latestMasukId;
					} else {
						// Fallback: ambil last presensi dari model
						$lastPresensi = $this->presensiModel->getLastPresensi($id_user);
						if ($lastPresensi && isset($lastPresensi['id'])) {
							$presensiId = $lastPresensi['id'];
						}
					}
					
					$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
					
					// Kirim email notifikasi setelah presensi pulang berhasil disimpan
					if ($presensiId) {
						try {
							$this->sendPresensiEmail($presensiId, $id_company, 'pulang');
						} catch (\Throwable $e) {
							log_message('error', 'Presensi email (pulang) gagal: ' . $e->getMessage());
						}
					}
				} else {
					$result = ['status' => 'error', 'message' => 'Anda belum melakukan presensi masuk. Silakan lakukan presensi masuk terlebih dahulu.'];
				}
			} else {
				$result = ['status' => 'error', 'message' => 'Jenis presensi tidak valid'];
			}
		}
		echo json_encode($result);
	}
	
	/**
	 * Kirim email ringkasan presensi ke alamat email company
	 * Mengikuti pola konfigurasi email di Mobile_activity.
	 */
	private function sendPresensiEmail($presensiId, $idCompany, $jenisPresensi)
	{
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
		
		// Tentukan tanggal & waktu berdasarkan jenis presensi
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
				'attachments'    => [],
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
		$companies = $userCompanyModel->getActiveCompanyByUser($id_user);
		
		// Load company settings
		$companyModel = new \App\Models\CompanyModel;
		foreach ($companies as $company) {
			$company->setting_data = $companyModel->getCompanySetting($company->id_company);
		}
		
		// Get latest presensi record
		$last_presensi = $this->presensiModel->getLastPresensi($id_user);
		
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
		
		$last = $last_presensi;
		if ($last && is_object($last)) {
			$last = (array) $last;
		}
		
		if ($last) {
			if (empty($last['tgl_keluar'])) {
				if (!empty($last['tgl_masuk'])) {
					$waktu_masuk = date('H:i', strtotime($last['tgl_masuk']));
					$tanggal_masuk = date('d/m/Y', strtotime($last['tgl_masuk']));
				}
			} else if (!empty($last['tgl_keluar'])) {
				$waktu_pulang = date('H:i', strtotime($last['tgl_keluar']));
				$tanggal_pulang = date('d/m/Y', strtotime($last['tgl_keluar']));
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
		$nama_bulan = nama_bulan();
		$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
		$end_date_ts = strtotime(date('Y-m-d'));
		$start_date_ts = strtotime('-' . $this->data['setting_presensi']['jml_riwayat_presensi_home'] . ' days', $end_date_ts);
		$hari_kerja = $company_setting['hari_kerja'] ?? [1,2,3,4,5];
		$no = 1;
		
		// Collect all attendance records
		$attendance_records = [];
		for ($i = $end_date_ts; $i > $start_date_ts; $i = strtotime('-1 day', $i)) {
			$curr = date('Y-m-d', $i);
			$date_w = date('w', $i);
			
			if (in_array($date_w, $hari_kerja) && key_exists($curr, $riwayat_presensi)) {
				$presensi_masuk = $riwayat_presensi[$curr]['masuk']['presensi_masuk'] ?? null;
				$presensi_pulang = $riwayat_presensi[$curr]['pulang']['presensi_pulang'] ?? null;
				$durasi = $riwayat_presensi[$curr]['durasi'] ?? null;
				$is_valid = $riwayat_presensi[$curr]['is_valid'] ?? 0;
				
				if ($presensi_masuk || $presensi_pulang) {
					$attendance_records[] = [
						'date' => $curr,
						'masuk' => $presensi_masuk,
						'pulang' => $presensi_pulang,
						'durasi' => $durasi,
						'is_valid' => $is_valid
					];
				}
			}
		}
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
							if (empty($attendance_records)) {
								echo '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data presensi untuk periode ini</td></tr>';
							} else {
								foreach ($attendance_records as $record) {
									// Format waktu masuk
									$waktu_masuk_display = '-';
									if ($record['masuk']) {
										$waktu_masuk_time = substr($record['masuk'], 0, 5);
										$waktu_masuk_display = '<span>' . $waktu_masuk_time . '</span>';
									}
									
									// Format waktu pulang
									$waktu_pulang_display = '-';
									if ($record['pulang']) {
										$waktu_pulang_time = substr($record['pulang'], 0, 5);
										$waktu_pulang_display = '<span>' . $waktu_pulang_time . '</span>';
									}
									
									// Determine date display
									$tanggal_display = date('d/m/Y', strtotime($record['date']));
									if ($record['masuk'] && $record['pulang']) {
										$masuk_timestamp = strtotime($record['date'] . ' ' . $record['masuk']);
										$pulang_timestamp = strtotime($record['date'] . ' ' . $record['pulang']);
										
										if ($pulang_timestamp < $masuk_timestamp) {
											$pulang_date = date('Y-m-d', strtotime($record['date'] . ' +1 day'));
											$tanggal_display = date('d/m/Y', strtotime($record['date'])) . ' - ' . date('d/m/Y', strtotime($pulang_date));
										}
									}
									
									// Format jam kerja
									$jam_kerja_display = '-';
									if ($record['durasi'] !== null && $record['durasi'] > 0) {
										$durasi_formatted = number_format($record['durasi'], 2);
										$durasi_formatted = rtrim(rtrim($durasi_formatted, '0'), '.');
										$jam_kerja_display = $durasi_formatted . ' jam';
										
										$valid_class = $record['is_valid'] ? 'bg-success' : 'bg-warning';
										$valid_text = $record['is_valid'] ? 'Valid' : 'Tidak Valid';
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
