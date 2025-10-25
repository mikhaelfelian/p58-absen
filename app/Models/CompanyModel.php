<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class CompanyModel extends \App\Models\BaseModel
{
	protected $table = 'company';
	protected $primaryKey = 'id_company';
	protected $returnType = 'object';
	protected $useSoftDeletes = false;
	protected $allowedFields = [
		'nama_company', 'alamat', 'id_wilayah_kelurahan', 
		'latitude', 'longitude', 'radius_nilai', 'radius_satuan',
		'email', 'no_telp', 'contact_person', 'status', 'keterangan', 'setting',
		'id_user_input', 'tgl_input', 'id_user_update', 'tgl_update'
	];
	
	public function __construct() {
		parent::__construct();
	}
	
	public function getAllCompanies() {
		$sql = 'SELECT company.*, 
					wilayah_kelurahan.nama_kelurahan,
					wilayah_kecamatan.nama_kecamatan,
					wilayah_kabupaten.nama_kabupaten,
					wilayah_propinsi.nama_propinsi
				FROM company
				LEFT JOIN wilayah_kelurahan USING(id_wilayah_kelurahan)
				LEFT JOIN wilayah_kecamatan USING(id_wilayah_kecamatan)
				LEFT JOIN wilayah_kabupaten USING(id_wilayah_kabupaten)
				LEFT JOIN wilayah_propinsi USING(id_wilayah_propinsi)
				ORDER BY nama_company';
		return $this->db->query($sql)->getResult();
	}
	
	public function getCompanyById($id) {
		$sql = 'SELECT company.*, 
					wilayah_kelurahan.nama_kelurahan,
					wilayah_kecamatan.nama_kecamatan,
					wilayah_kabupaten.nama_kabupaten,
					wilayah_propinsi.nama_propinsi
				FROM company
				LEFT JOIN wilayah_kelurahan USING(id_wilayah_kelurahan)
				LEFT JOIN wilayah_kecamatan USING(id_wilayah_kecamatan)
				LEFT JOIN wilayah_kabupaten USING(id_wilayah_kabupaten)
				LEFT JOIN wilayah_propinsi USING(id_wilayah_propinsi)
				WHERE id_company = ?';
		return $this->db->query($sql, [$id])->getRow();
	}
	
	public function getActiveCompanies() {
		$sql = 'SELECT * FROM company WHERE status = "active" ORDER BY nama_company';
		return $this->db->query($sql)->getResult();
	}
	
	public function getCompanySetting($id_company) {
		$company = $this->find($id_company);
		if ($company && isset($company->setting) && $company->setting) {
			return json_decode($company->setting, true);
		}
		return [
			'hari_kerja' => [1,2,3,4,5],
			'gunakan_foto_selfi' => 'Y',
			'gunakan_radius_lokasi' => 'Y',
			'latitude' => ($company && isset($company->latitude)) ? $company->latitude : '-7.797068',
			'longitude' => ($company && isset($company->longitude)) ? $company->longitude : '110.370529',
			'radius_nilai' => ($company && isset($company->radius_nilai)) ? $company->radius_nilai : '1.00',
			'radius_satuan' => ($company && isset($company->radius_satuan)) ? $company->radius_satuan : 'km'
		];
	}
	
	public function saveData() {
		$fields = ['nama_company', 'alamat', 'id_wilayah_kelurahan', 
				   'latitude', 'longitude', 'radius_nilai', 'radius_satuan',
				   'email', 'no_telp', 'contact_person', 'status', 'keterangan'];
		
		$data_db = [];
		foreach ($fields as $field) {
			if (isset($_POST[$field])) {
				$data_db[$field] = $this->request->getPost($field);
			}
		}
		
		// Handle setting JSON data
		$hari_kerja_post = $this->request->getPost('hari_kerja');
		$hari_kerja = $hari_kerja_post ? array_map('intval', $hari_kerja_post) : [1,2,3,4,5];
		
		$setting_data = [
			'hari_kerja' => $hari_kerja, // Ensure integers
			'gunakan_foto_selfi' => $this->request->getPost('gunakan_foto_selfi') ?: 'Y',
			'gunakan_radius_lokasi' => $this->request->getPost('gunakan_radius_lokasi') ?: 'Y',
			'latitude' => $this->request->getPost('setting_latitude') ?: $this->request->getPost('latitude'),
			'longitude' => $this->request->getPost('setting_longitude') ?: $this->request->getPost('longitude'),
			'radius_nilai' => $this->request->getPost('setting_radius_nilai') ?: $this->request->getPost('radius_nilai'),
			'radius_satuan' => $this->request->getPost('setting_radius_satuan') ?: $this->request->getPost('radius_satuan')
		];
		$data_db['setting'] = json_encode($setting_data);
		
		$this->db->transStart();
		
		if ($this->request->getPost('id')) {
			$data_db['id_user_update'] = $this->session->get('user')['id_user'];
			$data_db['tgl_update'] = date('Y-m-d H:i:s');
			$save = $this->db->table('company')->update($data_db, ['id_company' => $_POST['id']]);
			$id_company = $_POST['id'];
		} else {
			$data_db['id_user_input'] = $this->session->get('user')['id_user'];
			$data_db['tgl_input'] = date('Y-m-d H:i:s');
			$save = $this->db->table('company')->insert($data_db);
			$id_company = $this->db->insertID();
		}
		
		// Handle patrol points
		$patrol_data = $this->request->getPost('patrol');
		if ($patrol_data && is_array($patrol_data)) {
			$patrolModel = new \App\Models\CompanyPatrolModel();
			$patrolModel->savePatrolPoints($id_company, $patrol_data);
		}
		
		$this->db->transComplete();
		
		if ($this->db->transStatus() === false) {
			return ['status' => 'error', 'message' => 'Data gagal disimpan'];
		}
		
		return ['status' => 'ok', 'message' => 'Data berhasil disimpan', 'id_company' => $id_company];
	}
	
	public function deleteData() {
		$this->db->transStart();
		$this->db->table('company')->delete(['id_company' => $_POST['id']]);
		$this->db->transComplete();
		
		return $this->db->transStatus();
	}
	
	public function countAllData() {
		$sql = 'SELECT COUNT(*) AS jml FROM company';
		$result = $this->db->query($sql)->getRow();
		return $result->jml;
	}
	
	public function getListData() {
		$columns = $this->request->getPost('columns');
		
		// Search
		$search_all = @$this->request->getPost('search')['value'];
		$where = ' WHERE 1 = 1 ';
		
		if ($search_all) {
			$where_col = [];
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
		if (strpos($_POST['columns'][$order_data[0]['column']]['data'], 'ignore') === false) {
			$order_by = $columns[$order_data[0]['column']]['data'] . ' ' . strtoupper($order_data[0]['dir']);
			$order = ' ORDER BY ' . $order_by;
		}
		
		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml_data FROM company ' . $where;
		$total_filtered = $this->db->query($sql)->getRow()->jml_data;
		
		// Query Data
		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		$sql = 'SELECT * FROM company ' . $where . $order . ' LIMIT ' . $start . ', ' . $length;
		$data = $this->db->query($sql)->getResult();
		
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
}

