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
		$data = base64_decode($_POST['data']);
		$data_array = json_decode($data, true);
		
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
		echo json_encode($result);
	}
}

