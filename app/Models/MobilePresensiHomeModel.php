<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

use CodeIgniter\Model;

class MobilePresensiHomeModel extends \App\Models\BaseModel
{
	/**
	 * Ambil presensi masuk terbaru user pada hari tertentu
	 * $date: Y-m-d
	 */
	public function getPresensiByIdUserAndDate($id_user, $date)
	{
		// Ambil presensi masuk pada tanggal/$date oleh $id_user
		$sql = "SELECT * FROM user_presensi WHERE id_user = ? AND DATE(tgl_masuk) = ? ORDER BY tgl_masuk DESC";
		return $this->db->query($sql, [$id_user, $date])->getResultArray();
	}

	/**
	 * Riwayat presensi dalam rentang tanggal, hasil diurutkan dari terbaru ke lama
	 * $start_date, $end_date: Y-m-d
	 */
	public function getRiwayatPresensi($start_date, $end_date)
	{
		$id_user = service('session')->get('user')['id_user'];
		$sql = "SELECT
					DATE(tgl_masuk) AS shift_date,
					TIME(tgl_masuk) AS presensi_masuk,
					TIME(tgl_keluar) AS presensi_pulang,
					durasi,
					is_valid,
					id_company
				FROM user_presensi
				WHERE id_user = ?
					AND DATE(tgl_masuk) >= ?
					AND DATE(tgl_masuk) <= ?
				ORDER BY tgl_masuk DESC";
		return $this->db->query($sql, [$id_user, $start_date, $end_date])->getResultArray();
	}

	/**
	 * Simpan data presensi "masuk"
	 * $data harus termasuk: id_user, id_company, location[coords][latitude,longitude], [foto]
	 */
	public function saveDataPresensi($data)
	{
		$db = $this->db;
		$builder = $db->table('user_presensi');

		$tgl_masuk   = $data['tgl_masuk'] ?? date('Y-m-d H:i:s');
		$id_user     = $data['id_user'];
		$id_company  = $data['id_company'];
		$latitude    = $data['location']['coords']['latitude'] ?? null;
		$longitude   = $data['location']['coords']['longitude'] ?? null;
		$is_valid    = $data['is_valid'] ?? 0;

		$insert_data = [
			'id_user'     => $id_user,
			'id_company'  => $id_company,
			'tgl_masuk'   => $tgl_masuk,
			'latitude'    => $latitude,
			'longitude'   => $longitude,
			'is_valid'    => $is_valid,
			'created_at'  => date('Y-m-d H:i:s')
		];

		// Jika foto disertakan, simpan file dan masukkan path ke kolom 'foto'
		if (!empty($data['foto'])) {
			// Naming: user_NAMA_ID-timestamp.jpeg
			$user = service('session')->get('user');
			$nama_file = str_replace(' ', '_', $user['nama']) . '_' . date('Ymd_His_') . gettimeofday()['usec'] . '.jpeg';
			$insert_data['foto'] = $nama_file;
			$exp = explode(',', $data['foto']);
			file_put_contents(ROOTPATH . 'public/images/presensi/' . $nama_file, base64_decode($exp[1]));
		}

		// Insert presensi "masuk"
		$builder->insert($insert_data);
		$insert_id = $db->insertID();

		if (!$insert_id) {
			return false;
		}

		return $builder->where('id_user_presensi', $insert_id)->get()->getRowArray();
	}

	/**
	 * Simpan presensi pulang (update row tgl_keluar, durasi)
	 */
	public function updateDataPresensiPulang($id_user_presensi, $tgl_keluar = null)
	{
		$db = $this->db;
		$builder = $db->table('user_presensi');
		if (!$tgl_keluar) {
			$tgl_keluar = date('Y-m-d H:i:s');
		}
		// Hitung durasi dalam jam (float)
		$sql = "SELECT tgl_masuk FROM user_presensi WHERE id_user_presensi = ?";
		$row = $db->query($sql, [$id_user_presensi])->getRowArray();
		if (!$row) {
			return false;
		}
		$tgl_masuk = $row['tgl_masuk'];
		$durasi = (strtotime($tgl_keluar) - strtotime($tgl_masuk)) / 3600;

		$data_update = [
			'tgl_keluar' => $tgl_keluar,
			'durasi'     => $durasi,
			'updated_at' => date('Y-m-d H:i:s')
		];
		$builder->where('id_user_presensi', $id_user_presensi)->update($data_update);

		return $builder->where('id_user_presensi', $id_user_presensi)->get()->getRowArray();
	}
}
?>