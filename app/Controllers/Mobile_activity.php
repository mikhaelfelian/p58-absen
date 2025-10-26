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
		$id_user = $this->session->get('user')['id_user'];
		
		// Get active companies for this user
		$companies = $userCompanyModel->getActiveCompanyByUser($id_user);
		
		$this->data['companies'] = $companies;
		
		echo view('themes/modern/mobile-activity-home.php', $this->data);
	}
	
	public function riwayat() {
		$id_user = $this->session->get('user')['id_user'];
		$start_date = date('Y-m-d', strtotime('-30 days'));
		$end_date = date('Y-m-d');
		
		$activities = $this->model->getActivityByUser($id_user, $start_date, $end_date);
		
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
		
		// Validate required fields
		if (empty($data_array['judul_activity'])) {
			$error[] = 'Judul activity harus diisi';
		}
		
		if (empty($data_array['deskripsi_activity'])) {
			$error[] = 'Deskripsi activity harus diisi';
		}
		
		// Handle photo upload
		$foto_filename = null;
		if (!empty($data_array['foto'])) {
			$image = explode('data:image/jpeg;base64,', $data_array['foto']);
			$image_data = base64_decode(trim($image[1]));
			
			$foto_filename = 'activity_' . time() . '_' . uniqid() . '.jpg';
			$upload_path = ROOTPATH . 'public/images/activity/';
			
			if (!is_dir($upload_path)) {
				mkdir($upload_path, 0777, true);
			}
			
			file_put_contents($upload_path . $foto_filename, $image_data);
		}
		
		if ($error) {
			echo json_encode(['status' => 'error', 'message' => $error]);
			return;
		}
		
		// Save activity
		$activity_data = [
			'id_user' => $this->session->get('user')['id_user'],
			'id_company' => $data_array['id_company'],
			'id_user_presensi' => $data_array['id_user_presensi'] ?? null,
			'tanggal' => date('Y-m-d'),
			'waktu' => date('H:i:s'),
			'judul_activity' => $data_array['judul_activity'],
			'deskripsi_activity' => $data_array['deskripsi_activity'],
			'foto_activity' => $foto_filename,
			'latitude' => $data_array['location']['coords']['latitude'] ?? null,
			'longitude' => $data_array['location']['coords']['longitude'] ?? null,
		];
		
		$result = $this->model->saveData($activity_data);
		
		// Save patrol scan if patrol is selected (skip for test data)
		if ($result['status'] == 'ok' && !empty($data_array['id_patrol']) && $data_array['id_patrol'] !== 'TEST_PATROL_ID') {
			$activityPatrolModel = new ActivityPatrolModel;
			$activityPatrolModel->savePatrolScan(
				$result['id_activity'],
				$data_array['id_patrol'],
				$data_array['barcode_scanned'] ?? '',
				$data_array['location']['coords']['latitude'] ?? null,
				$data_array['location']['coords']['longitude'] ?? null
			);
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
		$patrolModel = new CompanyPatrolModel;
		$patrols = $patrolModel->getPatrolByCompany($company_id);
		
		echo json_encode([
			'status' => 'ok',
			'data' => $patrols
		]);
	}
	
	/**
	 * Validate QR code
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
		
		$patrolModel = new CompanyPatrolModel;
		$patrol = $patrolModel->validateBarcode($barcode, $id_company);
		
		if ($patrol) {
			echo json_encode([
				'status' => 'ok',
				'data' => $patrol
			]);
		} else {
			echo json_encode([
				'status' => 'error',
				'message' => 'QR Code tidak valid atau tidak ditemukan untuk company ini'
			]);
		}
	}
}

