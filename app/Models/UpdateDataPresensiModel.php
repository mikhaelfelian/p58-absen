<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class UpdateDataPresensiModel extends \App\Models\BaseModel
{

	public function __construct() {
		parent::__construct();
	}
	
	public function getTanggalMulaiPresensi() {
		// Use new schema: get minimum date from tgl_masuk
		$sql = 'SELECT MIN(DATE(tgl_masuk)) as tanggal FROM user_presensi WHERE tgl_masuk IS NOT NULL';
		$result = $this->db->query($sql)->getRowArray();
		$tanggal = $result['tanggal'] ?? null;
		return $tanggal;
	}
	
	public function updateDataPresensi() {

		$result_setting = $this->getSetting('presensi');
		$setting = [];
		foreach ($result_setting as $val) {
			$setting[$val['param']] = $val['value'];
		}
		$hari_kerja = json_decode($setting['hari_kerja'], true);
		
		$sql = 'SELECT * FROM setting_waktu_presensi WHERE gunakan = "Y"';
		$setting_waktu = $this->db->query($sql)->getRowArray();
		if (!$setting_waktu) {
			return 0;
		}
		
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		
		// Get all users except root (id_user = 1)
		$sql = 'SELECT id_user FROM user WHERE id_user <> 1';
		$all_users = $this->db->query($sql)->getResultArray();
		
		// Get company assignments for all users
		// Get the first active company for each user (if they have multiple companies)
		$today = date('Y-m-d');
		$sql = 'SELECT DISTINCT uc.id_user, uc.id_company 
				FROM user_company uc
				INNER JOIN company c ON uc.id_company = c.id_company
				WHERE uc.status = "active" 
				AND (uc.tanggal_mulai IS NULL OR uc.tanggal_mulai <= ?)
				AND (uc.tanggal_selesai IS NULL OR uc.tanggal_selesai >= ?)
				AND c.status = "active"
				ORDER BY uc.id_user, uc.id_user_company';
		$user_companies = $this->db->query($sql, [$today, $today])->getResultArray();
		
		// Build mapping: id_user => id_company (use first company if user has multiple)
		$user_company_map = [];
		foreach ($user_companies as $uc) {
			if (!isset($user_company_map[$uc['id_user']])) {
				$user_company_map[$uc['id_user']] = $uc['id_company'];
			}
		}
		
		// Get existing presensi records for the date range
		// New schema: one record per day with tgl_masuk and optionally tgl_keluar
		$sql = 'SELECT id_user, DATE(tgl_masuk) as tanggal, tgl_masuk, tgl_keluar 
				FROM user_presensi 
				WHERE DATE(tgl_masuk) >= "' . $start_date . '" 
				AND DATE(tgl_masuk) <= "' . $end_date . '"';
		$result = $this->db->query($sql)->getResultArray();
		
		// Organize data by user and date
		// Structure: $data_presensi[id_user][tanggal] = ['has_masuk' => true/false, 'has_pulang' => true/false]
		$data_presensi = [];
		foreach ($result as $val) {
			$id_user = $val['id_user'];
			$tanggal = $val['tanggal'];
			if (!isset($data_presensi[$id_user])) {
				$data_presensi[$id_user] = [];
			}
			if (!isset($data_presensi[$id_user][$tanggal])) {
				$data_presensi[$id_user][$tanggal] = ['has_masuk' => false, 'has_pulang' => false];
			}
			// Check if this record has masuk (tgl_masuk exists)
			if (!empty($val['tgl_masuk'])) {
				$data_presensi[$id_user][$tanggal]['has_masuk'] = true;
			}
			// Check if this record has pulang (tgl_keluar exists)
			if (!empty($val['tgl_keluar'])) {
				$data_presensi[$id_user][$tanggal]['has_pulang'] = true;
			}
		}
		
		$jumlah_data = 0;
		$data_db = [];
		
		// Loop through each day in the date range
		for ($i = $start_date; $i <= $end_date; $i = date('Y-m-d', strtotime('+1 day', strtotime($i)))) 
		{
			$day = date('w', strtotime($i));
			// Only process working days
			if (in_array($day, $hari_kerja)) {
				// Check each user
				foreach ($all_users as $user) {
					$id_user = $user['id_user'];
					
					// Skip users without company assignment
					if (!isset($user_company_map[$id_user])) {
						continue; // User has no active company, skip
					}
					
					$id_company = $user_company_map[$id_user];
					
					// Check if user has presensi record for this date
					if (isset($data_presensi[$id_user][$i])) {
						$presensi = $data_presensi[$id_user][$i];
						
						// If user has a record but missing masuk
						if (!$presensi['has_masuk']) {
							// Need to create a record with tgl_masuk
							// Use the batas_waktu_masuk time for the datetime
							$tgl_masuk = $i . ' ' . $setting_waktu['batas_waktu_masuk'] . ':00';
							
							$data_db[] = [
								'id_user' => $id_user,
								'id_company' => $id_company, // Use actual company from user_company table
								'tgl_masuk' => $tgl_masuk,
								'tgl_keluar' => null,
								'durasi' => null,
								'is_valid' => 0
							];
							$jumlah_data++;
						}
						
						// If user has masuk but missing pulang
						if ($presensi['has_masuk'] && !$presensi['has_pulang']) {
							// Need to update existing record to add tgl_keluar
							// Find the record for this user and date
							$sql = 'SELECT id_user_presensi FROM user_presensi 
									WHERE id_user = ? AND DATE(tgl_masuk) = ? AND tgl_keluar IS NULL 
									LIMIT 1';
							$existing = $this->db->query($sql, [$id_user, $i])->getRowArray();
							
							if ($existing) {
								// Update existing record
								$tgl_keluar = $i . ' ' . $setting_waktu['batas_waktu_pulang'] . ':00';
								$tgl_masuk_time = $this->db->query('SELECT tgl_masuk FROM user_presensi WHERE id_user_presensi = ?', [$existing['id_user_presensi']])->getRowArray();
								if ($tgl_masuk_time) {
									$durasi = (strtotime($tgl_keluar) - strtotime($tgl_masuk_time['tgl_masuk'])) / 3600;
									$this->db->table('user_presensi')
										->where('id_user_presensi', $existing['id_user_presensi'])
										->update([
											'tgl_keluar' => $tgl_keluar,
											'durasi' => $durasi
										]);
									$jumlah_data++;
								}
							}
						}
					} else {
						// User has no presensi record for this date - create both masuk and pulang
						$tgl_masuk = $i . ' ' . $setting_waktu['batas_waktu_masuk'] . ':00';
						$tgl_keluar = $i . ' ' . $setting_waktu['batas_waktu_pulang'] . ':00';
						$durasi = (strtotime($tgl_keluar) - strtotime($tgl_masuk)) / 3600;
						
						$data_db[] = [
							'id_user' => $id_user,
							'id_company' => $id_company, // Use actual company from user_company table
							'tgl_masuk' => $tgl_masuk,
							'tgl_keluar' => $tgl_keluar,
							'durasi' => $durasi,
							'is_valid' => 0
						];
						$jumlah_data++;
					}
				}
			}
		}
		
		// Insert new records
		if ($data_db) {
			$this->db->table('user_presensi')->insertBatch($data_db);
		}
		
		// Update last update date
		$this->db->table('setting')->update(['value' => date('Y-m-d')], ['param' => 'last_update_data_presensi']);
		return $jumlah_data;

	}
}
?>