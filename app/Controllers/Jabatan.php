<?php
/**
*	App Name	: Aplikasi Siswa dan Pembayaran SPP Sekolah	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2023-2023
*/

namespace App\Controllers;
use App\Models\JabatanModel;

class Jabatan extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new JabatanModel;	
		$this->data['site_title'] = 'Jabatan';
		$this->addStyle( $this->config->baseURL . 'public/themes/modern/css/jabatan.css');
		$this->addJs($this->config->baseURL . 'public/vendors/dragula/dragula.min.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/dragula/dragula.min.css');
		$this->addStyle($this->config->baseURL . 'public/themes/modern/js/jabatan.css');
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/jabatan.js');
	}
	
	public function index()
	{
		$this->hasPermissionPrefix('read');
		
		$this->data['list_jabatan'] = $this->model->getListJabatan();
		$this->data['jml_jabatan'] = $this->model->getJmlJabatan();
		$this->view('jabatan-result-container.php', $this->data);
	}
	
	public function ajaxGetListJabatan() {
		$this->data['list_jabatan'] = $this->model->getListJabatan();
		$this->data['jml_jabatan'] = $this->model->getJmlJabatan();
		echo view('themes/modern/jabatan-result.php', $this->data);
	}
	
	public function ajaxDeleteData() {

		$result = $this->model->deleteData();
		// $result = true;
		if ($result) {
			$result = ['status' => 'ok', 'message' => 'Data jabatan berhasil dihapus'];
		} else {
			$result = ['status' => 'error', 'message' => 'Data jabatan gagal dihapus'];
		}
		
		echo json_encode($result);
	}
	
	public function ajaxUpdateUrut() {
		$result = $this->model->updateUrut();
		if ($result) {
			$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
		} else {
			$result = ['status' => 'error', 'message' => 'Data gagal disimpan'];
		}
		
		echo json_encode($result);
	}
	
	public function ajaxDeleteAllJabatan() {

		$result = $this->model->deleteAllJabatan();
		// $result = true;
		if ($result) {
			$result = ['status' => 'ok', 'message' => 'Data jabatan berhasil dihapus'];
		} else {
			$result = ['status' => 'error', 'message' => 'Data jabatan gagal dihapus'];
		}
		
		echo json_encode($result);
	}
	
	public function ajaxGetFormData() {
		
		if (isset($_GET['id'])) {
			if ($_GET['id']) {
				$this->data['jabatan'] = $this->model->getJabatanById($_GET['id']);
				if (!$this->data['jabatan'])
					$this->errorDataNotFound();
			}
		}

		echo view('themes/modern/jabatan-form.php', $this->data);
	}
	
	public function ajaxUpdateData() {

		$message = $this->model->saveData();
		echo json_encode($message);
	}
	
	public function getDataDT() {
		
		$this->hasPermissionPrefix('read');
		
		$num_data = $this->model->countAllData( $this->whereOwn() );
		$result['draw'] = $start = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = $num_data;
		
		$query = $this->model->getListData( $this->whereOwn() );
		$result['recordsFiltered'] = $query['total_filtered'];
				
		helper('html');
		
		$no = $this->request->getPost('start') + 1 ?: 1;
		foreach ($query['data'] as $key => &$val) 
		{
			$val['ignore_urut'] = $no;
			$val['ignore_action'] = '<div class="form-inline btn-action-group">'
										. btn_label(
												['icon' => 'fas fa-edit'
													, 'attr' => ['class' => 'btn btn-success btn-edit btn-xs me-1', 'data-id' => $val['id_jabatan']]
													, 'label' => 'Edit'
												])
										. btn_label(
												['icon' => 'fas fa-times'
													, 'attr' => ['class' => 'btn btn-danger btn-delete btn-xs'
																	, 'data-id' => $val['id_jabatan']
																	, 'data-delete-title' => 'Hapus data jabatan: <strong>' . $val['nama_jabatan'] . '</strong>'
																]
													, 'label' => 'Delete'
												]) . 
										
										'</div>';
			$no++;
		}
					
		$result['data'] = $query['data'];
		echo json_encode($result); exit();
	}
	
}
