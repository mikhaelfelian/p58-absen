<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class UserCompanyModel extends \App\Models\BaseModel
{
	protected $table = 'user_company';
	protected $primaryKey = 'id_user_company';
	protected $returnType = 'object';
	protected $useSoftDeletes = false;
	protected $allowedFields = [
		'id_user', 'id_company', 'id_setting_waktu_presensi', 'tanggal_mulai', 'tanggal_selesai',
		'status', 'isPatrolRequired', 'keterangan', 'id_user_input', 'tgl_input', 
		'id_user_update', 'tgl_update', 'shift_cutoff_time', 'jam_kerja_target', 'created_at', 'updated_at'
	];
	protected $useTimestamps = true; // Enable automatic created_at and updated_at
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';

	public function __construct() {
		parent::__construct();
	}
	
	public function getCompanyByUser($id_user) {
		$sql = 'SELECT user_company.*, company.nama_company, company.latitude, company.longitude, 
					company.radius_nilai, company.radius_satuan
				FROM user_company
				LEFT JOIN company USING(id_company)
				WHERE id_user = ? AND user_company.status = "active"
				ORDER BY company.nama_company';
		return $this->db->query($sql, [$id_user])->getResult();
	}
	
	public function getActiveCompanyByUser($id_user) {
		$today = date('Y-m-d');
		
		// Simplified query - get all user_company records for this user, then filter in PHP
		// This ensures we don't miss records due to strict SQL conditions
		$sql = 'SELECT user_company.*, user_company.isPatrolRequired, user_company.id_setting_waktu_presensi,
					user_company.jam_kerja_target,
					company.id_company, company.nama_company, company.latitude, company.longitude, 
					company.radius_nilai, company.radius_satuan, company.status as company_status,
					setting_waktu_presensi.nama_setting,
					setting_waktu_presensi.waktu_masuk_awal, setting_waktu_presensi.waktu_masuk_akhir,
					setting_waktu_presensi.waktu_pulang_awal, setting_waktu_presensi.waktu_pulang_akhir,
					setting_waktu_presensi.batas_waktu_masuk, setting_waktu_presensi.batas_waktu_pulang
				FROM user_company
				LEFT JOIN company ON user_company.id_company = company.id_company
				LEFT JOIN setting_waktu_presensi ON user_company.id_setting_waktu_presensi = setting_waktu_presensi.id_setting_waktu_presensi
				WHERE user_company.id_user = ?
				ORDER BY company.nama_company';
		$all_records = $this->db->query($sql, [$id_user])->getResult();
		
		// Filter records in PHP to be more flexible
		$result = [];
		
		// Log for debugging
		log_message('debug', 'getActiveCompanyByUser: Found ' . count($all_records) . ' total user_company records for user_id: ' . $id_user);
		
		foreach ($all_records as $row) {
			// Check user_company status - accept 'active' or if status is NULL/empty, treat as active
			$user_company_active = (
				$row->status == 'active' || 
				$row->status === null || 
				empty($row->status) ||
				!isset($row->status)
			);
			
			// Check company status - accept 'active', NULL, or missing company
			// If company doesn't exist (LEFT JOIN returned NULL), still allow it
			$company_active = true; // Default to true
			if (isset($row->id_company) && $row->id_company !== null) {
				// Company exists, check its status
				$company_active = (
					$row->company_status == 'active' || 
					$row->company_status === null || 
					empty($row->company_status) ||
					!isset($row->company_status)
				);
			}
			// If company doesn't exist (id_company is NULL from LEFT JOIN), still allow it
			
			// Check date range - if dates are set, they must be valid
			$date_valid = true;
			if (!empty($row->tanggal_mulai) && $row->tanggal_mulai > $today) {
				$date_valid = false; // Start date is in the future
			}
			if (!empty($row->tanggal_selesai) && $row->tanggal_selesai < $today) {
				$date_valid = false; // End date is in the past
			}
			
			// Log each record for debugging
			log_message('debug', 'getActiveCompanyByUser: Record id_user_company=' . $row->id_user_company . 
				', id_user=' . ($row->id_user ?? 'NULL') .
				', id_company=' . ($row->id_company ?? 'NULL') .
				', status=' . ($row->status ?? 'NULL') . 
				', company_status=' . ($row->company_status ?? 'NULL') .
				', tanggal_mulai=' . ($row->tanggal_mulai ?? 'NULL') .
				', tanggal_selesai=' . ($row->tanggal_selesai ?? 'NULL') .
				', user_company_active=' . ($user_company_active ? 'YES' : 'NO') .
				', company_active=' . ($company_active ? 'YES' : 'NO') .
				', date_valid=' . ($date_valid ? 'YES' : 'NO'));
			
			// Include record if all conditions are met
			if ($user_company_active && $company_active && $date_valid) {
				$result[] = $row;
			} else {
				log_message('debug', 'getActiveCompanyByUser: EXCLUDED record id_user_company=' . $row->id_user_company . 
					' - user_company_active=' . ($user_company_active ? 'YES' : 'NO') .
					', company_active=' . ($company_active ? 'YES' : 'NO') .
					', date_valid=' . ($date_valid ? 'YES' : 'NO'));
			}
		}
		
		log_message('debug', 'getActiveCompanyByUser: Returning ' . count($result) . ' active companies for user_id: ' . $id_user);
		
		return $result;
	}
	
	public function getUserByCompany($id_company) {
		$sql = 'SELECT user_company.*, user.nama, user.nip, user.email
				FROM user_company
				LEFT JOIN user USING(id_user)
				WHERE id_company = ? AND user_company.status = "active"
				ORDER BY user.nama';
		return $this->db->query($sql, [$id_company])->getResult();
	}
	
	public function saveData($data) {
		try {
			log_message('debug', 'saveData with: ' . json_encode($data));
			
			// Set timestamps (Created and Updated at will be handled by Model's automatic timestamps)
			if (isset($data[$this->primaryKey])) {
				$data['id_user_update'] = $this->session->get('user')['id_user'];
				$data['tgl_update'] = date('Y-m-d H:i:s');
				// $data['updated_at'] will be set automatically
			} else {
				$data['id_user_input'] = $this->session->get('user')['id_user'];
				$data['tgl_input'] = date('Y-m-d H:i:s');
				// $data['created_at'] and $data['updated_at'] will be set automatically
			}
			
			// Use Model's save() - automatically handles insert/update and timestamps
			$result = $this->save($data);
			
			if (!$result) {
				$errors = $this->errors();
				return ['status' => 'error', 'message' => 'Data gagal disimpan: ' . implode(', ', $errors)];
			}
			
			$id = isset($data[$this->primaryKey]) ? $data[$this->primaryKey] : $this->getInsertID();
			return ['status' => 'ok', 'message' => 'Data berhasil disimpan', 'id' => $id];
			
		} catch (\Exception $e) {
			return ['status' => 'error', 'message' => 'Data gagal disimpan: ' . $e->getMessage()];
		}
	}
	
	public function deleteData() {
		$this->db->transStart();
		$this->db->table('user_company')->delete(['id_user_company' => $_POST['id']]);
		$this->db->transComplete();
		
		return $this->db->transStatus();
	}
	
	public function checkUserCompanyAccess($id_user, $id_company) {
		$today = date('Y-m-d');
		$sql = 'SELECT * FROM user_company 
				WHERE id_user = ? AND id_company = ? 
				AND status = "active"
				AND (tanggal_mulai IS NULL OR tanggal_mulai <= ?)
				AND (tanggal_selesai IS NULL OR tanggal_selesai >= ?)';
		$result = $this->db->query($sql, [$id_user, $id_company, $today, $today])->getRow();
		return $result ? true : false;
	}
}

