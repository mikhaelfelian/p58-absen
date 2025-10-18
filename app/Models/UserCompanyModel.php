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
		'id_user', 'id_company', 'tanggal_mulai', 'tanggal_selesai',
		'status', 'keterangan', 'id_user_input', 'tgl_input', 
		'id_user_update', 'tgl_update'
	];
	
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
		$sql = 'SELECT user_company.*, company.*
				FROM user_company
				LEFT JOIN company USING(id_company)
				WHERE user_company.id_user = ? 
				AND user_company.status = "active"
				AND (company.status = "active" OR company.status IS NULL)
				AND (user_company.tanggal_mulai IS NULL OR user_company.tanggal_mulai <= ?)
				AND (user_company.tanggal_selesai IS NULL OR user_company.tanggal_selesai >= ?)
				ORDER BY company.nama_company';
		return $this->db->query($sql, [$id_user, $today, $today])->getResult();
	}
	
	public function getUserByCompany($id_company) {
		$sql = 'SELECT user_company.*, user.nama, user.nip, user.email
				FROM user_company
				LEFT JOIN user USING(id_user)
				WHERE id_company = ? AND user_company.status = "active"
				ORDER BY user.nama';
		return $this->db->query($sql, [$id_company])->getResult();
	}
	
	public function saveData() {
		$fields = ['id_user', 'id_company', 'tanggal_mulai', 'tanggal_selesai', 'status', 'keterangan'];
		
		$data_db = [];
		foreach ($fields as $field) {
			if (isset($_POST[$field])) {
				$value = $this->request->getPost($field);
				$data_db[$field] = $value === '' ? null : $value;
			}
		}
		
		$this->db->transStart();
		
		if ($this->request->getPost('id')) {
			$data_db['id_user_update'] = $this->session->get('user')['id_user'];
			$data_db['tgl_update'] = date('Y-m-d H:i:s');
			$save = $this->db->table('user_company')->update($data_db, ['id_user_company' => $_POST['id']]);
			$id = $_POST['id'];
		} else {
			$data_db['id_user_input'] = $this->session->get('user')['id_user'];
			$data_db['tgl_input'] = date('Y-m-d H:i:s');
			$save = $this->db->table('user_company')->insert($data_db);
			$id = $this->db->insertID();
		}
		
		$this->db->transComplete();
		
		if ($this->db->transStatus() === false) {
			return ['status' => 'error', 'message' => 'Data gagal disimpan'];
		}
		
		return ['status' => 'ok', 'message' => 'Data berhasil disimpan', 'id' => $id];
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

