<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\SettingWaktuPresensiModel;

class Setting_waktu_presensi extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new SettingWaktuPresensiModel;	
		$this->data['site_title'] = 'Setting Waktu Presensi';
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/bootstrap-5-theme/select2-bootstrap-5-theme.min.css' );
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/setting-waktu-presensi.js');
	}
	
	public function index()
	{
		$this->hasPermissionPrefix('read');	
		$this->view('setting-waktu-presensi-result.php', $this->data);
	}
	
	public function add()
	{
		$this->data['setting_presensi'] = [];
		
		if (!empty($_POST['submit'])) {
			$this->data['message'] = $this->model->saveData();
			$this->data['setting_presensi'] = $this->model->getSettingWaktuPresensiById($this->data['message']['id']);
			$this->data['id'] = $this->data['message']['id'];
		}
		
		$this->data['title'] = 'Tambah Data Penjualan';
		$this->data['breadcrumb']['Add'] = '';
		$this->view('setting-waktu-presensi-form.php', $this->data);
	}
	
	public function edit()
	{
		$error = [];
		
		if (empty($_GET['id'])) {
			$error = ['status' => 'error', 'message' => 'Data tidak ditemukan'];
		} else {
			$this->data['setting_presensi'] = $this->model->getSettingWaktuPresensiById($_GET['id']);
			if ($this->data['setting_presensi']) {
				if (!has_permission('update_all')) {
					$error = ['status' => 'error', 'message' => 'Anda tidak berhak mengubah data ini'];
				}
			} else {
				$error = ['status' => 'error', 'message' => 'Data tidak ditemukan'];
			}
		}
		
		if (!empty($_POST['submit'])) {
			$this->data['message'] = $this->model->saveData();
			$this->data['setting_presensi'] = $this->model->getSettingWaktuPresensiById($_GET['id']);
		}
		
		$this->data['title'] = 'Edit Waktu Presensi';
		$this->data['id'] = $_GET['id'];
		$this->data['breadcrumb']['Edit'] = '';		
		$this->view('setting-waktu-presensi-form.php', $this->data);
	}
	
	/* public function ajaxSaveData() 
	{
		$error = false;
		if (empty($_POST['id'])) {
			if (!user_can('create')) {
				$error = true;
				$result = ['status' => 'error', 'message' => 'Anda tidak diperkenankan menambah data penjualan'];
			}
		} else {
			$data = $this->model->getPenjualanById($_POST['id']);
			if ($data) {
				if (!user_can('update', $data['id_user_petugas'], 'penjualan')) {
					$error = true;
					$result = ['status' => 'error', 'message' => 'Anda tidak berhak mengubah data ini'];
				}
			}
		}
		
		if (!$error) {
			$result = $this->model->saveData();
		}
		
		echo json_encode($result);
	} */
	
	public function ajaxDeleteData() {
		if (empty($_POST['id'])) {
			$result = ['status' => 'error', 'message' => 'Invalid input'];
		} else {
			$data = $this->model->getSettingWaktuPresensiById($_POST['id']);
			if ($data) {
				if (user_can('delete', $data['id_setting_waktu_presensi'], 'setting-waktu-presensi')) {
					$delete = $this->model->deleteData($_POST['id']);
					// $delete = true;
					if ($delete) {
						$result =  ['status' => 'ok', 'message' => 'Data berhasil dihapus'];
					} else {
						$result = ['status' => 'error', 'message' => 'Data gagal dihapus'];
					}
				} else {
					$result = ['status' => 'error', 'message' => 'Anda tidak berhak menghapus data ini'];
				}
			} else {
				$result = ['status' => 'error', 'message' => 'Data tidak ditemukan'];
			}
		}
		
		echo json_encode($result);
	}
	
	public function ajaxSwitchDefault() {
		$update = $this->model->switchDefault($_POST['id']);
		if ($update) {
			$result = ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
		} else {
			$result = ['status' => 'error', 'message' => 'Data gagal disimpan'];
		}
		echo json_encode($result);
	}
	
	public function getDataDT() {
		
		$this->hasPermissionPrefix('read');
		
		$num_data = $this->model->countAllData();
		$result['draw'] = $start = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = $num_data;
		
		$query = $this->model->getListData();
		$result['recordsFiltered'] = $query['total_filtered'];
				
		helper('html');
		$id_user = $this->session->get('user')['id_user'];
		
		$no = $this->request->getPost('start') + 1 ?: 1;
		foreach ($query['data'] as $key => &$val) 
		{
			$val['ignore_urut'] = $no;
			$val['ignore_batas_presensi_masuk'] = '<div class="text-end">' . $val['waktu_masuk_awal'] . ' s.d. ' . $val['waktu_masuk_akhir'] . '</div>';
			$val['ignore_batas_presensi_pulang'] = '<div class="text-end">' . $val['waktu_pulang_awal'] . ' s.d. ' . $val['waktu_pulang_akhir'] . '</div>';
			$val['ignore_action'] = '<div class="btn-action-group">' . 
				btn_link(['url' => base_url() . '/setting-waktu-presensi/edit?id=' . $val['id_setting_waktu_presensi'],'label' => '', 'icon' => 'fas fa-edit', 'attr' => ['class' => 'btn btn-success btn-xs me-1', 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Edit Data'] ]) . 
				btn_label(['label' => '', 'icon' => 'fas fa-times', 'attr' => ['class' => 'btn btn-danger btn-xs btn-delete', 'data-id' => $val['id_setting_waktu_presensi'], 'data-delete-message' => 'Hapus data setting presensi ?', 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Delete Data'] ]) . 
			'</div>';
			
			$val['batas_waktu_masuk'] = '<div class="text-end">' . $val['batas_waktu_masuk'] . '</div>';
			$val['batas_waktu_pulang'] = '<div class="text-end">' . $val['batas_waktu_pulang'] . '</div>';
			$val['waktu_pulang_awal'] = '<div class="text-end">' . $val['waktu_pulang_awal'] . '</div>';
			$checked = $val['gunakan'] == 'Y' ? 'checked' : '';
			$val['ignore_switch'] = '<div class="d-flex justify-content-end"><div class="form-check form-switch">
										  <input class="form-check-input switch-aktif" data-id="' . $val['id_setting_waktu_presensi'] . '" type="checkbox" ' . $checked . ' id="check-'. $val['id_setting_waktu_presensi'].'">
									</div></div>';
			$no++;
		}
					
		$result['data'] = $query['data'];
		echo json_encode($result); exit();
	}
}
