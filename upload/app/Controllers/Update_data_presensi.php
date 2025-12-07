<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\UpdateDataPresensiModel;

class Update_data_presensi extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/moment/moment.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.js');
		$this->addStyle ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.css');
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/update-data-presensi.js');
		
		$this->model = new UpdateDataPresensiModel;
		$this->data['site_title'] = 'Update Data Presensi';
	}
	
	public function index()
	{
		$this->hasPermissionPrefix('read');
		
		$tanggal_mulai_presensi = $this->model->getTanggalMulaiPresensi();
		if (!$tanggal_mulai_presensi) {
			$this->printError('Data presensi masih kosong');
			return;
		}
		
		$setting = $this->getSetting('presensi');
		// echo '<pre>';
		// print_r($setting); die;
		if ($setting['last_update_data_presensi']) {
			$tanggal_mulai_db = $setting['last_update_data_presensi'];
		} else {
			$tanggal_mulai_db = $tanggal_mulai_presensi;
		}
		
		list($y, $m, $d) = explode('-', $tanggal_mulai_db);
		$tanggal_mulai = $d . '-' . $m . '-' . $y;
		$this->data['tanggal_mulai_db'] = $tanggal_mulai_db;
		$this->data['tanggal_mulai'] = $tanggal_mulai;
		
		if (!empty($_POST['submit'])) {
			$jml_insert = $this->model->updateDataPresensi();
			if ($jml_insert == 0) {
				$message = 'Proses pengecekan selesai, tidak ada data presensi yang ditambahkan';
			} else {
				$message = 'Data presensi berhasil ditambahkan sebanyak ' . format_number($jml_insert) . ' data';
			}
			$this->data['message'] = ['status' => 'ok' , 'message' => $message];
		}
		$this->view('update-data-presensi.php', $this->data);
	}
}
