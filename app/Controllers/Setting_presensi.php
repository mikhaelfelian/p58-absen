<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\SettingPresensiModel;

class Setting_presensi extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new SettingPresensiModel;	
		$this->data['title'] = 'Setting Presensi';
		$this->addJs ( $this->config->baseURL . 'public/vendors/leafletjs/leaflet.js');
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/setting-presensi.js');
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/bootstrap-5-theme/select2-bootstrap-5-theme.min.css' );
		
		$this->addStyle ( $this->config->baseURL . 'public/vendors/leafletjs/leaflet.css');
	}
	
	public function index() {
		
		if (!empty($_POST['submit'])) {
			$error = $this->validateFormSetting();
			if ($error) {
				$this->data['message'] = ['status' => 'error', 'message' => $error];
			} else {
				$message = $this->model->saveSetting();
				$this->data['message'] = $message;
			}
		}
		
		$setting = $this->model->getSetting('presensi');
		$setting_presensi = [];
		foreach ($setting as $val) {
			$setting_presensi[$val['param']] = $val['value'];
		}
		
		$this->data['setting_presensi'] = $setting_presensi;
		$this->view('setting-presensi-form.php', $this->data);
	}
	
	private function validateFormSetting() {
	
		$validation =  \Config\Services::validation();
		$validation->setRule('gunakan_foto_selfi', 'Gunakan Foto Selfi', 'trim|required');
		$validation->withRequest($this->request)->run();
		$form_errors = $validation->getErrors();
		
		return $form_errors;
	}
}
