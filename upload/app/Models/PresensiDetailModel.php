<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class PresensiDetailModel extends \App\Models\BaseModel
{
	public function getAllUser() {
		
		// Tampilkan semua user kecuali root (id = 1), tanpa pembatasan permission.
		$sql = 'SELECT * FROM user WHERE id_user <> 1';
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
		// Sesuaikan dengan primary key baru tabel user_presensi (id)
		$sql = 'SELECT * FROM user_presensi WHERE id = ?';
		$result = $this->db->query($sql, $id)->getRowArray();
		return $result;
	}
	
	public function getUserPresensiByDate($start_date, $end_date) 
	{
		// Filter user: only by explicit GET id_user when provided (no permission restriction)
		$id_user = !empty($_GET['id_user']) ? ' AND t.id_user = ' . intval($_GET['id_user']) : '';

		// Filter jenis presensi (masuk / pulang)
		$jenis_presensi = !empty($_GET['jenis_presensi']) ? ' AND t.jenis_presensi = "' . $this->db->escapeString($_GET['jenis_presensi']) . '"' : '';

		// Status filter: only two logical states - Masuk & Pulang Belum Waktu
		$status = '';
		if (!empty($_GET['status'])) {
			switch ($_GET['status']) {
				case 'masuk':
					$status = ' AND t.jenis_presensi = "masuk"';
					break;
				case 'pulang_belum_waktu':
					$status = ' AND t.jenis_presensi = "pulang" AND t.is_valid = 0';
					break;
			}
		}
		
		// Bangun data presensi dari skema baru user_presensi (per shift)
		$sql = '
			SELECT 
				t.nama,
				t.tanggal,
				t.jenis_presensi,
				t.waktu,
				NULL AS batas_waktu_presensi,
				CASE 
					WHEN t.jenis_presensi = "masuk" THEN "Masuk"
					WHEN t.jenis_presensi = "pulang" AND t.is_valid = 0 THEN "Pulang Belum Waktu"
					ELSE ""
				END AS status,
				CONCAT(t.latitude, ",", t.longitude) AS koordinat  
			FROM (
				-- Presensi masuk
				SELECT 
					up.id               AS id,
					up.id_user,
					u.nama,
					DATE(up.tgl_masuk) AS tanggal,
					"masuk"            AS jenis_presensi,
					TIME(up.tgl_masuk) AS waktu,
					up.is_valid,
					up.latitude,
					up.longitude
				FROM user_presensi up
				LEFT JOIN user u ON up.id_user = u.id_user
				WHERE up.tgl_masuk IS NOT NULL
				
				UNION ALL
				
				-- Presensi pulang
				SELECT 
					up.id               AS id,
					up.id_user,
					u.nama,
					DATE(up.tgl_keluar) AS tanggal,
					"pulang"            AS jenis_presensi,
					TIME(up.tgl_keluar) AS waktu,
					up.is_valid,
					up.latitude,
					up.longitude
				FROM user_presensi up
				LEFT JOIN user u ON up.id_user = u.id_user
				WHERE up.tgl_keluar IS NOT NULL
			) AS t
			WHERE t.tanggal >= ? AND t.tanggal <= ?
			' . $id_user . $jenis_presensi . $status . '
			ORDER BY t.tanggal ASC, t.nama ASC, t.jenis_presensi ASC
		';
				
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
		// Count over all users, optionally filtered by explicit GET id_user
		$user = !empty($_GET['id_user']) ? ' AND t.id_user = ' . intval($_GET['id_user']) : '';

		$start_date = $_GET['start_date'];
		$end_date   = $_GET['end_date'];

		$sql = '
			SELECT COUNT(*) AS jml
			FROM (
				SELECT 
					up.id_user,
					DATE(up.tgl_masuk) AS tanggal
				FROM user_presensi up
				WHERE up.tgl_masuk IS NOT NULL
				
				UNION ALL
				
				SELECT 
					up.id_user,
					DATE(up.tgl_keluar) AS tanggal
				FROM user_presensi up
				WHERE up.tgl_keluar IS NOT NULL
			) AS t
			WHERE t.tanggal >= ? AND t.tanggal <= ?
			' . $user;

		$result = $this->db->query($sql, [$start_date, $end_date])->getRow();
		return $result ? $result->jml : 0;
	}
	
	public function getListPresensi() 
	{

		$columns = $this->request->getPost('columns');

		// Filter user & jenis presensi (no permission restriction)
		$user = !empty($_GET['id_user']) ? ' AND t.id_user = ' . intval($_GET['id_user']) : '';
		$jenis_presensi = !empty($_GET['jenis_presensi']) ? ' AND t.jenis_presensi = "' . $this->db->escapeString($_GET['jenis_presensi']) . '"' : '';
		
		$search_all = @$this->request->getPost('search')['value'];
		$where = ' WHERE t.tanggal >= ? AND t.tanggal <= ? ' . $user . $jenis_presensi;
		if ($search_all) {
			foreach ($columns as $val) {
				
				if (strpos($val['data'], 'ignore_search') !== false) 
					continue;
				
				if (strpos($val['data'], 'ignore') !== false)
					continue;
				
				$where_col[] = 't.' . $val['data'] . ' LIKE "%' . $this->db->escapeString($search_all) . '%"';
			}
			 $where .= ' AND (' . join(' OR ', $where_col) . ') ';
		}
		
		if (!empty($_GET['status'])) {
			switch ($_GET['status']) {
				case 'masuk':
					$where .= ' AND t.jenis_presensi = "masuk"';
					break;
				case 'pulang_belum_waktu':
					$where .= ' AND t.jenis_presensi = "pulang" AND t.is_valid = 0';
					break;
			}
		}
		
		// Subquery dari skema baru user_presensi
		$baseSubquery = '
			FROM (
				SELECT 
					up.id               AS id,
					up.id_user,
					u.nama,
					up.foto,
					DATE(up.tgl_masuk) AS tanggal,
					"masuk"            AS jenis_presensi,
					TIME(up.tgl_masuk) AS waktu,
					up.is_valid,
					up.latitude,
					up.longitude
				FROM user_presensi up
				LEFT JOIN user u ON up.id_user = u.id_user
				WHERE up.tgl_masuk IS NOT NULL
				
				UNION ALL
				
				SELECT 
					up.id               AS id,
					up.id_user,
					u.nama,
					up.foto,
					DATE(up.tgl_keluar) AS tanggal,
					"pulang"            AS jenis_presensi,
					TIME(up.tgl_keluar) AS waktu,
					up.is_valid,
					up.latitude,
					up.longitude
				FROM user_presensi up
				LEFT JOIN user u ON up.id_user = u.id_user
				WHERE up.tgl_keluar IS NOT NULL
			) AS t ';
		
		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml ' . $baseSubquery . $where;
		$data = $this->db->query($sql, [$_GET['start_date'], $_GET['end_date']])->getRowArray();
		$total_filtered = $data['jml'];
		
		// Order (guard against missing DataTables order payload)
		$order_data = (array) $this->request->getPost('order');
		$order = '';
		if (!empty($order_data) && isset($order_data[0]['column'])) {
			$colIndex = (int) $order_data[0]['column'];
			if (isset($columns[$colIndex]) && strpos($columns[$colIndex]['data'], 'ignore_search') === false) {
				$dir = isset($order_data[0]['dir']) ? strtoupper($order_data[0]['dir']) : 'ASC';
				$order_by = 't.' . $columns[$colIndex]['data'] . ' ' . $dir;
				$order = ' ORDER BY ' . $order_by;
			}
		}

		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		
		// Query Data, with simplified statuses:
		// - 'Masuk' for all clock-in records
		// - 'Pulang Belum Waktu' for clock-out records that are not valid
		$sql = 'SELECT 
					t.*,
					CASE 
						WHEN t.jenis_presensi = \'masuk\' THEN \'Masuk\'
						WHEN t.jenis_presensi = \'pulang\' AND t.is_valid = 0 THEN \'Pulang Belum Waktu\'
						ELSE \'\'
					END AS status
				' . $baseSubquery . $where . $order . ' LIMIT ' . $start . ', ' . $length;

		$data = $this->db->query($sql, [$_GET['start_date'], $_GET['end_date']])->getResultArray();
		
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
}
?>