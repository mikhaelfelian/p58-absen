<?php
namespace App\Models;

class DashboardModel extends \App\Models\BaseModel
{
	public function __construct() {
		parent::__construct();
	}
	
	public function getListTahun() 
	{
		$sql= 'SELECT YEAR(tanggal) AS tahun
				FROM user_presensi
				GROUP BY tahun';
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function getJumlahDataPresensi($tahun) {
		$sql = 'SELECT COUNT(*) AS jml FROM user_presensi WHERE tanggal LIKE "' . $tahun . '%"';
		$result = $this->db->query($sql)->getRowArray();
		return $result['jml'];
	}
	
	public function getTotalPegawai() {
		$sql = 'SELECT COUNT(*) AS jml FROM user';
		$result = $this->db->query($sql)->getRowArray();
		return $result['jml'];
	}
	
	public function getPresensiUrutTepatWaktu() {
		$start_date = date('Y') . '-01-01';
		$end_date = date('Y-m-d');
		$sql = 'SELECT nama
						, COUNT(IF(status_absen = 1, nama, NULL)) AS jml_tepat_waktu
						, COUNT(IF(status_absen = 0, nama, NULL)) AS jml_tidak_tepat_waktu
				FROM (	
					SELECT id_user, nama,
						CASE
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk
								THEN 0
							WHEN waktu_presensi_pulang < batas_waktu_presensi_pulang
								THEN 0
							WHEN waktu_presensi_masuk IS NULL OR
									waktu_presensi_masuk = "" OR
									waktu_presensi_masuk = "00:00:00" OR
									waktu_presensi_pulang IS NULL OR
									waktu_presensi_pulang = "" OR
									waktu_presensi_pulang = "00:00:00"
								THEN 0
							ELSE 1
						END
						AS status_absen
					FROM(
						SELECT user_presensi.*, nama, MIN(IF(jenis_presensi = "masuk", waktu, NULL)) AS waktu_presensi_masuk 
											, MAX(IF(jenis_presensi = "pulang", waktu, NULL)) AS waktu_presensi_pulang
											, MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_masuk
											, MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_pulang
									FROM user_presensi
									LEFT JOIN user USING(id_user)
									WHERE tanggal >= "' . $start_date . '" AND tanggal <= "' . $end_date. '"
									GROUP BY tanggal, id_user
					) AS tabel
				) AS tabel_rekap
				GROUP BY id_user
				ORDER BY jml_tepat_waktu DESC LIMIT 5';
				// echo $sql; die;
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function getPresensiPerbulan($list_tahun) {
		if (!$list_tahun) {
			return [];
		}
		$list_tahun = [max($list_tahun), max($list_tahun) - 1];
		foreach ($list_tahun as $tahun) 
		{
			$sql = 'SELECT *, MONTH(tanggal) AS bulan, CASE
								WHEN waktu IS NULL OR waktu = "" OR waktu = "00:00:00" THEN "tidak_absen"
								WHEN jenis_presensi = "masuk" AND waktu > batas_waktu_presensi THEN "terlambat_masuk"
								WHEN jenis_presensi = "pulang" AND waktu < batas_waktu_presensi THEN "pulang_sebelum_waktunya"
								ELSE "tepat_waktu"
								END
								AS status
					FROM user_presensi
					LEFT JOIN user USING(id_user)
					WHERE tanggal LIKE "' . $tahun . '%"';

			$result[$tahun] = $this->db->query($sql)->getResultArray();
		}
		return $result;
	}
	
	public function writeExcel($tahun) 
	{
		require_once(ROOTPATH . "/app/ThirdParty/PHPXlsxWriter/xlsxwriter.class.php");
		$query = $this->getPresensiByDate($tahun);
		
		$colls = [
					'no' 			=> ['type' => '#,##0', 'width' => 5, 'title' => 'No'],
					'nama' 			=> ['type' => 'string', 'width' => 20, 'title' => 'Nama Pegawai'],
					'tanggal' 		=> ['type' => 'date', 'width' => 13, 'title' => 'Tanggal'],
					'jenis_presensi' => ['type' => 'string', 'width' => 13, 'title' => 'Jenis Presensi'],
					'waktu' 		=> ['type' => 'string', 'width' => 10, 'title' => 'Waktu Presensi'],
					'batas_waktu_presensi' 	=> ['type' => 'string', 'width' => 10, 'title' => 'Batas Presensi'],
					'status' 		=> ['type' => 'string', 'width' => 15, 'title' => 'Status'],
					'koordinat' 	=> ['type' => 'string', 'width' => 20, 'title' => 'Lokasi Presensi'],
				];
		
		$col_type = $col_width = $col_header = [];
		foreach ($colls as $field => $val) {
			$col_type[$field] = $val['type'];
			$col_header[$field] = $val['title'];
			$col_header_type[$field] = 'string';
			$col_width[] = $val['width'];
		}
		
		// Excel
		$sheet_name = strtoupper('Detail Presensi');
		$writer = new \XLSXWriter();
		$writer->setAuthor('Jagowebdev');
		
		$writer->writeSheetHeader($sheet_name, $col_header_type, $col_options = ['widths'=> $col_width, 'suppress_row'=>true]);
		$writer->writeSheetRow($sheet_name, $col_header);
		$writer->updateFormat($sheet_name, $col_type);
		
		$no = 1;
		foreach ($query as $row) {
			array_unshift($row, $no);
			$writer->writeSheetRow($sheet_name, $row);
			$no++;
		}
		
		$tmp_file = ROOTPATH . 'public/tmp/presensi_terbaru_' . time() . '.xlsx.tmp';
		$writer->writeToFile($tmp_file);
		return $tmp_file;
	}
	
	public function getPresensiByDate($tahun) 
	{
		$jenis_presensi = !empty($_GET['jenis_presensi']) ? ' AND jenis_presensi = "' . $_GET['jenis_presensi'] . '"' : '';
		$status = '';
		if (!empty($_GET['jenis_presensi'])) {
			switch ($_GET['jenis_presensi']) {
				case 'tepat_waktu':
					$status = ' AND ( (jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi) OR (jenis_presensi = "pulang" AND waktu >= batas_waktu_presensi) )';
					break;
				case 'terlambat_masuk':
					$status = ' AND jenis_presensi = "masuk" AND waktu >= batas_waktu_presensi';
					break;
				case 'pulang_sebelum_waktunya':
					$status = ' AND jenis_presensi = "pulang" AND waktu <= batas_waktu_presensi';
					break;
				case 'terlambat_masuk_dan_pulang_sebelum_waktunya':
					$status = ' AND ( (jenis_presensi = "masuk" AND waktu >= batas_waktu_presensi) OR (jenis_presensi = "pulang" AND waktu <= batas_waktu_presensi) )';
					break;
			}
		}
		
		$sql = 'SELECT nama, tanggal, jenis_presensi, waktu, batas_waktu_presensi,
				CASE 
					WHEN (jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi) OR (jenis_presensi = "pulang" AND waktu >= batas_waktu_presensi)
						THEN "Tepat waktu"
					WHEN jenis_presensi = "masuk" AND waktu >= batas_waktu_presensi
						THEN "Terlambat masuk"
					WHEN jenis_presensi = "pulang" AND waktu <= batas_waktu_presensi
						THEN "Pulang sebelum waktunya"
					WHEN waktu IS NULL OR waktu = "" OR waktu = "00:00:00"
						THEN "Tidak absen"
				END AS status,
				CONCAT(latitude, ",", longitude) AS koordinat  
				FROM user_presensi
				LEFT JOIN user USING(id_user)
				WHERE tanggal LIKE "' . $tahun . '%" ' . $jenis_presensi . $status;
				
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function countAllDataPresensiTerbaru($tahun) {
		$sql = 'SELECT COUNT(*) as jml
				FROM user_presensi
				LEFT JOIN user USING(id_user)
				WHERE tanggal LIKE "' . $tahun . '%"';
				
		$result = $this->db->query($sql)->getRow();
		return $result->jml;
	}
	
	public function getListDataPresensiTerbaru($tahun) {

		$columns = $this->request->getPost('columns');

		// Search
		$where = ' WHERE 1=1 ';
		$search_all = @$this->request->getPost('search')['value'];
		if ($search_all) {

			foreach ($columns as $val) {
				
				if (strpos($val['data'], 'ignore') !== false)
					continue;
				
				$where_col[] = $val['data'] . ' LIKE "%' . $search_all . '%"';
			}
			 $where .= ' AND (' . join(' OR ', $where_col) . ') ';
		}
		
		// Order		
		$order_data = $this->request->getPost('order');
		$order = '';
		if (strpos($_POST['columns'][$order_data[0]['column']]['data'], 'ignore_search') === false) {
			$order_by = $columns[$order_data[0]['column']]['data'] . ' ' . strtoupper($order_data[0]['dir']);
			$order = ' ORDER BY ' . $order_by;
		}
		
		$jenis_presensi = !empty($_GET['jenis_presensi']) ? ' AND jenis_presensi = "' . $_GET['jenis_presensi'] . '"' : '';
		$status = '';
		if (!empty($_GET['jenis_presensi'])) {
			switch ($_GET['jenis_presensi']) {
				case 'tw':
					$status = ' AND ( (jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi) OR (jenis_presensi = "pulang" AND waktu >= batas_waktu_presensi) )';
					break;
				case 'tl':
					$status = ' AND jenis_presensi = "masuk" AND waktu >= batas_waktu_presensi';
					break;
				case 'psw':
					$status = ' AND jenis_presensi = "pulang" AND waktu <= batas_waktu_presensi';
					break;
				case 'ta':
					$status = ' AND waktu IS NULL OR waktu = "" OR waktu = "00:00:00"';
					break;
			}
		}

		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml_data
				FROM user_presensi
				LEFT JOIN user USING(id_user)
				' . $where . ' AND tanggal LIKE "' . $tahun . '%" ' . $jenis_presensi . $status;
				
		// echo $sql; die;
		$total_filtered = $this->db->query($sql)->getRowArray()['jml_data'];
		
		// Query Data
		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		
		$sql = '
				SELECT nama, tanggal, jenis_presensi, waktu, batas_waktu_presensi,
				CASE 
					WHEN (jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi) OR (jenis_presensi = "pulang" AND waktu >= batas_waktu_presensi)
						THEN "tepat waktu"
					WHEN jenis_presensi = "masuk" AND waktu >= batas_waktu_presensi
						THEN "terlambat"
					WHEN jenis_presensi = "pulang" AND waktu <= batas_waktu_presensi
						THEN "pulang awal"
					WHEN waktu IS NULL OR waktu = "" OR waktu = "00:00:00"
						THEN "tidak absen"
				END AS status
				FROM user_presensi
				LEFT JOIN user USING(id_user)
				' . $where . ' AND tanggal LIKE "' . $tahun . '%" ' . $jenis_presensi . $status .
				$order . ' LIMIT ' . $start . ', ' . $length;

		$data = $this->db->query($sql)->getResultArray();
				
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
	
	/**
	 * Get minimal attendance stats for current user
	 */
	public function getUserAttendanceStats($id_user, $tahun) {
		// Get basic stats: total attendance, on time, late, absent
		$start_date = $tahun . '-01-01';
		$end_date = date('Y-m-d');
		
		$sql = 'SELECT 
					COUNT(*) AS total_presensi,
					SUM(CASE WHEN jenis_presensi = "masuk" THEN 1 ELSE 0 END) AS masuk,
					SUM(CASE WHEN jenis_presensi = "pulang" THEN 1 ELSE 0 END) AS pulang,
					SUM(CASE WHEN waktu > batas_waktu_presensi THEN 1 ELSE 0 END) AS terlambat,
					SUM(CASE WHEN jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi THEN 1 ELSE 0 END) AS tepat_waktu
				FROM user_presensi
				WHERE id_user = ? AND tanggal BETWEEN ? AND ?';
		
		$result = $this->db->query($sql, [$id_user, $start_date, $end_date])->getRowArray();
		
		// Get today's attendance status
		$today = date('Y-m-d');
		$today_sql = 'SELECT jenis_presensi, waktu, batas_waktu_presensi 
					  FROM user_presensi 
					  WHERE id_user = ? AND tanggal = ?';
		$today_attendance = $this->db->query($today_sql, [$id_user, $today])->getResultArray();
		
		return [
			'stats' => $result,
			'today_attendance' => $today_attendance
		];
	}
	
	/**
	 * Get recent presensi for a user
	 */
	public function getRecentPresensi($id_user, $limit = 10) {
		$sql = 'SELECT tanggal, jenis_presensi, waktu, batas_waktu_presensi
				FROM user_presensi
				WHERE id_user = ?
				ORDER BY tanggal DESC, waktu DESC
				LIMIT ?';
		
		return $this->db->query($sql, [$id_user, $limit])->getResultArray();
	}
}