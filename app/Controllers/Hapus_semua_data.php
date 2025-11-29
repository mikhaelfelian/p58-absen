<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\HapusSemuaDataModel;

class Hapus_semua_data extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();

		$this->model = new HapusSemuaDataModel;
		$this->data['list_table'] = $this->model->getListTable();
		$this->data['site_title'] = 'Hapus Semua Data';
		
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/hapus-semua-data.js');
	}
	
	public function index() 
	{
		$this->data['title'] = 'Hapus Semua Data';
		$this->data['nama_database'] = $this->model->getDbName();
		$this->view('hapus-semua-data-form', $this->data);
	}
	
	public function ajaxDeleteAllData() {
		if (empty($_POST['submit'])) {
			$result = ['status' => 'Error', 'message' => 'Invalid input'];
		} else {
			$result = $this->model->deleteAllData();
		}
		echo json_encode($result);
	}
}