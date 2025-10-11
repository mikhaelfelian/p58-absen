<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\PresensiRekapModel;
use App\Libraries\JWDPDF;

require ROOTPATH . 'app/ThirdParty/PhpSpreadsheet/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Presensi_rekap extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new PresensiRekapModel;	
		$this->data['title'] = 'Rekap Presensi';
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/moment/moment.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.js');
		$this->addStyle ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.css');
		$this->addJs ( $this->config->baseURL . 'public/vendors/filesaver/FileSaver.js');
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/bootstrap-5-theme/select2-bootstrap-5-theme.min.css' );
		
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/presensi-rekap.js');
	}
	
	public function index() {

		$result = $this->model->getAllUser();
		if (has_permission('read_own')) {
			$user = [];
		} else {
			$user = ['' => 'Semua'];
		}
		
		foreach ($result as $val) {
			$user[$val['id_user']] = $val['nama'];
		}

		$this->data['user'] = $user;
		
		$this->data['setting_presensi'] = $this->getSetting('presensi');
		$presensi = [];
		if (!empty($_GET['tahun'])) {
			
			$result = $this->model->getPresensiByMonth($_GET['bulan'], $_GET['tahun']);
			foreach ($result as $val) {
				$presensi[$val['id_user']][$val['day']] = $val['status'];
			}
		}
		$this->data['presensi'] = $presensi;
		
		$start_date = '01-01-' . date('Y');
		$end_date = date('d-m-Y');
		
		$exp = explode('-', $start_date);
		$start_date_db = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
		
		$exp = explode('-', $end_date);
		$end_date_db = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
		
		$this->view('presensi-rekap.php', $this->data);
	}
	
	public function generateExcel($bulan, $tahun, $output) 
	{
		$excel = new Spreadsheet();
		$setting = $this->getSetting('presensi');
		$hari_kerja = json_decode($setting['hari_kerja'], true);
				
		$result = $this->model->getAllUser();
		foreach ($result as $val) {
			$user[$val['id_user']] = $val['nama'];
		}
		
		// Set document properties
		$excel->getProperties()->setCreator('Jagowebdev.com')
			->setLastModifiedBy('Jagowebdev.com')
			->setTitle('Rekap Presensi')
			->setSubject('Rekap Presensi')
			->setDescription('Rekap Presensi')
			->setKeywords('Rekap Presensi')
			->setCategory('Rekap Presensi');
		
		$excel->setActiveSheetIndex(0);
		$sheet = $excel->getActiveSheet()->setTitle('PRESENSI');
		$sheet->mergeCells('A1:A2');
		$sheet->mergeCells('B1:B2');
				
		$num_day = date('t', strtotime(date($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . '01')));
		
		$last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $num_day);
		$sheet->mergeCells('C1:' . $last_col . '1');
					
		$sheet->getColumnDimension('A')->setWidth(4);
		$sheet->getColumnDimension('B')->setWidth(25);
		
		$nama_bulan = nama_bulan(true);
		$sheet->setCellValue('A1', 'No');
		$sheet->setCellValue('B1', 'Nama');
		$sheet->setCellValue('C1', $nama_bulan[$_GET['bulan']] . ' ' . $_GET['tahun']);
		
		for ($i = 1; $i <= $num_day; $i++) {
			$col_name = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $i);
			$sheet->getColumnDimension($col_name)->setWidth(4.7);
			$day = date('w', strtotime($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . substr('0'.$i, -2)));
			if (!in_array($day, $hari_kerja)) {
				$sheet
					->getStyle($col_name . '2')
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB('e2e3e5');
			}
			$sheet->setCellValue($col_name . '2', $i);
		}
	
		$result = $this->model->getPresensiByMonth($bulan, $tahun);
		
		foreach ($result as $val) {
			$presensi[$val['id_user']][$val['day']] = $val['status'];
		}
		
		$no = 1;
		$row = 3;
		foreach ($presensi as $id_user => $absen_user) {
			$sheet->setCellValue('A' . $row, $no);
			$sheet->setCellValue('B' . $row, $user[$id_user]);
			for ($i = 1; $i <= $num_day; $i++) 
			{
				$col_name = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + $i);
				$day = date('w', strtotime($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . substr('0'.$i, -2)));
				$text = '';
				if (in_array($day, $hari_kerja)) {
					
					if (key_exists($i, $absen_user)) {									
						switch ($absen_user[$i]) 
						{
							case 'tam':
								$bgcolor = 'f8d7da';
								$text = 'TAM';
								break;
							case 'tam_psw':
								$bgcolor = 'f8d7da';
								$text = 'TAM,PSW';
								break;
							case 'tap':
								$bgcolor = 'f8d7da';
								$text = 'TAP';
								break;
							case 'tap_psw':
								$bgcolor = 'f8d7da';
								$text = 'TAP,PSW';
								break;
							case 'tam_tap':
								$bgcolor = 'f8d7da';
								$text = 'TAM,TAP';
								break;
							case 'tw':
								$bgcolor = 'd1e7dd';
								$text = 'v';
								break;
							case 'tl':
								$bgcolor = 'fff3cd';
								$text = 'TL';
								break;
							case 'psw':
								$bgcolor = 'fff3cd';
								$text = 'PSW';
								break;
							case 'tl_psw':
								$bgcolor = 'fff3cd';
								$text = 'TL,PSW';
								break;
						}
					} else {
						$bgcolor = 'f8d7da';
						$text = 'TA';
					}
					
					
				} else {
					$bgcolor = 'e2e3e5';
				}
				
				if (strpos($text,',')) {
					$sheet->getColumnDimension($col_name)->setWidth(8.4);
				}
				$sheet
					->getStyle($col_name . $row)
					->getFill()
					->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
					->getStartColor()
					->setARGB($bgcolor);
					
				$sheet->setCellValue($col_name . $row, $text);
				
			}
			$no++;
			$row++;
		}
		
		$sheet->getStyle('A1:' . $last_col . $row)
				->getAlignment()
				->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		
		$sheet->getStyle('B3:' . 'B' . $row)
				->getAlignment()
				->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
				
		$sheet->getStyle('A1:' . $last_col . $row)
				->getAlignment()
				->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER );
		
		$styleArray = [
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
					'color' => ['argb' => '000000'],
				],
			],
		];
		
		$row--;
		$sheet->getStyle('A1:' . $last_col . $row)->applyFromArray($styleArray);
		
		$sheet->setCellValue('A' . ++$row, '*)Keterangan: V: Tepat waktu, TL: Terlambat masuk, PSW: Pulang sebelum waktunya, TAM: Tidak absen masuk, TAP: Tidak absen pulang');
		$sheet->getStyle('A' . $row)->getAlignment()
				->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
		
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Laporan Kas.xlsx"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');
		
		$writer = new Xlsx($excel);
		$writer->save('php://output');
	}
	
	public function ajaxExportExcel() 
	{
		$output = '';
		if (@$_GET['ajax'] == 'true') {
			$output = 'raw';
		}
		$this->generateExcel($_GET['bulan'], $_GET['tahun'], $output); 
	}
}
