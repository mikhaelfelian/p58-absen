<?php
/**
*	App Name	: Antrian	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\AktivasiModel;

class Aktivasi extends \App\Controllers\BaseController
{	
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new AktivasiModel;	
		$this->data['site_title'] = 'Aktivasi Aplikasi';
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/bootstrap-5-theme/select2-bootstrap-5-theme.min.css' );
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/aktivasi.js');
		
		$this->cryptTools = new \CryptTools;
	}
	
	public function index()
	{
		$this->hasPermissionPrefix('read');
		
		$activation_message = $this->session->get('activation');
		if (!empty($activation_message)) {
			if (key_exists('message', $activation_message)) {
				if ($activation_message['message']) {
					$this->data['message'] = $activation_message['message'];
				}
			}
		}
		
		if (!empty($_POST['submit'])) {
			$error = $this->validateForm();
			if ($error) {
				$this->data['message'] = ['status' => 'error', 'message' => $error];
			} else {
				$this->data['message'] = $this->registerProduk();
			}
		}
	
		$setting = $this->getSetting('aktivasi');
		$this->data['aktivasi'] = [];
		if (!empty($setting['activation_key'])) {
			$this->data['aktivasi'] = json_decode($this->cryptTools->decrypt($setting['activation_key']), true);
			if ($this->data['aktivasi']) {
				$this->data['message'] = ['status' => 'ok', 'message' => 'Produk berhasil diaktivasi'];
				$this->data['aktivasi']['activation_key'] = $setting['activation_key'];
			} else {
				if (empty($this->data['message'])) {
					$this->data['message'] = ['status' => 'error', 'message' => 'Activation key tidak valid'];
				}
			}
		}
		
		$this->data['is_domain_lokal'] = $this->isDomainLokal();
		$this->data['title'] = 'Aktivasi Produk';
		$this->view('aktivasi-form.php', $this->data);
	}
	
	public function ajaxDeleteAktivasi() 
	{
		$delete = $this->model->deleteAktivasi();
		if ($delete) {
			$result = ['status' => 'ok', 'message' => 'Data aktivasi berhasil dihapus.'];
		} else {
			$result = ['status' => 'error', 'message' => 'Data aktivasi gagal dihapus.'];
		}
		echo json_encode($result);
	}
	
	private function validateForm() {
	
		$validation =  \Config\Services::validation();
		$validation->setRule('email', 'Email', 'trim|required|valid_email');
		$validation->setRule('serial_number', 'Serial Number', 'trim|required|min_length[5]');
		$validation->withRequest($this->request)->run();
		$form_errors = $validation->getErrors();
		return $form_errors;
	}
}