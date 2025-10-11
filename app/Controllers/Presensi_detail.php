<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\PresensiDetailModel;
use App\Libraries\JWDPDF;

class Presensi_detail extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new PresensiDetailModel;	
		$this->data['title'] = 'Detail Presensi';
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/moment/moment.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.js');
		$this->addStyle ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.css');
		$this->addJs ( $this->config->baseURL . 'public/vendors/filesaver/FileSaver.js');
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/glightbox/css/glightbox.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/bootstrap-5-theme/select2-bootstrap-5-theme.min.css' );
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/webcamjs/webcam.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/glightbox/js/glightbox.min.js');
		
		$this->addJs($this->config->baseURL . 'public/vendors/flatpickr/dist/flatpickr.min.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/flatpickr/dist/flatpickr.min.css');
		
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/presensi-detail.js');
	}
	
	public function index() {
		$start_date = '01-01-' . date('Y');
		$end_date = date('d-m-Y');
		
		$exp = explode('-', $start_date);
		$start_date_db = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
		
		$exp = explode('-', $end_date);
		$end_date_db = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
		
		$this->setData();
		$this->data['start_date'] = $start_date;
		$this->data['end_date'] = $end_date;
		$this->data['start_date_db'] = $start_date_db;
		$this->data['end_date_db'] = $end_date_db;
		$this->view('presensi-detail-result.php', $this->data);
	}
	
	public function add()
	{
		$this->setData();
		$this->data['title'] = 'Tambah Data Presensi';
		$this->data['breadcrumb']['Add'] = '';
		$this->data['message'] = [];
		$this->data['presensi'] = [];
		
		if (!empty($_POST['submit'])) {
			$this->data['message'] = $this->model->saveData(@$_GET['id']);
			if ($this->data['message']['status'] == 'ok') {
				$this->data['presensi'] = $this->model->getUserPresensiById($this->data['message']['id']);
			}
		}
		
		$this->view('presensi-detail-form.php', $this->data);
	}
	
	public function edit()
	{
		$error = [];
		
		if (empty($_GET['id'])) {
			$error = ['status' => 'error', 'message' => 'Data tidak ditemukan'];
		} else {
			$this->data['presensi'] = $this->model->getUserPresensiById($_GET['id']);
			if ($this->data['presensi']) {
				if (!has_permission('update_all')) {
					
					$error = ['status' => 'error', 'message' => 'Anda tidak berhak mengubah data ini'];
				}
			} else {
				$error = ['status' => 'error', 'message' => 'Data tidak ditemukan'];
			}
		}
		
		if ($error) {
			$this->data['message'] = $error;
		} else {
			if (!empty($_POST['submit'])) {
				$this->data['message'] = $this->model->saveData($_GET['id']);
				$this->data['presensi'] = $this->model->getUserPresensiById($this->data['message']['id']);
			}
			$this->setData();
		}
		
		$this->data['title'] = 'Edit Presensi';
		$this->data['breadcrumb']['Edit'] = '';

		$this->view('presensi-detail-form.php', $this->data);
	}
	
	public function setData() 
	{
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
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/leafletjs/leaflet.js');
		$this->addStyle ( $this->config->baseURL . 'public/vendors/leafletjs/leaflet.css');
		
		$this->data['setting_presensi'] = $this->getSetting('presensi');
	}
	
	public function ajaxDeleteData() {
		if (empty($_POST['id'])) {
			$result = ['status' => 'error', 'message' => 'Parameter invalid'];
		} else {
			$delete = $this->model->deleteData($_POST['id']);
			if ($delete) {
				$result = ['status' => 'ok', 'message' => 'Data berhasil dihapus'];
			} else {
				$result = ['status' => 'error', 'message' => 'Data gagal dihapus'];
			}
		}
		echo json_encode($result);
	}
	
	public function generatePdf($start_date, $end_date, $output) 
	{
		$presensi = $this->model->getUserPresensiByDate($start_date, $end_date);
		
		// $identitas = $this->model->getIdentitas();
		$pdf = new JWDPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
		$pdf->setFooterText('Detail presensi ' . format_date($start_date) . ' s.d. ' . format_date($end_date));
		
		$pdf->setPageUnit('mm');

		// set document information
		$pdf->SetCreator('Jagowebdev');
		$pdf->SetAuthor('Agus Prawoto Hadi');
		$pdf->SetTitle('Detail Presensi Periode ' . $start_date . ' s.d. ' . $end_date);
		$pdf->SetSubject('Detail Presensi');
		
		// Margin Header
		$pdf->SetMargins(10, 0, 10);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$pdf->startDate = $start_date;
		$pdf->endDate = $end_date;
		$pdf->SetPrintHeader(true);
		$pdf->SetPrintFooter(true);
		
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
		$pdf->SetProtection(array('modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'), '', null, 0, null);

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		$margin_left = 10; //mm
		$margin_right = 10; //mm
		$margin_top = 30; //mm
		$font_size = 10;
		
		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont('dejavusans', '', $font_size + 4, '', true);
		// Margin Content
		$pdf->SetMargins($margin_left, $margin_top, $margin_right, false);

		$pdf->AddPage();
		
		// $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 0, 0)));
		$pdf->SetTextColor(50,50,50);
		$pdf->SetFont ('helvetica', 'B', $font_size + 4, '', 'default', true );
		$pdf->Cell(0, 0, 'Presensi Pegawai', 0, 1, 'C', 0, '', 0, false, 'T', 'M' );
		$pdf->SetFont ('helvetica', 'B', $font_size + 2, '', 'default', true );
		$pdf->Cell(0, 0, 'Periode: ' . format_date($start_date) . ' s.d. ' . format_date($end_date), 0, 1, 'C', 0, '', 0, false, 'T', 'M' );
		
		$pdf->SetFont ('helvetica', '', $font_size, '', 'default', true );

		$pdf->ln(8);
		$pdf->SetFont ('helvetica', '', $font_size, '', 'default', true );
		$border_color = '#CECECE';
		$background_color = '#efeff0';
		$tbl = <<<EOD
		<table border="0" cellspacing="0" cellpadding="6">
			<thead>
				<tr border="1" style="background-color:$background_color">
					<th style="width:5%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">No</th>
					<th style="width:25%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">Nama Pegawai</th>
					<th style="width:22%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Tanggal</th>
					<th style="width:13%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Waktu Presensi</th>
					<th style="width:15%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Jenis Presensi</th>
					<th style="width:15%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Status</th>
				</tr>
			</thead>
			<tbody>
		EOD;

			$no = 1;
			$format_date = 'format_date';
			$total = 0;
			foreach ($presensi as $val) {
				list($y, $m, $d) = explode('-', $val['tanggal']);
				$tanggal = $d . '-' . $m . '-' . $y;
				$tbl .= <<<EOD
					<tr>
						<td style="width:5%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">$no</td>
						<td style="width:25%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color">$val[nama]</td>
						<td style="width:22%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="right">$tanggal</td>
						<td style="width:13%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="right">$val[waktu]</td>
						<td style="width:15%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$val[jenis_presensi]</td>
						<td style="width:15%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$val[status]</td>
					</tr>
					EOD;
				$no++;
			}
		$tbl .= '</tbody></table>';
		
		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$filename = 'Presensi Pegawai - ' . format_date($start_date) . '_' . format_date($end_date) . '.pdf';
		$filepath = ROOTPATH . 'public/tmp/detail_presensi_' . time() . '.pdf.tmp';
		
		switch ($output) {
			case 'raw':
				$pdf->Output($filepath, 'F');
				$content = file_get_contents($filepath);
				echo $content;
				delete_file($filepath);
				break;
			case 'file':
				$pdf->Output($filepath, 'F');
				return $filepath;
				break;
			default:
				$pdf->Output($filename, 'D');
				
		}
		exit;
	}
	
	public function ajaxExportPdf() 
	{
		$output = '';
		if (@$_GET['ajax'] == 'true') {
			$output = 'raw';
		}
		$this->generatePdf($_GET['start_date'], $_GET['end_date'], $output); 
	}
	
	public function generateExcel($start_date, $end_date, $output) 
	{
		$start_date = $_GET['start_date'];
		$end_date = $_GET['end_date'];
		
		$filepath = $this->model->writeExcel($start_date, $end_date);
		$filename = 'Detail Presensi - ' . format_date($start_date) . '_' . format_date($end_date) . '.xlsx';
		
		switch ($output) {
			case 'raw':
				$content = file_get_contents($filepath);
				echo $content;
				delete_file($filepath);
				break;
			case 'file':
				return $filepath;
				break;
			default:
				header('Content-disposition: attachment; filename="'. $filename .'"');
				header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
				header('Content-Transfer-Encoding: binary');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');  
				$content = file_get_contents($filepath);
				delete_file($filepath);
				echo $content;
		}
		exit;
	}
	
	public function ajaxExportExcel() 
	{
		$output = '';
		if (@$_GET['ajax'] == 'true') {
			$output = 'raw';
		}
		$this->generateExcel($_GET['start_date'], $_GET['end_date'], $output); 
	}
		
	// Pembelian
	public function getDataDTPresensi() {

		$this->hasPermissionPrefix('read');
		
		$num_data = $this->model->countAllDataPresensi();
		$result['draw'] = $start = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = $num_data;
		
		$query = $this->model->getListPresensi();
		$result['recordsFiltered'] = $query['total_filtered'];
				
		helper('html');
		$id_user = $this->session->get('user')['id_user'];
		
		$no = $this->request->getPost('start') + 1 ?: 1;
		foreach ($query['data'] as $key => &$val) 
		{
			switch ($val['status']) {
				case 'Tepat waktu':
					$color = 'success';
					break;
				case 'Tidak absen':
					$color = 'danger';
					break;
				default:
					$color = 'warning';
					break;
			}
			
			$val['status'] = '<span class="badge rounded-pill text-bg-' . $color . '">' . $val['status'] . '</span>';
			
			$val['ignore_urut'] = $no;
			$val['ignore_action'] = '<div class="btn-action-group">' . 
				btn_link(['url' => base_url() . 'presensi-detail/edit?id=' . $val['id_user_presensi']
						,'label' => 'Edit'
						, 'icon' => 'fas fa-edit'
						, 'attr' => ['target' => '_blank', 'class' => 'btn btn-success btn-xs me-1', 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Edit Data'] ]
				) . 
				btn_label(['label' => 'Delete'
						, 'icon' => 'fas fa-times'
						, 'attr' => ['class' => 'btn btn-danger btn-xs btn-del-presensi'
						, 'data-id' => $val['id_user_presensi']
						, 'data-delete-message' => 'Hapus data presensi ' . $val['jenis_presensi'] . ' tanggal ' . format_tanggal($val['tanggal']) . ' pukul ' . $val['waktu'] . ' atas nama ' . $val['nama'] . ' ?', 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Delete Data'] ]) . 
			'</div>';
			
			if ($val['foto']) {
				if (file_exists(ROOTPATH . 'public/images/presensi/' . $val['foto'])) {
					$url_image = base_url() . 'public/images/presensi/' . $val['foto'];
					$image = '<a href="' . $url_image . '" class="glightbox" data-glightbox="title: Foto Presensi ' . $val['nama'] . '; description: Presensi ' . $val['jenis_presensi'] . ' tanggal ' . format_tanggal($val['tanggal']) . ' pukul ' . $val['waktu'] . '"><img style="border-radius:10px" src="' . $url_image . '" title="Foto Absen ' . $val['nama'] . '"/></a>';
				} else {
					$url_image = base_url() . 'public/images/noimage.png';
					$image = '<img style="border-radius:10px" src="' . $url_image . '" title="' . $val['nama'] . '"/>';
				}
				
				$val['foto'] = '<div class="text-center">' . $image . '</div>';
			}
			
			$val['jenis_presensi'] = ucfirst($val['jenis_presensi']);
			$val['tanggal'] = '<div class="text-end">' . format_tanggal($val['tanggal']) . '</div>';
			$no++;
		}
					
		$result['data'] = $query['data'];
		echo json_encode($result); exit();
	}
}
