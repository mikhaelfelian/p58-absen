<?php
/**
* App Name	: Aplikasi Absensi Online
* Author	: Agus Prawoto Hadi
* Website	: https://jagowebdev.com
* Year		: 2024
*/

namespace App\Controllers;
use App\Models\DashboardModel;

class Dashboard extends BaseController
{
	public function __construct() {
		parent::__construct();
		$this->model = new DashboardModel;
		$this->addJs($this->config->baseURL . 'public/vendors/chartjs/chart.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/material-icons/css.css');
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/datatables/extensions/Buttons/js/dataTables.buttons.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/datatables/extensions/Buttons/js/buttons.bootstrap5.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/datatables/extensions/JSZip/jszip.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/datatables/extensions/pdfmake/pdfmake.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/datatables/extensions/pdfmake/vfs_fonts.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/datatables/extensions/Buttons/js/buttons.html5.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/datatables/extensions/Buttons/js/buttons.print.min.js');
		$this->addStyle ( $this->config->baseURL . 'public/vendors/datatables/extensions/Buttons/css/buttons.bootstrap5.min.css');
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/filesaver/FileSaver.js');
		
		$this->addStyle($this->config->baseURL . 'public/themes/modern/css/dashboard.css');
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/dashboard.js');
	}
	
	public function index()
	{
		$result = $this->model->getListTahun();
		$list_tahun = [];
		foreach ($result as $val) {
			$list_tahun[$val['tahun']] = $val['tahun'];
		}
		
		if ($list_tahun) {
			$tahun = max($list_tahun);
		} else {
			$tahun = '';
		}
		
		$this->data['list_tahun'] = $list_tahun;
		$this->data['tahun'] = $tahun;
		
		// Baris pertama
		$this->data['total_item_terjual'] = '';
		$this->data['total_jumlah_transaksi'] = '';
		$this->data['total_nilai_penjualan'] = '';
		$this->data['total_pelanggan_aktif'] = '';
		$this->data['penjualan'] = '';
		$this->data['total_penjualan'] = '';
		$this->data['item_terjual'] = '';
		$this->data['kategori_terjual'] = '';
		$this->data['pelanggan_terbesar'] = '';
		
		if ($tahun) {
			/* $this->data['total_item_terjual'] = $this->model->getTotalItemTerjual( $tahun );
			$this->data['total_jumlah_transaksi'] = $this->model->getTotalJumlahTransaksi( $tahun );
			$this->data['total_nilai_penjualan'] = $this->model->getTotalNilaiPenjualan( $tahun );
			$this->data['total_pelanggan_aktif'] = $this->model->getTotalPelangganAktif( $tahun );
		
			$this->data['penjualan'] = $this->model->getSeriesPenjualan( $list_tahun );
			$this->data['total_penjualan'] = $this->model->getSeriesTotalPenjualan( $list_tahun );
			$this->data['item_terjual'] = $this->model->getItemTerjual( $tahun );
			$this->data['kategori_terjual'] = $this->model->getKategoriTerjual( $tahun );        
			$this->data['pelanggan_terbesar'] = $this->model->getPembelianPelangganTerbesar( $tahun ); */
		}
		
		$this->data['total_pegawai'] = $this->model->getTotalPegawai();
		
		// Presensi perbulan
		$result = $this->model->getPresensiPerbulan($list_tahun);
		if (!$result) {
			// $result[date('Y')] = 0;
		}
		$data_presensi = [];
		$presensi_pertahun = [];
		$total_presensi_masuk = [];
		$total_presensi_pulang = [];
		$presensi_perbulan = [];
		if ($result) {
			foreach ($result as $tahun => $val) {
				$total_presensi_masuk[$tahun] = 0;
				$total_presensi_pulang[$tahun] = 0;
				$presensi_perbulan[$tahun] = [];
				$data_presensi[$tahun] = [];
				$presensi_pertahun[$tahun] = [];
			}
		}
		
		if ($result) {
			foreach ($result as $tahun => $arr_data) {
				foreach ($arr_data as $val) {
					if ($val['jenis_presensi'] == 'masuk') {
						$total_presensi_masuk[$tahun]++;
					}
					if ($val['jenis_presensi'] == 'pulang') {
						$total_presensi_pulang[$tahun]++;
					}
					$data_presensi[$tahun][$val['bulan']][$val['status']][] = $val;
				}
			}
		}
		
		$nama_status = ['tepat_waktu','terlambat_masuk','pulang_sebelum_waktunya', 'tidak_absen'];
		$total_presensi = 0;
		foreach ($nama_status as $status) 
		{
			foreach ($result as $tahun => $val) {
				$presensi_pertahun[$tahun][$status] = 0;
			}
			
			foreach ($result as $tahun => $val) 
			{
				for ($i = 1; $i <= 12; $i++) 
				{
					if (key_exists($i, $data_presensi[$tahun])) {
						$jumlah = 0;
						if (key_exists($status, $data_presensi[$tahun][$i])) {
							$jumlah = count($data_presensi[$tahun][$i][$status]);
							$presensi_pertahun[$tahun][$status] += $jumlah;
						}
					} else {
						$jumlah = 0;
					}
					$presensi_perbulan[$tahun][$status][] = $jumlah;
				}
			}
		}
		
		$total_jumlah_presensi = [];
		foreach ($presensi_pertahun as $tahun => $arr_data) {
			$total_jumlah_presensi[$tahun] = 0;
		}
		foreach ($presensi_pertahun as $tahun => $arr_data) {
			foreach ($arr_data as $val) {
				$total_jumlah_presensi[$tahun] += $val;
			}
		}
				
		/* $result = [];
		foreach ($query_result as $val) {
			$result[$val['bulan']] = ['tepat_waktu' => $val['tepat_waktu'], 'tidak_tepat_waktu' => $val['tidak_tepat_waktu']];
		}
		
		$result_perbulan = [];
		$total_presensi = [];
		$total_presensi['tepat_waktu'] = 0;
		$total_presensi['tidak_tepat_waktu'] = 0;
		for ($i = 1; $i <= 12; $i++) {
			
			$tepat_waktu = 0;
			$tidak_tepat_waktu = 0;
			if (key_exists($i, $result)) {
				$tepat_waktu = $result[$i]['tepat_waktu'];
				$tidak_tepat_waktu = $result[$i]['tidak_tepat_waktu'];
			}
			
			$total_presensi['tepat_waktu'] += $tepat_waktu;
			$total_presensi['tidak_tepat_waktu'] += $tidak_tepat_waktu;
			
			$result_perbulan['tepat_waktu'][] = $tepat_waktu;
			$result_perbulan['tidak_tepat_waktu'][] = $tidak_tepat_waktu;
		}
		
		$total_presensi_all = $total_presensi['tepat_waktu'] + $total_presensi['tidak_tepat_waktu'];
		$total_presensi_persen['tepat_waktu'] = round($total_presensi['tepat_waktu'] / $total_presensi_all * 100);
		$total_presensi_persen['tidak_tepat_waktu'] = round($total_presensi['tidak_tepat_waktu'] / $total_presensi_all * 100); */
		
		$jml_data_presensi = $this->model->getJumlahDataPresensi(date('Y'));
		$presensi_urut_tepat_waktu = $this->model->getPresensiUrutTepatWaktu();
		
		$this->data['presensi_perbulan'] = $presensi_perbulan;
		$this->data['presensi_pertahun'] = $presensi_pertahun;
		$this->data['total_jumlah_presensi'] = $total_jumlah_presensi;
		$this->data['total_presensi_masuk'] = $total_presensi_masuk;
		$this->data['total_presensi_pulang'] = $total_presensi_pulang;
		$this->data['jml_data_presensi'] = $jml_data_presensi;
		$this->data['presensi_urut_tepat_waktu'] = $presensi_urut_tepat_waktu;
		// $this->data['total_presensi_persen'] = $total_presensi_persen;
				
		$this->data['message']['status'] = 'ok';
        if (empty($this->data['penjualan'])) {
            $this->data['message']['status'] = 'error';
            $this->data['message']['message'] = 'Data tidak ditemukan';
		}
		
		$this->view('dashboard.php', $this->data);
	}
	
