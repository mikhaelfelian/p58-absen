<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\PresensiRiwayatModel;
use App\Libraries\JWDPDF;

class Presensi_riwayat extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new PresensiRiwayatModel;	
		$this->data['title'] = 'Riwayat Presensi';
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/moment/moment.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.js');
		$this->addStyle ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.css');
		$this->addJs ( $this->config->baseURL . 'public/vendors/filesaver/FileSaver.js');
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/bootstrap-5-theme/select2-bootstrap-5-theme.min.css' );
			
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/presensi-riwayat.js');
	}
	
	public function index() {
		$start_date = '01-01-' . date('Y');
		$end_date = date('d-m-Y');
		
		$exp = explode('-', $start_date);
		$start_date_db = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
		
		$exp = explode('-', $end_date);
		$end_date_db = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
		
		$result = $this->model->getAllUser();
	
		if (has_permission('read_all')) {
			$user = ['' => 'Semua'];
		} else {
			$user = [];
		}
		
		foreach ($result as $val) {
			$user[$val['id_user']] = $val['nama'];
		}

		$this->data['user'] = $user;
		$this->data['start_date'] = $start_date;
		$this->data['end_date'] = $end_date;
		$this->data['start_date_db'] = $start_date_db;
		$this->data['end_date_db'] = $end_date_db;
		$this->view('presensi-riwayat-result.php', $this->data);
	}
	
	public function ajaxDeletePresensi() {
		if (empty($_POST['tanggal']) || empty($_POST['id_user'])) {
			$result = ['status' => 'error', 'message' => 'Invalid input'];
		} else {
			$detail = $this->model->getDetailPresensi($_POST['tanggal'], $_POST['id_user']);
			if ($detail) {
				$delete = $this->model->deleteDataPresensi($_POST['tanggal'], $_POST['id_user']);
				if ($delete) {
					$result = ['status' => 'ok', 'message' => 'Data berhasil dihapus'];
				} else {
					$result = ['status' => 'error', 'message' => 'Data gagal dihapus'];
				}
			} else {
				$result = ['status' => 'error', 'message' => 'Data tidak ditemukan'];
			}
		}
		echo json_encode($result);
	}
	
	public function ajaxGetDetailPresensi() {
		$this->data['result'] = $this->model->getDetailPresensi($_GET['tanggal'], $_GET['id_user']);
		echo view('themes/modern/presensi-riwayat-detail.php', $this->data);
	}
	
	public function generatePdf($start_date, $end_date, $output) 
	{
		$presensi = $this->model->getPresensiByDate($start_date, $end_date);
		
		// $identitas = $this->model->getIdentitas();
		$pdf = new JWDPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
		$pdf->setFooterText('Riwayat presensi periode ' . format_date($start_date) . ' s.d. ' . format_date($end_date));
		
		$pdf->setPageUnit('mm');

		// set document information
		$pdf->SetCreator('Jagowebdev.com');
		$pdf->SetAuthor('Agus Prawoto Hadi');
		$pdf->SetTitle('Riwayat Presensi Periode ' . $start_date . ' s.d. ' . $end_date);
		$pdf->SetSubject('Riwayat Presensi');
		
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
		$pdf->Cell(0, 0, 'Riwayat Presensi', 0, 1, 'C', 0, '', 0, false, 'T', 'M' );
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
					<th style="width:22%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">Nama Pegawai</th>
					<th style="width:22%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">NIP Pegawai</th>
					<th style="width:13%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Tanggal</th>
					<th style="width:12%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Masuk</th>
					<th style="width:12%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Pulang</th>
					<th style="width:14%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Status</th>
				</tr>
			</thead>
			<tbody>
		EOD;

			$no = 1;
			$format_date = 'format_date';
			$total = 0;
			foreach ($presensi as $val) {
				$exp = explode('-', $val['tanggal']);
				$tanggal = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
				$tbl .= <<<EOD
					<tr>
						<td style="width:5%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">$no</td>
						<td style="width:22%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color">$val[nama]</td>
						<td style="width:22%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color">$val[nip]</td>
						<td style="width:13%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="right">$tanggal</td>
						<td style="width:12%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="right">$val[waktu_presensi_masuk]</td>
						<td style="width:12%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$val[waktu_presensi_pulang]</td>
						<td style="width:14%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$val[status]</td>
					</tr>
					EOD;
				$no++;
			}
		
			$tbl .=	'</tbody></table>';

		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$filename = 'Riwayat Presensi - ' . format_date($start_date) . '_' . format_date($end_date) . '.pdf';
		$filepath = ROOTPATH . 'public/tmp/riwayat_presensi_' . time() . '.pdf.tmp';
		
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
		$filename = 'Riwayat Presensi - ' . format_date($start_date) . '_' . format_date($end_date) . '.xlsx';
		
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
			/* $message = [];
			if ($val['waktu_presensi_masuk'] > $val['batas_waktu_presensi_masuk']) {
				$message[] = 'Terlambat Masuk';
			}

			if ($val['waktu_presensi_pulang'] < $val['batas_waktu_presensi_pulang']) {
				$message[] = 'Pulang Sebelum Waktunya';
			}
			
			if ($message) {
				if (count($message) == 1) {
					$message_text = $message[0];
				} else {
					$message_text = $message[0] . ' dan ' . $message[1];
				}
				$val['status'] = '<span class="badge rounded-pill text-bg-warning">' . $message_text . '</span>';
			} else {
				$val['status'] = '<span class="badge rounded-pill text-bg-success">Tepat Waktu</span>';
			} */
			
			if ($val['status'] == 'Tepat waktu') {
				$color = 'success';
			} else if (strpos(strtolower($val['status']), 'tidak') !== false) {
				$color = 'danger';
			} else {
				$color = 'warning';
			}
			$val['status'] = '<span class="badge rounded-pill text-bg-' . $color . '">' . $val['status'] . '</span>';
			
			$val['ignore_urut'] = $no;
			$val['ignore_action'] = '<div class="btn-action-group">' . 
				btn_label(['label' => 'Edit'
						, 'icon' => 'fas fa-edit'
						, 'attr' => ['target' => '_blank', 'class' => 'btn btn-success btn-edit btn-xs me-1'
									, 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Edit Data'
									, 'data-tanggal' => $val['tanggal']
									, 'data-id-user' => $val['id_user']
								]
						]
				) . 
				btn_label(['label' => 'Delete'
						, 'icon' => 'fas fa-times'
						, 'attr' => ['class' => 'btn btn-danger btn-xs btn-delete'
									, 'data-id-user' => $val['id_user']
									, 'data-tanggal' => $val['tanggal']
									, 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Delete Data'
								] 
						]) . 
			'</div>';
			
			$val['tanggal'] = '<div class="text-end">' . format_tanggal($val['tanggal']) . '</div>';
			$no++;
		}
					
		$result['data'] = $query['data'];
		echo json_encode($result); exit();
	}
}
