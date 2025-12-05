<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\ActivityModel;
use App\Models\UserCompanyModel;
use App\Models\CompanyPatrolModel;
use App\Models\ActivityPatrolModel;
use App\Models\UserPresensiModel;

class Mobile_activity extends BaseController
{
	public function __construct() {
		parent::__construct();
		$this->model = new ActivityModel;
		$this->data['title'] = 'Input Activity';
		
		// CSS and JS are inline in the view file
		// Mobile layout already includes all necessary libraries
	}
	
	public function index() {
		$userCompanyModel = new UserCompanyModel;
		$companyModel = new \App\Models\CompanyModel;
		$companyPatrolModel = new CompanyPatrolModel;
		$id_user = $this->session->get('user')['id_user'];
		
		// Get active companies for this user with assignment details
		$companies = $userCompanyModel->getActiveCompanyByUser($id_user);
		
		// Get patrol settings for each company
		$companies_with_patrol = [];
		foreach ($companies as $company) {
			// Get company's patrol mode setting
			$setting = $companyModel->getCompanySetting($company->id_company);
			$company->is_patrol_mode = $setting['is_patrol_mode'] ?? 'N';
			
			// Check if this user is required to patrol for this company
			// Combined: company patrol mode AND user's patrol requirement
			$company->isPatrolRequired = ($setting['is_patrol_mode'] == 'Y' && $company->isPatrolRequired == 1) ? 1 : 0;
			
			$companies_with_patrol[] = $company;
		}
		
		$this->data['companies'] = $companies_with_patrol;
		
		// Preload patrol list for each company (used for offline validation/UX)
		$companies_patrols = [];
		foreach ($companies_with_patrol as $company) {
			$patrols = $companyPatrolModel->getPatrolByCompany($company->id_company);
			$companies_patrols[$company->id_company] = $patrols ? array_values($patrols) : [];
		}
		$this->data['companies_patrols'] = $companies_patrols;
		
		echo view('themes/modern/mobile-activity-home.php', $this->data);
	}
	
	public function riwayat() {
		$id_user = $this->session->get('user')['id_user'];
		$start_date = date('Y-m-d', strtotime('-30 days'));
		$end_date = date('Y-m-d');
		
		$activities = $this->model->getActivityByUser($id_user, $start_date, $end_date);
		
		// Process foto_activity field for each activity
		// foto_activity can be:
		// 1. JSON string: [{"file_name": "activity_123.jpg"}] (new format)
		// 2. Single filename string: "activity_123.jpg" (old format for backward compatibility)
		foreach ($activities as $activity) {
			$activity->foto_activity_images = []; // Array to store processed image file names
			
			if (!empty($activity->foto_activity)) {
				// Debug: log the raw foto_activity value
				log_message('debug', 'Processing foto_activity: ' . substr($activity->foto_activity, 0, 100));
				
				// Try to decode as JSON first (new format)
				$decoded = json_decode($activity->foto_activity, true);
				
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
					// It's JSON array - extract file names
					foreach ($decoded as $photo) {
						if (isset($photo['file_name']) && !empty($photo['file_name'])) {
							$activity->foto_activity_images[] = $photo['file_name'];
						}
					}
					log_message('debug', 'Extracted ' . count($activity->foto_activity_images) . ' images from JSON');
				} else {
					// It's a single filename string (old format)
					$activity->foto_activity_images[] = $activity->foto_activity;
					log_message('debug', 'Using single filename format: ' . $activity->foto_activity);
				}
			} else {
				log_message('debug', 'foto_activity is empty for activity ID: ' . ($activity->id_activity ?? 'unknown'));
			}
		}
		
		$this->data['activities'] = $activities;
		
