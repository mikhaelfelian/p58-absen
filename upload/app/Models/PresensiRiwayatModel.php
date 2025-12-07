<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024 - 2025
*/

namespace App\Models;

class PresensiRiwayatModel extends \App\Models\BaseModel
{
	public function getAllUser() {
		$where = '';
		if (has_permission('read_own')) {
			$where = ' WHERE id_user = ' . $_SESSION['user']['id_user'];
		}
		$sql = 'SELECT * FROM user' . $where;
		$result = $this->db->query($sql)->getResultArray();
		
		return $result;
	}
	
	public function getDetailPresensi($tanggal, $id_user) {
		$sql = 'SELECT *,
					CASE 
						WHEN (jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi) OR (jenis_presensi = "pulang" AND waktu >= batas_waktu_presensi)
							THEN "Tepat waktu"
						WHEN jenis_presensi = "masuk" AND waktu >= batas_waktu_presensi
							THEN "Terlambat masuk"
						WHEN jenis_presensi = "pulang" AND waktu <= batas_waktu_presensi
							THEN "Pulang sebelum waktunya"
						WHEN waktu IS NULL OR waktu = "" OR waktu = "00:00:00"
							THEN "Tidak absen"
					END AS status
				FROM user_presensi 
				LEFT JOIN user USING(id_user)
				WHERE tanggal = ? AND id_user = ?';
		$result = $this->db->query($sql, [$tanggal, $id_user])->getResultArray();
		return $result;
	}
	
	public function deleteDataPresensi($tanggal, $id_user) {
		$delete = $this->db->table('user_presensi')->delete(['tanggal' => $tanggal, 'id_user' => $id_user]);
		return $delete;
	}
	