	public function generateExcel($output) 
	{
		$filepath = $this->model->writeExcel($_GET['tahun']);
		$filename = 'Daftar Barang.xlsx';
		
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
	
	public function ajaxExportExcelPresensiTerbaru() 
	{
		$output = '';
		if (@$_GET['ajax'] == 'true') {
			$output = 'raw';
		}
		$this->generateExcel($output); 
	}
	
	public function generatePdf($output) 
	{
		$barang = $this->model->getDataBarang();
		if (!$barang) {
			$this->errorDataNotFound();
			return false;
		}
		
		$identitas = $this->model->getIdentitas();
		$pdf = new JWDPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
		$pdf->setFooterText('Daftar Barang per ' . format_date(date('Y-m-d')));

		$pdf->setPageUnit('mm');

		// set document information
		$pdf->SetCreator($identitas['nama']);
		$pdf->SetAuthor($identitas['nama']);
		$pdf->SetTitle('Daftar Barang Per ' . format_date(date('Y-m-d')) );
		$pdf->SetSubject('Daftar Barang');
		
		// Margin Header
		$pdf->SetMargins(10, 0, 10);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
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
		$pdf->Cell(0, 0, 'Daftar Barang', 0, 1, 'C', 0, '', 0, false, 'T', 'M' );
		$pdf->SetFont ('helvetica', 'B', $font_size + 2, '', 'default', true );
		$pdf->Cell(0, 0, 'Tanggal : ' . format_date(date('Y-m-d')), 0, 1, 'C', 0, '', 0, false, 'T', 'M' );
		
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
					<th style="width:10%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">Kode Barang</th>
					<th style="width:50%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">Nama Barang</th>
					<th style="width:10%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Satuan</th>
					<th style="width:8%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Stok</th>
					<th style="width:16%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="center">Barcode</th>
				</tr>
			</thead>
			<tbody>
		EOD;

			$no = 1;
			$format_number = 'format_number';

			foreach ($barang as $val) {
				$tbl .= <<<EOD
					<tr>
						<td style="width:5%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color" align="center">$no</td>
						<td style="width:10%;border-bottom-color:$border_color;border-right-color:$border_color;border-left-color:$border_color">$val[kode_barang]</td>
						<td style="width:50%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$val[nama_barang]</td>
						<td style="width:10%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$val[satuan]</td>
						<td style="width:8%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color" align="right">{$format_number($val['total_stok'])}</td>
						<td style="width:16%;border-top-color:$border_color;border-bottom-color:$border_color;border-right-color:$border_color">$val[barcode]</td>
					</tr>
					EOD;
				$no++;
			}
		
			$tbl .= <<<EOD
			</tbody>
		</table>
		EOD;

		$pdf->writeHTML($tbl, false, false, false, false, '');
		
		$filename = 'Daftar Barang - ' . date('dmY') . '.pdf';
		$filepath = ROOTPATH . 'public/tmp/barang_' . time() . '.pdf.tmp';
		
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
		$this->generatePdf($output); 
	}
	
	public function getDataDTPresensiTerbaru() {
		
		$this->hasPermission('read_all');
		
		$num_data = $this->model->countAllDataPresensiTerbaru( $_GET['tahun'] );
		$result['draw'] = $start = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = $num_data;
		
		$query = $this->model->getListDataPresensiTerbaru( $_GET['tahun'] );
		$result['recordsFiltered'] = $query['total_filtered'];
				
		helper('html');
		
		$no = $this->request->getPost('start') + 1 ?: 1;
		foreach ($query['data'] as $key => &$val) 
		{
			$val['ignore_urut'] = $no;
			if ($val['status'] == 'tidak absen') {
				$val['status'] = '<span class="badge rounded-pill text-bg-danger">' . $val['status'] . '</span>';
			}
			if ($val['status'] == 'tepat waktu') {
				$val['status'] = '<span class="badge rounded-pill text-bg-success">' . $val['status'] . '</span>';
			}
			if ($val['status'] == 'terlambat') {
				$val['status'] = '<span class="badge rounded-pill text-bg-warning">' . $val['status'] . '</span>';
			}
			if ($val['status'] == 'pulang awal') {
				$val['status'] = '<span class="badge rounded-pill text-bg-warning">' . $val['status'] . '</span>';
			}
			$no++;
		}
					
		$result['data'] = $query['data'];
		echo json_encode($result); exit();
	}
}