		echo view('themes/modern/mobile-activity-riwayat.php', $this->data);
	}
	
	public function ajaxSaveActivity() {
		try {
			// Log raw POST data for debugging
			log_message('debug', 'Raw POST data: ' . json_encode($_POST));
			
			// Check if data exists in POST
			if (!isset($_POST['data'])) {
				echo json_encode([
					'status' => 'error',
					'message' => 'Data tidak ditemukan dalam request. POST keys: ' . implode(', ', array_keys($_POST))
				]);
				return;
			}
			
			$data = base64_decode($_POST['data']);
			$data_array = json_decode($data, true);
			
			// Validate JSON decode
			if (json_last_error() !== JSON_ERROR_NONE) {
				echo json_encode([
					'status' => 'error',
					'message' => 'Data tidak valid: ' . json_last_error_msg()
				]);
				return;
			}
			
			// Log for debugging
			log_message('debug', 'Activity save data: ' . json_encode($data_array));
			
			$error = [];
		
		// Validate company assignment
		$userCompanyModel = new UserCompanyModel;
		$hasAccess = $userCompanyModel->checkUserCompanyAccess(
			$this->session->get('user')['id_user'],
			$data_array['id_company']
		);
		
		if (!$hasAccess) {
			$error[] = 'Anda tidak memiliki akses ke company ini';
		}
		
		// Validate active shift - user must have an active presensi (masuk) to add activity
		$id_user = $this->session->get('user')['id_user'];
		$presensiModel = new UserPresensiModel;
		$lastPresensi = $presensiModel->getLastPresensi($id_user);
		
		$id_user_presensi = null;
		if ($lastPresensi && !empty($lastPresensi['tgl_masuk']) && empty($lastPresensi['tgl_keluar'])) {
			// User has active shift, use its ID
			$id_user_presensi = $lastPresensi['id'];
		} else {
			// No active shift found
			$error[] = 'Anda belum absen masuk, tidak bisa menambah aktifitas';
		}
		
		// Validate required fields
		if (empty($data_array['judul_activity'])) {
			$error[] = 'Judul activity harus diisi';
		}
		
		// if (empty($data_array['deskripsi_activity'])) {
		// 	$error[] = 'Deskripsi activity harus diisi';
		// }
		
		// Handle photo upload (multiple photos as JSON array)
		$foto_activity_data = null;
		if (!empty($data_array['foto'])) {
			$foto_data = $data_array['foto'];
			
			// Check if it's JSON (multiple photos)
			$photos_array = json_decode($foto_data, true);
			
			if (json_last_error() === JSON_ERROR_NONE && is_array($photos_array)) {
				// Multiple photos - save each to file
				$upload_path = ROOTPATH . 'public/images/activity/';
				if (!is_dir($upload_path)) {
					mkdir($upload_path, 0777, true);
				}
				
				$saved_photos = [];
				foreach ($photos_array as $photo) {
					if (isset($photo['image'])) {
						$image = explode('data:image/jpeg;base64,', $photo['image']);
						$image_data = base64_decode(trim($image[1]));
						
						$filename = 'activity_' . time() . '_' . uniqid() . '.jpg';
						file_put_contents($upload_path . $filename, $image_data);
						
						$saved_photos[] = [
							'file_name' => $filename,
							'lat' => $photo['lat'] ?? null,
							'lon' => $photo['lon'] ?? null
						];
					}
				}
				
				// Convert saved photos to JSON
				$foto_activity_data = json_encode($saved_photos);
			} else {
				// Single photo (legacy support)
				$image = explode('data:image/jpeg;base64,', $foto_data);
				$image_data = base64_decode(trim($image[1]));
				
				$foto_filename = 'activity_' . time() . '_' . uniqid() . '.jpg';
				$upload_path = ROOTPATH . 'public/images/activity/';
				
				if (!is_dir($upload_path)) {
					mkdir($upload_path, 0777, true);
				}
				
				file_put_contents($upload_path . $foto_filename, $image_data);
				
				// Convert to new format
				$foto_activity_data = json_encode([[
					'file_name' => $foto_filename,
					'lat' => $data_array['location']['coords']['latitude'] ?? null,
					'lon' => $data_array['location']['coords']['longitude'] ?? null
				]]);
			}
		}
		
		if ($error) {
			echo json_encode(['status' => 'error', 'message' => $error]);
			return;
		}
		
		// Extract GPS coordinates from location object
		$latitude = null;
		$longitude = null;
		
		if (!empty($data_array['location'])) {
			// Check if location has lat/lng directly (new format)
			if (isset($data_array['location']['lat'])) {
				$latitude = $data_array['location']['lat'];
				$longitude = $data_array['location']['lng'] ?? null;
			}
			// Check if location has coords.latitude (old format)
			elseif (isset($data_array['location']['coords']['latitude'])) {
				$latitude = $data_array['location']['coords']['latitude'];
				$longitude = $data_array['location']['coords']['longitude'] ?? null;
			}
		}
		
		// Save activity
		$activity_data = [
			'id_user' => $this->session->get('user')['id_user'],
			'id_company' => $data_array['id_company'],
			'id_user_presensi' => $id_user_presensi,
			'tanggal' => date('Y-m-d'),
			'waktu' => date('H:i:s'),
			'judul_activity' => $data_array['judul_activity'],
			'deskripsi_activity' => $data_array['deskripsi_activity'],
			'foto_activity' => $foto_activity_data,
			'latitude' => $latitude,
			'longitude' => $longitude,
		];
		
		// Log activity data for debugging
		log_message('debug', 'Saving activity with GPS: lat=' . $latitude . ', lon=' . $longitude);
		
		$result = $this->model->saveData($activity_data);
		
		// Save patrol scan if patrol is selected (skip for test data)
		if ($result['status'] == 'ok' && !empty($data_array['id_patrol']) && $data_array['id_patrol'] !== 'TEST_PATROL_ID') {
			$activityPatrolModel = new ActivityPatrolModel;
			$activityPatrolModel->savePatrolScan(
				$result['id_activity'],
				$data_array['id_patrol'],
				$data_array['barcode_scanned'] ?? '',
				$latitude,
				$longitude
			);
		}
		
		// Send email with activity images after successful save
		if ($result['status'] == 'ok') {
			$this->sendActivityEmail($result['id_activity'], $activity_data, $foto_activity_data);
		}
		
		echo json_encode($result);
		
		} catch (\Exception $e) {
			log_message('error', 'Activity save error: ' . $e->getMessage());
			echo json_encode([
				'status' => 'error',
				'message' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()
			]);
		}
	}
	
	/**
	 * Get patrol points for a company
	 */
	public function getPatrolPoints($company_id) {
		// Set cache control headers to prevent browser/proxy caching
		$this->response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
		$this->response->setHeader('Pragma', 'no-cache');
		$this->response->setHeader('Expires', '0');
		
		$patrolModel = new CompanyPatrolModel;
		$activityPatrolModel = new ActivityPatrolModel;
		$presensiModel = new UserPresensiModel;
		$id_user = $this->session->get('user')['id_user'];
		
		// Get active shift's tgl_masuk if user has active shift
		$lastPresensi = $presensiModel->getLastPresensi($id_user);
		$tgl_masuk = null;
		if ($lastPresensi && !empty($lastPresensi['tgl_masuk']) && empty($lastPresensi['tgl_keluar'])) {
			// User has active shift, use its tgl_masuk for patrol validation
			$tgl_masuk = $lastPresensi['tgl_masuk'];
		}
		
		$patrols = $patrolModel->getPatrolByCompany($company_id);
		
		// Get last scanned patrol today to determine next patrol
		$lastScanned = $activityPatrolModel->getLastScannedPatrolToday($id_user, $company_id);
		$lastScanned = $this->normalizePatrolRecord($lastScanned);
		
		// Get all scanned patrols during active shift (or today if no active shift) with their scan times
		$scannedPatrolsToday = $activityPatrolModel->getScannedPatrolsToday($id_user, $company_id, $tgl_masuk);
		
		$nextPatrol = null;
		
		if ($lastScanned) {
			$lastSequenceValue = $this->getPatrolSequenceValue($lastScanned);
			if ($lastSequenceValue !== null) {
				$nextPatrol = $patrolModel->getNextPatrolInSequence($company_id, $id_user, $lastSequenceValue);
			}
		}
		
		if (!$nextPatrol) {
			$nextPatrol = $patrolModel->getFirstPatrol($company_id);
		}
		$nextPatrol = $this->normalizePatrolRecord($nextPatrol);
		
		$this->response->setContentType('application/json');
		echo json_encode([
			'status' => 'ok',
			'data' => $patrols,
			'next_patrol' => $nextPatrol ? $this->formatNextPatrolPayload($nextPatrol) : null,
			'last_scanned' => $lastScanned,
			'scanned_patrols_today' => $scannedPatrolsToday
		]);
	}
	
	/**
	 * Normalize patrol code from QR or OCR input
	 * Extracts and validates PATROL_ pattern
	 * 
	 * @param string $input Raw input from QR scanner or OCR
	 * @return string|null Normalized patrol code or null if invalid
	 */
	private function normalizePatrolCode(string $input): ?string {
		// Trim whitespace
		$cleaned = trim($input);
		
		if (empty($cleaned)) {
			return null;
		}
		
		// Extract PATROL_ pattern using regex - format: PATROL_XXX_XXX_YYYYMMDDHHMMSS
		// More accurate pattern: PATROL_ + 3 digits (company) + _ + 3 digits (sequence) + _ + 14 digits (timestamp)
		// Timestamp format: YYYYMMDDHHMMSS (14 digits)
		if (preg_match('/PATROL_\d{3}_\d{3}_\d{14}/i', $cleaned, $matches)) {
			return $matches[0];
		}
		
		return null;
	}
	
	/**
	 * Validate QR code (sequence checking removed - allow jumping between patrols)
	 * Now supports both QR and OCR input
	 */
	public function validateQRCode() {
		$barcode = $this->request->getPost('barcode');
		$id_company = $this->request->getPost('id_company');
		
		if (empty($barcode)) {
			echo json_encode([
				'status' => 'error',
				'message' => 'Barcode tidak boleh kosong'
			]);
			return;
		}
		
		// Normalize input (handles both QR and OCR)
		$normalized_barcode = $this->normalizePatrolCode($barcode);
		
		if (!$normalized_barcode) {
			echo json_encode([
				'status' => 'error',
				'message' => 'QR Code tidak valid atau tidak ditemukan untuk company ini'
			]);
			return;
		}
		
		$patrolModel = new CompanyPatrolModel;
		
		// Validate barcode exists and belongs to company
		$patrol = $patrolModel->validateBarcode($normalized_barcode, $id_company);
		
		if (!$patrol) {
			echo json_encode([
				'status' => 'error',
				'message' => 'QR Code tidak valid atau tidak ditemukan untuk company ini'
			]);
			return;
		}
		$patrol = $this->normalizePatrolRecord($patrol);
		
		// Valid patrol - allow scanning any patrol point in any order
		echo json_encode([
			'status' => 'ok',
			'data' => $patrol
		]);
	}
	
	private function normalizePatrolRecord($record) {
		if (!$record) {
			return null;
		}
		if (is_array($record)) {
			$record = (object) $record;
		}
		return $record;
	}
	
	private function getPatrolSequenceValue($patrol) {
		$patrol = $this->normalizePatrolRecord($patrol);
		if (!$patrol) {
			return null;
		}
		if ($this->patrolHasUrutan($patrol)) {
			return (int) $patrol->urutan;
		}
		return isset($patrol->id_patrol) ? (int) $patrol->id_patrol : null;
	}
	
	private function patrolHasUrutan($patrol) {
		return is_object($patrol) && property_exists($patrol, 'urutan') && $patrol->urutan !== null;
	}
	
	private function formatNextPatrolPayload($patrol) {
		$patrol = $this->normalizePatrolRecord($patrol);
		if (!$patrol) {
			return null;
		}
		$order = $this->getPatrolSequenceValue($patrol);
		if (!$this->patrolHasUrutan($patrol)) {
			$patrol->urutan = $order;
		}
		return [
			'id_patrol' => $patrol->id_patrol ?? null,
			'nama_patrol' => $patrol->nama_patrol ?? '',
			'urutan' => $order,
			'barcode' => $patrol->barcode ?? ''
		];
	}
	
	/**
	 * Send email with activity details and images
	 */
	private function sendActivityEmail($id_activity, $activity_data, $foto_activity_data) {
		try {
			// Load email helper
			helper('email_registrasi');
			
			// Get user from session
			$user = $this->session->get('user');
			$user_name = $user['nama'] ?? 'Unknown';
			$user_email = $user['email'] ?? null;
			
			// Get company information
			$companyModel = new \App\Models\CompanyModel;
			$company = $companyModel->find($activity_data['id_company']);
			$company_name = $company ? $company->nama_company : 'Unknown Company';
			
			// Get recipient emails from company.email field (semicolon-separated)
			$recipient_emails = [];
			if ($company && !empty($company->email)) {
				// Split by semicolon and trim each email
				$emails = explode(';', $company->email);
				foreach ($emails as $email) {
					$email = trim($email);
					if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$recipient_emails[] = $email;
					}
				}
			}
			
			// Fallback: If no company emails, use user email or config email
			if (empty($recipient_emails)) {
				$fallback_email = env('email.activity.recipient', '');
				if (empty($fallback_email) && !empty($user_email)) {
					$fallback_email = $user_email;
				}
				if (!empty($fallback_email) && filter_var($fallback_email, FILTER_VALIDATE_EMAIL)) {
					$recipient_emails[] = $fallback_email;
				}
			}
			
			// If still no valid emails, log and return
			if (empty($recipient_emails)) {
				log_message('warning', 'Activity email: No valid recipient email found (company.email empty, user email empty, config email empty)');
				return;
			}
			
			// Get patrol information if available
			$patrol_info = null;
			if (!empty($id_activity)) {
				$activityPatrolModel = new ActivityPatrolModel;
				$patrol_scans = $activityPatrolModel->getPatrolScansByActivity($id_activity);
				if (!empty($patrol_scans)) {
					// Get the first patrol scan (most recent or first in sequence)
					$patrol_info = $patrol_scans[0];
				}
			}
			
			// Prepare activity data with ID for email template
			$email_activity_data = $activity_data;
			$email_activity_data['id_activity'] = $id_activity;
			
			// Generate email content using helper function
			$email_content = email_activity_report_content($email_activity_data, $company_name, $user_name, $patrol_info);
			
			// Prepare attachments (images as files)
			$attachments = [];
			$upload_path = ROOTPATH . 'public/images/activity/';
			
			if (!empty($foto_activity_data)) {
				$photos_array = json_decode($foto_activity_data, true);
				
				if (json_last_error() === JSON_ERROR_NONE && is_array($photos_array)) {
					foreach ($photos_array as $index => $photo) {
						if (isset($photo['file_name'])) {
							$file_path = $upload_path . $photo['file_name'];
							
							if (file_exists($file_path)) {
								$attachments[] = [
									'path' => $file_path,
									'name' => 'activity_photo_' . ($index + 1) . '_' . $photo['file_name']
								];
							}
						}
					}
				}
			}
			
			// Send email to each recipient
			$email_config = new \Config\EmailConfig;
			$email_subject = 'Activity Report: ' . $activity_data['judul_activity'] . ' - ' . $activity_data['tanggal'];
			
			$emaillib = new \App\Libraries\SendEmail;
			$emaillib->init();
			$emaillib->setProvider($email_config->provider);
			
			$success_count = 0;
			$fail_count = 0;
			
			foreach ($recipient_emails as $recipient_email) {
				$email_data = [
					'from_email'     => $email_config->from,
					'from_title'     => $email_config->fromTitle,
					'to_email'       => $recipient_email,
					'to_name'        => $user_name,
					'email_subject'  => $email_subject,
					'email_content'  => $email_content,
					'attachments'    => $attachments,
				];
				
				$send_result = $emaillib->send($email_data);
				
				if ($send_result['status'] == 'ok') {
					$success_count++;
					log_message('info', 'Activity email sent successfully to: ' . $recipient_email);
				} else {
					$fail_count++;
					log_message('error', 'Failed to send activity email to ' . $recipient_email . ': ' . $send_result['message']);
				}
			}
			
			// Log summary
			if ($success_count > 0) {
				log_message('info', 'Activity email: ' . $success_count . ' email(s) sent successfully, ' . $fail_count . ' failed');
			}
			
		} catch (\Exception $e) {
			log_message('error', 'Error sending activity email: ' . $e->getMessage());
			// Don't throw exception - email failure shouldn't break activity save
		}
	}
}