	public function getPresensiByDate($start_date, $end_date) 
	{
		if (has_permission('read_own')) {
			$id_user = $_SESSION['user']['id_user'];
		} else {
			$id_user = !empty($_GET['id_user']) ? ' AND id_user = ' . $_GET['id_user'] : '';
		}
		$add_where = ' WHERE 1=1 ';
	
		if (!empty($_GET['status'])) {
			switch ($_GET['status']) {
				case 'tepat_waktu':
					$add_where .= ' AND waktu_presensi_masuk <= batas_waktu_presensi_masuk AND waktu_presensi_pulang >= batas_waktu_presensi_pulang';
					break;
				case 'terlambat_masuk':
					$add_where .= ' AND waktu_presensi_masuk >= batas_waktu_presensi_masuk AND waktu_presensi_pulang >= batas_waktu_presensi_pulang';
					break;
				case 'pulang_sebelum_waktunya':
					$add_where .= ' AND waktu_presensi_masuk <= batas_waktu_presensi_masuk AND waktu_presensi_pulang <= batas_waktu_presensi_pulang';
					break;
				case 'terlambat_masuk_dan_pulang_sebelum_waktunya':
					$add_where .= ' AND waktu_presensi_masuk >= batas_waktu_presensi_masuk AND waktu_presensi_pulang <= batas_waktu_presensi_pulang';
					break;
				case 'tidak_absen':
					$add_where .= ' AND ( (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") OR (waktu_presensi_pulang IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") )';
					break;
			}
		}
		
		$sql = 'SELECT nama, nip, tanggal, waktu_presensi_masuk, batas_waktu_presensi_masuk
						, waktu_presensi_pulang, batas_waktu_presensi_pulang, 
						CASE
							WHEN ( (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") OR (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") )
									THEN "Tidak absen"
							WHEN (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") AND waktu_presensi_masuk < batas_waktu_presensi_masuk
									THEN "Tidak absen pulang"
							WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang > batas_waktu_presensi_pulang
									THEN "Tidak absen masuk"
							WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang < batas_waktu_presensi_pulang
									THEN "Tidak absen masuk dan pulang awal"
							WHEN (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") AND waktu_presensi_masuk > batas_waktu_presensi_masuk
									THEN "Terlambat masuk dan tidak absen pulang"
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
									AND waktu_presensi_pulang < batas_waktu_presensi_pulang
									THEN "Terlambat masuk dan pulang sebelum waktunya"
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
									THEN "Terlambat masuk"
							WHEN waktu_presensi_pulang < batas_waktu_presensi_pulang 
									THEN "Pulang sebelum waktunya"
							ELSE "Tepat waktu"
						END AS status
				FROM
				( 
					SELECT *, MIN(IF(jenis_presensi = "masuk", waktu, NULL)) AS waktu_presensi_masuk 
							, MAX(IF(jenis_presensi = "pulang", waktu, NULL)) AS waktu_presensi_pulang
							, MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_masuk
							, MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_pulang
					FROM user_presensi
					LEFT JOIN user USING(id_user)
					WHERE tanggal >= "' . $start_date . '" AND tanggal <= "' . $end_date . '" ' . $id_user . '
					GROUP BY tanggal, id_user 
				) AS tabel' . $add_where;			
						
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function writeExcel($start_date, $end_date) 
	{
		require_once(ROOTPATH . "/app/ThirdParty/PHPXlsxWriter/xlsxwriter.class.php");
		
		$colls = [
					'no' 						=> ['type' => '#,##0', 'width' => 5, 'title' => 'No'],
					'nama' 						=> ['type' => 'string', 'width' => 30, 'title' => 'Nama Pegawai'],
					'nip' 						=> ['type' => 'string', 'width' => 20, 'title' => 'NIP Pegawai'],
					'tanggal' 					=> ['type' => 'date', 'width' => 13, 'title' => 'Tgl. Presensi'],
					'waktu_presensi_masuk' 		=> ['type' => 'string', 'width' => 13, 'title' => 'Presensi Masuk'],
					'batas_waktu_presensi_masuk' => ['type' => 'string', 'width' => 13, 'title' => 'Batas Presensi Masuk'],
					'waktu_presensi_pulang' 	=> ['type' => 'string', 'width' => 13, 'title' => 'Presensi Pulang'],
					'batas_waktu_presensi_pulang' => ['type' => 'string', 'width' => 13, 'title' => 'Batas Presensi Pulang'],
					'status' 					=> ['type' => 'string', 'width' => 12, 'title' => 'Status']
				];
		
		$col_type = $col_width = $col_header = [];
		foreach ($colls as $field => $val) {
			$col_type[$field] = $val['type'];
			$col_header[$field] = $val['title'];
			$col_header_type[$field] = 'string';
			$col_width[] = $val['width'];
		}
		
		// Excel
		$sheet_name = strtoupper('Riwayat Presensi');
		$writer = new \XLSXWriter();
		$writer->setAuthor('Jagowebdev');
		
		$writer->writeSheetHeader($sheet_name, $col_header_type, $col_options = ['widths'=> $col_width, 'suppress_row'=>true]);
		$writer->writeSheetRow($sheet_name, $col_header);
		$writer->updateFormat($sheet_name, $col_type);
		
		$no = 1;
		$result = $this->getPresensiByDate($start_date, $end_date);
		foreach ($result as $row) {
			array_unshift($row, $no);
			$writer->writeSheetRow($sheet_name, $row);
			$no++;
		}
		
		$tmp_file = ROOTPATH . 'public/tmp/penjualan_barang_' . time() . '.xlsx.tmp';
		$writer->writeToFile($tmp_file);
		return $tmp_file;
	}
	
	// Penjualan
	public function countAllDataPresensi() {
		if (has_permission('read_own')) {
			$user = ' AND id_user = ' . $_SESSION['user']['id_user'];
		} else {
			$user = !empty($_GET['id_user']) ? ' AND id_user = ' . $_GET['id_user'] : '';
		}
		$sql = 'SELECT COUNT(*) AS jml FROM user_presensi AS tabel WHERE tanggal >= ? AND tanggal <= ?' . $user;
		$result = $this->db->query($sql, [$_GET['start_date'], $_GET['end_date']])->getRow();
		return $result->jml;
	}
	
	public function getListPresensi() 
	{
		$columns = $this->request->getPost('columns');

		// Search
		if (has_permission('read_own')) {
			$user = ' AND id_user = ' . $_SESSION['user']['id_user'];
		} else {
			$user = !empty($_GET['id_user']) ? ' AND id_user = ' . $_GET['id_user'] : '';
		}
		
		$search_all = @$this->request->getPost('search')['value'];
		$where = ' WHERE tanggal >= "' . $_GET['start_date'] . '" AND tanggal <= "' . $_GET['end_date'] . '"' . $user;
		if ($search_all) {
			foreach ($columns as $val) {
				
				if (strpos($val['data'], 'ignore_search') !== false) 
					continue;
				
				if (strpos($val['data'], 'ignore') !== false)
					continue;
				
				$where_col[] = $val['data'] . ' LIKE "%' . $search_all . '%"';
			}
			 $where .= ' AND (' . join(' OR ', $where_col) . ') ';
		}
		
		$add_where = ' WHERE 1=1 ';
	
		if (!empty($_GET['status'])) {
			switch ($_GET['status']) {
				case 'tepat_waktu':
					$add_where .= ' AND waktu_presensi_masuk <= batas_waktu_presensi_masuk AND waktu_presensi_pulang >= batas_waktu_presensi_pulang';
					break;
				case 'terlambat_masuk':
					$add_where .= ' AND waktu_presensi_masuk >= batas_waktu_presensi_masuk AND waktu_presensi_pulang >= batas_waktu_presensi_pulang';
					break;
				case 'pulang_sebelum_waktunya':
					$add_where .= ' AND waktu_presensi_masuk <= batas_waktu_presensi_masuk AND waktu_presensi_pulang <= batas_waktu_presensi_pulang';
					break;
				case 'terlambat_masuk_dan_pulang_sebelum_waktunya':
					$add_where .= ' AND waktu_presensi_masuk >= batas_waktu_presensi_masuk AND waktu_presensi_pulang <= batas_waktu_presensi_pulang';
					break;
				case 'tidak_absen':
					$add_where .= ' AND ( (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") OR (waktu_presensi_pulang IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") )';
					break;
			}
		}
		
		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml 
				FROM 
					(
						SELECT user_presensi.*, nama, MIN(IF(jenis_presensi = "masuk", waktu, NULL)) AS waktu_presensi_masuk 
							, MAX(IF(jenis_presensi = "pulang", waktu, NULL)) AS waktu_presensi_pulang
							, MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_masuk
							, MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_pulang
						FROM user_presensi
						LEFT JOIN user USING(id_user)
						' . $where . '
						GROUP BY tanggal, id_user
					) AS tabel ' . $add_where;
					// echo $sql; die;
		$data = $this->db->query($sql, [$_GET['start_date'], $_GET['end_date']])->getRowArray();
		$total_filtered = $data['jml'];
		
		// Order
		$order_data = $this->request->getPost('order');
		$order = '';
		if (strpos($_POST['columns'][$order_data[0]['column']]['data'], 'ignore_search') === false) {
			$order_by = $columns[$order_data[0]['column']]['data'] . ' ' . strtoupper($order_data[0]['dir']);
			$order = ' ORDER BY ' . $order_by;
		}

		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		
		// Query Data
		$sql = '
				SELECT *, 
						CASE
							WHEN ( (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") OR (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") )
									THEN "Tidak absen"
							WHEN (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") AND waktu_presensi_masuk < batas_waktu_presensi_masuk
									THEN "Tidak absen pulang"
							WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang > batas_waktu_presensi_pulang
									THEN "Tidak absen masuk"
							WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang < batas_waktu_presensi_pulang
									THEN "Tidak absen masuk dan pulang awal"
							WHEN (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") AND waktu_presensi_masuk > batas_waktu_presensi_masuk
									THEN "Terlambat masuk dan tidak absen pulang"
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
									AND waktu_presensi_pulang < batas_waktu_presensi_pulang
									THEN "Terlambat masuk dan pulang sebelum waktunya"
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
									THEN "Terlambat masuk"
							WHEN waktu_presensi_pulang < batas_waktu_presensi_pulang 
									THEN "Pulang sebelum waktunya"
							ELSE "Tepat waktu"
						END AS status
				FROM
				( 
					SELECT user_presensi.*, nama, MIN(IF(jenis_presensi = "masuk", waktu, NULL)) AS waktu_presensi_masuk 
							, MAX(IF(jenis_presensi = "pulang", waktu, NULL)) AS waktu_presensi_pulang
							, MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_masuk
							, MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_pulang
					FROM user_presensi
					LEFT JOIN user USING(id_user)
					' . $where . '
					GROUP BY tanggal, id_user 
				) AS tabel 
				
				' . $add_where . $order . ' LIMIT ' . $start . ', ' . $length;
		
		$data = $this->db->query($sql)->getResultArray();
		
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
}
?>