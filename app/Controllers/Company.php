<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\CompanyModel;
use App\Models\CompanyPatrolModel;
use App\Libraries\SimpleQRCode;

class Company extends BaseController
{
	public function __construct() {
		parent::__construct();
		$this->model = new CompanyModel;
		$this->patrolModel = new CompanyPatrolModel;
		$this->data['title'] = 'Master Company';
		
		// Add required JS/CSS
		$this->addJs($this->config->baseURL . 'public/vendors/leafletjs/leaflet.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/leafletjs/leaflet.css');
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/company.js');
	}
	
	public function index() {
		$this->hasPermission('read_all');
		$this->view('company-list.php', $this->data);
	}
	
	public function add() {
		$this->hasPermission('create');
		
		if (!empty($_POST['submit'])) {
			$error = $this->validateForm();
			if ($error) {
				$this->data['message'] = ['status' => 'error', 'message' => $error];
				$this->data['form_errors'] = $error;
			} else {
				$result = $this->model->saveData();
				$this->data['message'] = $result;
				
				if ($result['status'] == 'ok') {
					// Stay on the form and show success message
					$this->data['message'] = ['status' => 'success', 'message' => 'Data perusahaan berhasil disimpan'];
					// Reload the form with the saved data
					$company_id = $this->model->getInsertID();
					$this->data['company'] = $this->model->getCompanyById($company_id);
					$this->data['mode'] = 'edit';
					$this->data['company_setting'] = $this->model->getCompanySetting($company_id);
					$this->data['existing_patrols'] = $this->patrolModel->getPatrolByCompany($company_id);
				}
			}
		}
		
		$this->data['mode'] = 'add';
		$this->data['form_errors'] = [];
		$this->data['company_setting'] = [
			'hari_kerja' => [1,2,3,4,5],
			'gunakan_foto_selfi' => 'Y',
			'gunakan_radius_lokasi' => 'Y',
			'latitude' => '-7.797068',
			'longitude' => '110.370529',
			'radius_nilai' => '1.00',
			'radius_satuan' => 'km'
		];
		$this->view('company-form.php', $this->data);
	}
	
	public function edit() {
		$this->hasPermission('update_all');
		
		$id = $this->request->getGet('id');
		$company = $this->model->getCompanyById($id);
		
		if (!$company) {
			$this->errorDataNotFound();
			return;
		}
		
		if (!empty($_POST['submit'])) {
			$error = $this->validateForm();
			if ($error) {
				$this->data['message'] = ['status' => 'error', 'message' => $error];
				$this->data['form_errors'] = $error;
			} else {
				$result = $this->model->saveData();
				$this->data['message'] = $result;
				
				if ($result['status'] == 'ok') {
					// Stay on the form and show success message
					$this->data['message'] = ['status' => 'success', 'message' => 'Data perusahaan berhasil diperbarui'];
					// Reload the form with the updated data
					$company_id = $this->request->getPost('id_company');
					$this->data['company'] = $this->model->getCompanyById($company_id);
					$this->data['company_setting'] = $this->model->getCompanySetting($company_id);
					$this->data['existing_patrols'] = $this->patrolModel->getPatrolByCompany($company_id);
				}
			}
		}
		
		$this->data['mode'] = 'edit';
		$this->data['company'] = $company;
		$this->data['form_errors'] = [];
		$this->data['company_setting'] = $this->model->getCompanySetting($company->id_company);
		$this->data['existing_patrols'] = $this->patrolModel->getPatrolByCompany($company->id_company);
		$this->view('company-form.php', $this->data);
	}
	
	private function validateForm() {
		$validation = \Config\Services::validation();
		$validation->setRule('nama_company', 'Nama Company', 'trim|required');
		$validation->setRule('latitude', 'Latitude', 'trim|required');
		$validation->setRule('longitude', 'Longitude', 'trim|required');
		$validation->setRule('radius_nilai', 'Radius', 'trim|required|numeric');
		$validation->withRequest($this->request)->run();
		
		return $validation->getErrors();
	}
	
