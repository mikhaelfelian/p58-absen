<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class PresensiRekapModel extends \App\Models\BaseModel
{
	public function getAllUser() {
		// Tampilkan semua pegawai tanpa pembatasan hak akses,
		// karena dropdown di laporan rekap presensi harus bisa memilih siapa saja.
		$sql = 'SELECT * FROM user';
		$result = $this->db->query($sql)->getResultArray();
		
		return $result;
	}
	
	public function getPresensiByMonth($month, $year, $id_company = null) 
	{
		// User filter: use explicit GET id_user when provided, otherwise no restriction
		$id_user_filter = !empty($_GET['id_user']) ? ' AND up.id_user = ' . intval($_GET['id_user']) : '';

		// Filter company if provided
		$company_filter = '';
		if (!empty($id_company)) {
			$company_filter = ' AND up.id_company = ' . intval($id_company);
		}

		// Range tanggal bulan
		$num_day = date('t', strtotime($year . '-' . $month . '-01'));
		$start_date = $year . '-' . $month . '-01';
		$end_date   = $year . '-' . $month . '-' . $num_day;

		$sql = '
			SELECT 
				up.id_user,
				DAY(up.tgl_masuk) AS `day`,
				CASE
					-- Hadir masuk & pulang, durasi memenuhi target -> V (valid)
					WHEN up.tgl_masuk IS NOT NULL 
					     AND up.tgl_keluar IS NOT NULL 
					     AND COALESCE(up.is_valid, 0) = 1
						THEN "v"
					-- Hadir masuk & pulang, tapi tidak memenuhi target jam kerja -> PSW
					WHEN up.tgl_masuk IS NOT NULL 
					     AND up.tgl_keluar IS NOT NULL 
					     AND COALESCE(up.is_valid, 0) = 0
						THEN "psw"
					-- Masuk ada, pulang tidak tercatat -> TAP (Tidak Absen Pulang)
					WHEN up.tgl_masuk IS NOT NULL 
					     AND up.tgl_keluar IS NULL
						THEN "tap"
					ELSE NULL
				END AS status
			FROM user_presensi up
			LEFT JOIN user u ON up.id_user = u.id_user
			WHERE DATE(up.tgl_masuk) >= ' . $this->db->escape($start_date) . '
			  AND DATE(up.tgl_masuk) <= ' . $this->db->escape($end_date) . '
			  ' . $id_user_filter . $company_filter;

		$result = $this->db->query($sql)->getResultArray();

		return $result;
	}
	
	public function writeExcel($start_date, $end_date) 
	{
		require_once(ROOTPATH . "/app/ThirdParty/PHPXlsxWriter/xlsxwriter.class.php");
		$query = $this->getUserPresensiByDate($start_date, $end_date);
		
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
		// while ($row = $query->getUnbufferedRow('array')) {
		foreach ($query as $row) {
			array_unshift($row, $no);
			$writer->writeSheetRow($sheet_name, $row);
			$no++;
		}
		
		$tmp_file = ROOTPATH . 'public/tmp/detail_presensi_' . time() . '.xlsx.tmp';
		$writer->writeToFile($tmp_file);
		return $tmp_file;
	}
	
	public function getUserPresensiById($id) {
		$sql = 'SELECT * FROM user_presensi WHERE id = ?';
		$result = $this->db->query($sql, $id)->getRowArray();
		return $result;
	}
	
	public function getUserPresensiByDate($start_date, $end_date) {
		if (has_permission('read_own')) {
			$id_user = ' AND id_user = ' . $_SESSION['user']['id_user'];
		} else {
			$id_user = !empty($_GET['id_user']) ? ' AND id_user = ' . $_GET['id_user'] : '';
		}
		$jenis_presensi = $_GET['jenis_presensi'] ? ' AND jenis_presensi = "' . $_GET['jenis_presensi'] . '"' : '';
		$status = '';
		if ($_GET['jenis_presensi']) {
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
				END AS status,
				CONCAT(latitude, ",", longitude) AS koordinat  
				FROM user_presensi
				LEFT JOIN user USING(id_user)
				WHERE tanggal >= ? AND tanggal <= ? ' . $id_user . $jenis_presensi . $status;
				
		$result = $this->db->query($sql, [$start_date, $end_date])->getResultArray();
		return $result;
	}
	
	public function deleteData($id) {
		$delete = $this->db->table('user_presensi')->delete(['id' => $id]);
		return $delete;
	}
	
	public function saveData($id = null) {
		$data_db = [];
		$query_result = $this->getSetting('presensi');
		$setting = [];
		foreach ($query_result as $val) {
			$setting[$val['param']] = $val['value'];
		}
		if ($_POST['jenis_presensi'] == 'masuk') {
			$data_db['batas_waktu_presensi'] = $setting['batas_waktu_masuk'];
		} else {
			$data_db['batas_waktu_presensi'] = $setting['batas_waktu_pulang'];
		}
		
		$data_db['id_user'] = $_POST['id_user'];
		list($d, $m, $y) = explode('-', $_POST['tanggal']);
		$data_db['tanggal'] = $y . '-' . $m . '-' . $d;
	
		$data_db['waktu'] = $_POST['waktu_jam'] . ':' . $_POST['waktu_menit'] . ':' . $_POST['waktu_detik'];
		$data_db['jenis_presensi'] = $_POST['jenis_presensi'];
		$data_db['latitude'] = $_POST['latitude'];
		$data_db['longitude'] = $_POST['longitude'];
		
		$path = ROOTPATH . 'public/images/presensi/';
		$error_message = '';
		$file = $this->request->getFile('foto');
	
		if ($_POST['id']) {
			if ( ($_POST['jenis_foto'] == 'upload' && $file->getName()) 
					|| ($_POST['jenis_foto'] == 'webcam' && !empty($_POST['foto_raw']))
					|| $_POST['foto_delete_img'] == 1) {
				

				$sql = 'SELECT foto FROM user_presensi WHERE id = ?';
				$img_db = $this->db->query($sql, $_POST['id'])->getRowArray();
				if ($img_db['foto']) {
					$del = delete_file($path . $img_db['foto']);
					if ($del) {
						$data_db['foto'] = '';
					} else {
						$error_message = 'Gagal menghapus gambar lama';
					}
				}
			}
		}
		
		if (!$error_message) {
			$nama_file = str_replace(' ', '_', session()->get('user')['nama']) . '_' . date('Ymd_His_') . gettimeofday()['usec'] . '.jpeg';
		
			if ($_POST['jenis_foto'] == 'webcam') {
				
				if (!empty($_POST['foto_raw'])) {
					$exp = explode(',', $_POST['foto_raw']);
					$save_file = file_put_contents($path . $nama_file, base64_decode($exp[1]));
					if ($save_file) {
						$data_db['foto'] = $nama_file;
					} else {
						$error_message = 'Gagal menyimpan foto kamera';
					}
				}
			} else {
				
				if ($file && $file->getName()) {
					
					$file->move($path, $nama_file);
						
					if ($file->hasMoved()) {
						$data_db['foto'] = $nama_file;
					} else {
						$error_message = 'Gagal menyimpan foto yang diupload';
					}
				}
			}
		}

		if ($error_message) {
			return ['status' => 'error', 'message' => $error_message];
		}
		
		if ($id) {
			$data_db['id_user_update'] = session()->get('user')['id_user'];
			$data_db['tgl_update'] = date('Y-m-d');
			$query = $this->db->table('user_presensi')->update($data_db, ['id' => $id]);
		} else {
			$data_db['id_user_input'] = session()->get('user')['id_user'];
			$data_db['tgl_input'] = date('Y-m-d');
			$query = $this->db->table('user_presensi')->insert($data_db);
			$id = $this->db->insertID();
		}
		
		if ($query) {
			return ['status' => 'ok', 'message' => 'Data berhasil disimpan', 'id' => $id];
		} else {
			return ['status' => 'error', 'message' => 'Data gagal disimpan'];
		}
	}
	
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
		$where = ' WHERE tanggal >= ? AND tanggal <= ? ' . $user;
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
		
		if (!empty($_GET['status'])) {
			switch ($_GET['status']) {
				case 'tepat_waktu':
					$where .= ' AND ( 
						(jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi) 
							OR 
						(jenis_presensi = "pulang" AND waktu >= batas_waktu_presensi)
					)';
					break;
				case 'terlambat_masuk':
					$where .= ' AND jenis_presensi = "masuk" AND waktu > batas_waktu_presensi';
					break;
				case 'pulang_sebelum_waktunya':
					$where .= ' AND jenis_presensi = "pulang" AND waktu < batas_waktu_presensi';
					break;
				case 'terlambat_masuk_dan_pulang_sebelum_waktunya':
					$where .= ' AND (
						( jenis_presensi = "masuk" AND waktu > batas_waktu_presensi )
						OR 
						( jenis_presensi = "pulang" AND waktu < batas_waktu_presensi)
					)';
					break;
			}
		}
		
		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml
				FROM user_presensi
				LEFT JOIN user USING(id_user)' . $where;
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
		$sql = 'SELECT *,
					CASE 
						WHEN jenis_presensi = "masuk" AND waktu > batas_waktu_presensi
								THEN "Terlambat masuk"
						WHEN jenis_presensi = "masuk" AND waktu <= batas_waktu_presensi 
								THEN "Tepat waktu"
						WHEN jenis_presensi = "pulang" AND waktu < batas_waktu_presensi 
								THEN "Pulang sebelum waktunya"
						WHEN jenis_presensi = "pulang" AND waktu >= batas_waktu_presensi 
								THEN "Tepat waktu"
					END AS status
				FROM user_presensi
				LEFT JOIN user USING(id_user)
				' . $where . $order . ' LIMIT ' . $start . ', ' . $length;
		// echo $sql; die;
		$data = $this->db->query($sql, [$_GET['start_date'], $_GET['end_date']])->getResultArray();
		
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
}
?>