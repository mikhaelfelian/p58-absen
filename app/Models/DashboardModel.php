<?php
namespace App\Models;

class DashboardModel extends \App\Models\BaseModel
{
	public function __construct() {
		parent::__construct();
	}
	
	public function getListTahun() 
	{
		$sql= 'SELECT YEAR(tgl_masuk) AS tahun
				FROM user_presensi
				GROUP BY tahun';
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function getJumlahDataPresensi($tahun) {
		$sql = 'SELECT COUNT(*) AS jml FROM user_presensi WHERE DATE(tgl_masuk) LIKE "' . $tahun . '%"';
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
					SELECT user_presensi.id_user, user.nama,
						CASE
							WHEN TIME(user_presensi.tgl_masuk) > setting_waktu_presensi.batas_waktu_masuk
								THEN 0
							WHEN user_presensi.tgl_keluar IS NOT NULL AND TIME(user_presensi.tgl_keluar) < setting_waktu_presensi.batas_waktu_pulang
								THEN 0
							WHEN user_presensi.tgl_masuk IS NULL
								THEN 0
							ELSE 1
						END
						AS status_absen
					FROM user_presensi
					LEFT JOIN user ON user_presensi.id_user = user.id_user
					LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
						AND user_presensi.id_company = user_company.id_company
					LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
					WHERE DATE(user_presensi.tgl_masuk) >= "' . $start_date . '" AND DATE(user_presensi.tgl_masuk) <= "' . $end_date. '"
				) AS tabel_rekap
				GROUP BY id_user, nama
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
			$sql = 'SELECT user_presensi.*, user.nama, MONTH(tgl_masuk) AS bulan,
							CASE
								WHEN user_presensi.tgl_masuk IS NULL THEN "tidak_absen"
								WHEN TIME(user_presensi.tgl_masuk) > setting_waktu_presensi.batas_waktu_masuk THEN "terlambat_masuk"
								WHEN user_presensi.tgl_keluar IS NOT NULL AND TIME(user_presensi.tgl_keluar) < setting_waktu_presensi.batas_waktu_pulang THEN "pulang_sebelum_waktunya"
								ELSE "tepat_waktu"
							END AS status,
							"masuk" AS jenis_presensi,
							TIME(user_presensi.tgl_masuk) AS waktu,
							setting_waktu_presensi.batas_waktu_masuk AS batas_waktu_presensi
					FROM user_presensi
					LEFT JOIN user ON user_presensi.id_user = user.id_user
					LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
						AND user_presensi.id_company = user_company.id_company
					LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
					WHERE DATE(tgl_masuk) LIKE "' . $tahun . '%"
					UNION ALL
					SELECT user_presensi.*, user.nama, MONTH(tgl_masuk) AS bulan,
							CASE
								WHEN user_presensi.tgl_keluar IS NULL THEN "tidak_absen"
								WHEN TIME(user_presensi.tgl_keluar) < setting_waktu_presensi.batas_waktu_pulang THEN "pulang_sebelum_waktunya"
								ELSE "tepat_waktu"
							END AS status,
							"pulang" AS jenis_presensi,
							TIME(user_presensi.tgl_keluar) AS waktu,
							setting_waktu_presensi.batas_waktu_pulang AS batas_waktu_presensi
					FROM user_presensi
					LEFT JOIN user ON user_presensi.id_user = user.id_user
					LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
						AND user_presensi.id_company = user_company.id_company
					LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
					WHERE DATE(tgl_masuk) LIKE "' . $tahun . '%" AND user_presensi.tgl_keluar IS NOT NULL';

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
		$jenis_presensi_filter = '';
		$status_filter = '';
		
		if (!empty($_GET['jenis_presensi'])) {
			switch ($_GET['jenis_presensi']) {
				case 'tepat_waktu':
					$status_filter = ' AND ( (jenis_presensi = "masuk" AND TIME(tgl_masuk) <= batas_waktu_masuk) OR (jenis_presensi = "pulang" AND TIME(tgl_keluar) >= batas_waktu_pulang) )';
					break;
				case 'terlambat_masuk':
					$jenis_presensi_filter = ' AND jenis_presensi = "masuk"';
					$status_filter = ' AND TIME(tgl_masuk) > batas_waktu_masuk';
					break;
				case 'pulang_sebelum_waktunya':
					$jenis_presensi_filter = ' AND jenis_presensi = "pulang"';
					$status_filter = ' AND TIME(tgl_keluar) < batas_waktu_pulang';
					break;
				case 'terlambat_masuk_dan_pulang_sebelum_waktunya':
					$status_filter = ' AND ( (jenis_presensi = "masuk" AND TIME(tgl_masuk) > batas_waktu_masuk) OR (jenis_presensi = "pulang" AND TIME(tgl_keluar) < batas_waktu_pulang) )';
					break;
			}
		}
		
		$sql = 'SELECT nama, DATE(tgl_masuk) AS tanggal, jenis_presensi, waktu, batas_waktu_presensi, status, koordinat
				FROM (
					SELECT user.nama, user_presensi.tgl_masuk, user_presensi.tgl_keluar,
							DATE(user_presensi.tgl_masuk) AS tanggal,
							"masuk" AS jenis_presensi,
							TIME(user_presensi.tgl_masuk) AS waktu,
							setting_waktu_presensi.batas_waktu_masuk AS batas_waktu_presensi,
							CASE 
								WHEN TIME(user_presensi.tgl_masuk) <= setting_waktu_presensi.batas_waktu_masuk
									THEN "Tepat waktu"
								ELSE "Terlambat masuk"
							END AS status,
							CONCAT(user_presensi.latitude, ",", user_presensi.longitude) AS koordinat
					FROM user_presensi
					LEFT JOIN user ON user_presensi.id_user = user.id_user
					LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
						AND user_presensi.id_company = user_company.id_company
					LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
					WHERE DATE(user_presensi.tgl_masuk) LIKE "' . $tahun . '%"
					UNION ALL
					SELECT user.nama, user_presensi.tgl_masuk, user_presensi.tgl_keluar,
							DATE(user_presensi.tgl_masuk) AS tanggal,
							"pulang" AS jenis_presensi,
							TIME(user_presensi.tgl_keluar) AS waktu,
							setting_waktu_presensi.batas_waktu_pulang AS batas_waktu_presensi,
							CASE 
								WHEN TIME(user_presensi.tgl_keluar) >= setting_waktu_presensi.batas_waktu_pulang
									THEN "Tepat waktu"
								ELSE "Pulang sebelum waktunya"
							END AS status,
							CONCAT(user_presensi.latitude, ",", user_presensi.longitude) AS koordinat
					FROM user_presensi
					LEFT JOIN user ON user_presensi.id_user = user.id_user
					LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
						AND user_presensi.id_company = user_company.id_company
					LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
					WHERE DATE(user_presensi.tgl_masuk) LIKE "' . $tahun . '%" AND user_presensi.tgl_keluar IS NOT NULL
				) AS combined_data
				WHERE 1=1 ' . $jenis_presensi_filter . $status_filter;
				
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function countAllDataPresensiTerbaru($tahun) {
		$sql = 'SELECT COUNT(*) as jml
				FROM user_presensi
				LEFT JOIN user USING(id_user)
				WHERE DATE(tgl_masuk) LIKE "' . $tahun . '%"';
				
		$result = $this->db->query($sql)->getRow();
		return $result->jml;
	}
	
	public function getListDataPresensiTerbaru($tahun) {

		$columns = $this->request->getPost('columns');

		// Build base query with UNION for masuk and pulang
		$base_query = '(
			SELECT user.nama, DATE(user_presensi.tgl_masuk) AS tanggal,
					"masuk" AS jenis_presensi,
					TIME(user_presensi.tgl_masuk) AS waktu,
					setting_waktu_presensi.batas_waktu_masuk AS batas_waktu_presensi,
					CASE 
						WHEN TIME(user_presensi.tgl_masuk) <= setting_waktu_presensi.batas_waktu_masuk
							THEN "tepat waktu"
						ELSE "terlambat"
					END AS status
			FROM user_presensi
			LEFT JOIN user ON user_presensi.id_user = user.id_user
			LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
				AND user_presensi.id_company = user_company.id_company
			LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
			WHERE DATE(user_presensi.tgl_masuk) LIKE "' . $tahun . '%"
			UNION ALL
			SELECT user.nama, DATE(user_presensi.tgl_masuk) AS tanggal,
					"pulang" AS jenis_presensi,
					TIME(user_presensi.tgl_keluar) AS waktu,
					setting_waktu_presensi.batas_waktu_pulang AS batas_waktu_presensi,
					CASE 
						WHEN TIME(user_presensi.tgl_keluar) >= setting_waktu_presensi.batas_waktu_pulang
							THEN "tepat waktu"
						ELSE "pulang awal"
					END AS status
			FROM user_presensi
			LEFT JOIN user ON user_presensi.id_user = user.id_user
			LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
				AND user_presensi.id_company = user_company.id_company
			LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
			WHERE DATE(user_presensi.tgl_masuk) LIKE "' . $tahun . '%" AND user_presensi.tgl_keluar IS NOT NULL
		) AS combined_data';

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
		$order = '';
		if (!empty($_POST['order']) && !empty($_POST['columns'])) {
			$order_data = $this->request->getPost('order');
			if (strpos($_POST['columns'][$order_data[0]['column']]['data'], 'ignore_search') === false) {
				$order_by = $columns[$order_data[0]['column']]['data'] . ' ' . strtoupper($order_data[0]['dir']);
				$order = ' ORDER BY ' . $order_by;
			}
		}
		
		$jenis_presensi_filter = '';
		$status_filter = '';
		if (!empty($_GET['jenis_presensi'])) {
			switch ($_GET['jenis_presensi']) {
				case 'tw':
					$status_filter = ' AND status = "tepat waktu"';
					break;
				case 'tl':
					$jenis_presensi_filter = ' AND jenis_presensi = "masuk"';
					$status_filter = ' AND status = "terlambat"';
					break;
				case 'psw':
					$jenis_presensi_filter = ' AND jenis_presensi = "pulang"';
					$status_filter = ' AND status = "pulang awal"';
					break;
				case 'ta':
					$status_filter = ' AND waktu IS NULL';
					break;
			}
		}

		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml_data FROM ' . $base_query . $where . $jenis_presensi_filter . $status_filter;
		$total_filtered = $this->db->query($sql)->getRowArray()['jml_data'];
		
		// Query Data
		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		
		$sql = 'SELECT nama, tanggal, jenis_presensi, waktu, batas_waktu_presensi, status
				FROM ' . $base_query . 
				$where . $jenis_presensi_filter . $status_filter .
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
					SUM(CASE WHEN user_presensi.tgl_masuk IS NOT NULL THEN 1 ELSE 0 END) AS masuk,
					SUM(CASE WHEN user_presensi.tgl_keluar IS NOT NULL THEN 1 ELSE 0 END) AS pulang,
					SUM(CASE WHEN TIME(user_presensi.tgl_masuk) > setting_waktu_presensi.batas_waktu_masuk THEN 1 ELSE 0 END) AS terlambat,
					SUM(CASE WHEN TIME(user_presensi.tgl_masuk) <= setting_waktu_presensi.batas_waktu_masuk THEN 1 ELSE 0 END) AS tepat_waktu
				FROM user_presensi
				LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
					AND user_presensi.id_company = user_company.id_company
				LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
				WHERE user_presensi.id_user = ? AND DATE(user_presensi.tgl_masuk) BETWEEN ? AND ?';
		
		$result = $this->db->query($sql, [$id_user, $start_date, $end_date])->getRowArray();
		
		// Get latest attendance record (no date filtering - uses latest record only)
		$userPresensiModel = new \App\Models\UserPresensiModel();
		$last_presensi = $userPresensiModel->getLastPresensi($id_user);
		
		// Format latest attendance for view compatibility
		$current_attendance = [];
		if ($last_presensi) {
			// If latest record has masuk without keluar, user is in active shift
			if (!empty($last_presensi['tgl_masuk']) && empty($last_presensi['tgl_keluar'])) {
				// Get time limits for this record
				$sql_limit = 'SELECT setting_waktu_presensi.batas_waktu_masuk
							FROM user_presensi
							LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
								AND user_presensi.id_company = user_company.id_company
							LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
							WHERE user_presensi.id = ?';
				$limit_result = $this->db->query($sql_limit, [$last_presensi['id']])->getRowArray();
				
				$current_attendance[] = [
					'jenis_presensi' => 'masuk',
					'waktu' => date('H:i:s', strtotime($last_presensi['tgl_masuk'])),
					'batas_waktu_presensi' => $limit_result['batas_waktu_masuk'] ?? null,
					'tanggal' => date('Y-m-d', strtotime($last_presensi['tgl_masuk'])),
					'is_active_shift' => true
				];
			}
			// If latest record has keluar, show pulang info
			else if (!empty($last_presensi['tgl_keluar'])) {
				// Get time limits for this record
				$sql_limit = 'SELECT setting_waktu_presensi.batas_waktu_pulang
							FROM user_presensi
							LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
								AND user_presensi.id_company = user_company.id_company
							LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
							WHERE user_presensi.id = ?';
				$limit_result = $this->db->query($sql_limit, [$last_presensi['id']])->getRowArray();
				
				$current_attendance[] = [
					'jenis_presensi' => 'pulang',
					'waktu' => date('H:i:s', strtotime($last_presensi['tgl_keluar'])),
					'batas_waktu_presensi' => $limit_result['batas_waktu_pulang'] ?? null,
					'tanggal' => date('Y-m-d', strtotime($last_presensi['tgl_masuk'])),
					'is_active_shift' => false
				];
			}
		} 
		
		return [
			'stats' => $result,
			'current_attendance' => $current_attendance,
			'last_presensi' => $last_presensi // Include full record for controller use
		];
	}
	
