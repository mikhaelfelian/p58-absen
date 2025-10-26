<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\UserCompanyModel;
use App\Models\CompanyModel;
use App\Models\Builtin\UserModel;

class User_company extends BaseController
{
	public function __construct() {
		parent::__construct();
		$this->model = new UserCompanyModel;
		$this->data['title'] = 'Assign Company ke User';
		
		$this->addJs($this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css');
		$this->addJs($this->config->baseURL . 'public/vendors/flatpickr/dist/flatpickr.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/flatpickr/dist/flatpickr.min.css');
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/user-company.js');
	}
	
	public function index() {
		$this->hasPermission('read_all');
		
		$this->view('user-company-list.php', $this->data);
	}
	
	public function add() {
		$this->hasPermission('create');
		
		// Get all users
		$sql = 'SELECT id_user, nama, nip FROM user ORDER BY nama';
		$this->data['users'] = $this->model->db->query($sql)->getResult();
		
		// Get active companies
		$companyModel = new CompanyModel();
		$this->data['companies'] = $companyModel->getActiveCompanies();
		
		$this->data['mode'] = 'add';
		$this->data['form_errors'] = $this->data['form_errors'] ?? [];
		
		// Get flash message
		if (session()->has('message')) {
			$this->data['message'] = session()->get('message');
		}
		
		$this->view('user-company-form.php', $this->data);
	}
	
	public function edit() {
		$this->hasPermission('update_all');
		
		$id = $this->request->getGet('id');
		$assignment = $this->model->find($id);
		
		if (!$assignment) {
			$this->errorDataNotFound();
			return;
		}
		
		// Get all users
		$sql = 'SELECT id_user, nama, nip FROM user ORDER BY nama';
		$this->data['users'] = $this->model->db->query($sql)->getResult();
		
		// Get active companies
		$companyModel = new CompanyModel();
		$this->data['companies'] = $companyModel->getActiveCompanies();
		
		$this->data['mode'] = 'edit';
		$this->data['assignment'] = $assignment;
		$this->data['form_errors'] = $this->data['form_errors'] ?? [];
		
		// Get flash message
		if (session()->has('message')) {
			$this->data['message'] = session()->get('message');
		}
		
		$this->view('user-company-form.php', $this->data);
	}
	
	public function store() 
	{
		$error = $this->validateForm();

		if ($error) {
			$this->data['message']     = ['status' => 'error', 'message' => $error];
			$this->data['form_errors'] = $error;

			// Return to form with errors
			if (!empty($_POST['id'])) {
				return redirect()->to($this->moduleURL . '/edit?id=' . $_POST['id']);
			}
			return redirect()->to($this->moduleURL . '/add');
		}

		// Prepare data
		$request           = $this->request;
		$idUser            = $request->getPost('id_user');
		$idCompany         = $request->getPost('id_company');
		$tanggalMulai      = $request->getPost('tanggal_mulai');
		$tanggalSelesai    = $request->getPost('tanggal_selesai');
		$status            = $request->getPost('status') ?? 'active';
		$isPatrolRequired  = $request->getPost('isPatrolRequired', FILTER_DEFAULT, FILTER_NULL_ON_FAILURE) ?? 0;
		$keterangan        = $request->getPost('keterangan');

		$data = [
			'id_user'          => $idUser,
			'id_company'       => $idCompany,
			'tanggal_mulai'    => !empty($tanggalMulai) ? $tanggalMulai : null,
			'tanggal_selesai'  => !empty($tanggalSelesai) ? $tanggalSelesai : null,
			'status'           => $status,
			'isPatrolRequired' => $isPatrolRequired,
			'keterangan'       => $keterangan,
		];

		// Add primary key for update
		if (!empty($_POST['id'])) {
			$data['id_user_company'] = $_POST['id'];
		}

		// Save using Model->save() method
		$result = $this->model->saveData($data);

		if ($result['status'] == 'ok') {
			// Redirect back to edit page with success message
			if (!empty($_POST['id'])) {
				return redirect()->to($this->moduleURL . '/edit?id=' . $_POST['id'])
					->with('message', $result);
			}
			return redirect()->to($this->moduleURL . '/edit?id=' . $result['id'])
				->with('message', $result);
		}

		return redirect()->back()->with('message', $result);
	}
	
	private function validateForm() {
		$validation = \Config\Services::validation();
		$validation->setRule('id_user', 'User', 'trim|required');
		$validation->setRule('id_company', 'Company', 'trim|required');
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
	
	public function ajaxGetCompanyByUser() {
		$id_user = $this->request->getGet('id_user');
		$companies = $this->model->getCompanyByUser($id_user);
		echo json_encode(['status' => 'ok', 'data' => $companies]);
	}
	
	public function getDataDT() {
		$this->hasPermission('read_all');
		
		// Get all user-company assignments
		$sql = 'SELECT user_company.*, user.nama, user.nip, company.nama_company
				FROM user_company
				LEFT JOIN user USING(id_user)
				LEFT JOIN company USING(id_company)
				ORDER BY user.nama, company.nama_company';
		$data = $this->model->db->query($sql)->getResult();
		
		$result['draw'] = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = count($data);
		$result['recordsFiltered'] = count($data);
		
		helper('html');
		
		$no = 1;
		foreach ($data as $key => &$val) {
			$val->ignore_urut = $no;
			$val->ignore_id = $val->id_user_company;  // Add ID for DataTable
			
			// Format dates
			$val->tanggal_mulai = $val->tanggal_mulai ? date('d-m-Y', strtotime($val->tanggal_mulai)) : '-';
			$val->tanggal_selesai = $val->tanggal_selesai ? date('d-m-Y', strtotime($val->tanggal_selesai)) : '-';
			
			// Status badge
			if ($val->status == 'active') {
				$val->status = '<span class="badge bg-success">Active</span>';
			} elseif ($val->status == 'completed') {
				$val->status = '<span class="badge bg-info">Completed</span>';
			} else {
				$val->status = '<span class="badge bg-secondary">Inactive</span>';
			}
			
			// Actions
			$val->ignore_action = '<div class="btn-action-group">';
			
			if (has_permission('update_all')) {
				$val->ignore_action .= btn_link([
					'url' => $this->moduleURL . '/edit?id=' . $val->id_user_company,
					'attr' => ['class' => 'btn btn-success btn-xs me-1',
							   'data-bs-toggle' => 'tooltip', 
							   'data-bs-title' => 'Edit Data'],
					'icon' => 'fas fa-edit',
					'label' => 'Edit'
				]);
			}
			
			if (has_permission('delete_all')) {
				$val->ignore_action .= btn_label([
					'label' => 'Delete',
					'icon' => 'fas fa-times',
					'attr' => ['class' => 'btn btn-danger btn-xs btn-delete',
							   'data-id' => $val->id_user_company,
							   'data-bs-toggle' => 'tooltip', 
							   'data-bs-title' => 'Delete Data']
				]);
			}
			
			$val->ignore_action .= '</div>';
			
			$no++;
		}
		
		$result['data'] = $data;
		echo json_encode($result);
	}
}

