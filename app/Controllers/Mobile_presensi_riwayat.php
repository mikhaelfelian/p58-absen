<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\MobilePresensiRiwayatModel;

class Mobile_presensi_riwayat extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/mobile-presensi-riwayat.js');
	
		$this->model = new MobilePresensiRiwayatModel;
		$this->data['title'] = 'Riwayat Presensi';
	}
	
	public function index() 
	{
		if (!empty($_GET['periode'])) {
			$exp = explode(' s.d. ', $_GET['periode']);
			$start_date = $exp[0];
			$end_date = $exp[1];
		} else {
			$start_date = date('d-m-Y', strtotime('-10 days'));
			$end_date = date('d-m-Y');
		}
			
		$exp = explode('-', $start_date);
		$start_date_db = $exp[2] . '-' . substr('0' . $exp[1], -2) . '-' . $exp[0];
		
		$exp = explode('-', $end_date);
		$end_date_db = $exp[2] . '-' . substr('0' . $exp[1], -2) . '-' . $exp[0];
		
		$query_result = $this->model->getRiwayatPresensi($start_date_db, $end_date_db);
		$riwayat_presensi = [];
		foreach ($query_result as $val) {
			$riwayat_presensi[$val['tanggal']]['masuk'] = $val;
			$riwayat_presensi[$val['tanggal']]['pulang'] = $val;
		}
		
		$this->data['start_date'] = $start_date;
		$this->data['end_date'] = $end_date;
		$this->data['start_date_db'] = $start_date_db;
		$this->data['end_date_db'] = $end_date_db;
		$this->data['riwayat_presensi'] = $riwayat_presensi;
		
		echo view('themes/modern/mobile-presensi-riwayat-result', $this->data);
	}
}