	/**
	 * Get recent presensi for a user
	 */
	public function getRecentPresensi($id_user, $limit = 10) {
		$sql = 'SELECT tanggal, jenis_presensi, waktu, batas_waktu_presensi
				FROM (
					SELECT DATE(user_presensi.tgl_masuk) AS tanggal,
							"masuk" AS jenis_presensi,
							TIME(user_presensi.tgl_masuk) AS waktu,
							setting_waktu_presensi.batas_waktu_masuk AS batas_waktu_presensi
					FROM user_presensi
					LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
						AND user_presensi.id_company = user_company.id_company
					LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
					WHERE user_presensi.id_user = ?
					UNION ALL
					SELECT DATE(user_presensi.tgl_masuk) AS tanggal,
							"pulang" AS jenis_presensi,
							TIME(user_presensi.tgl_keluar) AS waktu,
							setting_waktu_presensi.batas_waktu_pulang AS batas_waktu_presensi
					FROM user_presensi
					LEFT JOIN user_company ON user_presensi.id_user = user_company.id_user 
						AND user_presensi.id_company = user_company.id_company
					LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
					WHERE user_presensi.id_user = ? AND user_presensi.tgl_keluar IS NOT NULL
				) AS combined_data
				ORDER BY tanggal DESC, jenis_presensi DESC
				LIMIT ?';
		
		return $this->db->query($sql, [$id_user, $id_user, $limit])->getResultArray();
	}
}