	public function ajaxDelete() {
		$this->hasPermission('delete_all');
		
		if (empty($_POST['id'])) {
			echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
			return;
		}
		
		$result = $this->model->deleteData();
		
		if ($result) {
			echo json_encode(['status' => 'ok', 'message' => 'Data berhasil dihapus']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Data gagal dihapus']);
		}
	}
	
	public function getDataDT() {
		$this->hasPermission('read_all');
		
		$num_data = $this->model->countAllData();
		$result['draw'] = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = $num_data;
		
		$query = $this->model->getListData();
		$result['recordsFiltered'] = $query['total_filtered'];
		
		helper('html');
		
		$no = $this->request->getPost('start') + 1 ?: 1;
		foreach ($query['data'] as $key => &$val) {
			$val->ignore_urut = $no;
			//$val->ignore_id = $val->id_company;  // Include ID for DataTable
			
			// Build actions
			$val->ignore_action = '<div class="btn-action-group">';
			
			// Edit button - only show if user has permission (use btn_link for navigation)
			if (has_permission('update_all')) {
				$val->ignore_action .= btn_link([
					'url' => $this->moduleURL . '/edit?id=' . $val->id_company,
					'attr' => ['class' => 'btn btn-success btn-xs me-1',
							   'data-bs-toggle' => 'tooltip', 
							   'data-bs-title' => 'Edit Data'],
					'icon' => 'fas fa-edit',
					'label' => 'Edit'
				]);
			}
			
			// Delete button - only show if user has permission (use btn_label for JS action)
			if (has_permission('delete_all')) {
				$val->ignore_action .= btn_label([
					'label' => 'Delete',
					'icon' => 'fas fa-times',
					'attr' => ['class' => 'btn btn-danger btn-xs btn-delete',
							   'data-id' => $val->id_company,
							   'data-bs-toggle' => 'tooltip', 
							   'data-bs-title' => 'Delete Data']
				]);
			}
			
			$val->ignore_action .= '</div>';
			
			// Format status
			$val->status = $val->status == 'active' ? 
				'<span class="badge bg-success">Active</span>' : 
				'<span class="badge bg-danger">Inactive</span>';
			
			$no++;
		}
		
		$result['data'] = $query['data'];
		echo json_encode($result);
	}
	
	/**
	 * Generate QR code image for a patrol point
	 */
	public function generateBarcode($patrol_id)
	{
		$patrol = $this->patrolModel->find($patrol_id);
		if (!$patrol) {
			show_404();
		}
		
		$qrGenerator = new SimpleQRCode();
		$filepath = $qrGenerator->generateQRCodeImage($patrol['barcode']);
		
		// Return the image
		$image = file_get_contents($filepath);
		$this->response->setContentType('image/png');
		$this->response->setBody($image);
		return $this->response;
	}
	
	/**
	 * Print all QR codes for a company as PDF
	 */
	public function printBarcodes($company_id)
	{
		$company = $this->model->getCompanyById($company_id);
		if (!$company) {
			show_404();
		}
		
		$patrol_points = $this->patrolModel->getPatrolByCompany($company_id);
		if (empty($patrol_points)) {
			$this->data['message'] = ['status' => 'error', 'message' => 'Tidak ada titik patroli untuk dicetak'];
			$this->view('company-list.php', $this->data);
			return;
		}
		
		$qrGenerator = new SimpleQRCode();
		$qrGenerator->generatePrintablePDF($patrol_points, $company->nama_company);
	}
	
	/**
	 * Get QR code as base64 for display
	 */
	public function getBarcodeBase64($patrol_id)
	{
		$patrol = $this->patrolModel->find($patrol_id);
		if (!$patrol) {
			echo json_encode(['status' => 'error', 'message' => 'Patrol point not found']);
			return;
		}
		
		$qrGenerator = new SimpleQRCode();
		$base64 = $qrGenerator->generateQRCodeBase64($patrol['barcode']);
		
		echo json_encode(['status' => 'ok', 'barcode_image' => $base64]);
	}
}

