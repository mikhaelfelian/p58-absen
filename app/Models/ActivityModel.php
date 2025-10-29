<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class ActivityModel extends \App\Models\BaseModel
{
	protected $table = 'activity';
	protected $primaryKey = 'id_activity';
	protected $returnType = 'object';
	protected $useSoftDeletes = false;
	protected $useTimestamps = true;
	protected $createdField = 'created_at';
	protected $updatedField = 'updated_at';
	protected $allowedFields = [
		'id_user', 'id_company', 'id_user_presensi', 'tanggal', 'waktu',
		'judul_activity', 'deskripsi_activity', 'foto_activity',
		'latitude', 'longitude', 'status', 'approved_by', 'approved_at', 'rejection_reason'
	];
	
	public function __construct() {
		parent::__construct();
	}
	
	public function getAllUser() {
		return $this->db->table('user')
			->select('id_user, nama')
			->where('status', 'active')
			->orderBy('nama')
			->get()
			->getResultArray();
	}
	
	public function getActivityByUser($id_user, $start_date = null, $end_date = null) {
		$where_date = '';
		if ($start_date && $end_date) {
			$where_date = ' AND tanggal BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
		}
		
		$sql = 'SELECT activity.*, company.nama_company, user.nama
				FROM activity
				LEFT JOIN company USING(id_company)
				LEFT JOIN user USING(id_user)
				WHERE activity.id_user = ? ' . $where_date . '
				ORDER BY tanggal DESC, waktu DESC';
		return $this->db->query($sql, [$id_user])->getResult();
	}
	
	public function getActivityByCompany($id_company, $start_date = null, $end_date = null) {
		$where_date = '';
		if ($start_date && $end_date) {
			$where_date = ' AND tanggal BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
		}
		
		$sql = 'SELECT activity.*, company.nama_company, user.nama, user.nip
				FROM activity
				LEFT JOIN company USING(id_company)
				LEFT JOIN user USING(id_user)
				WHERE activity.id_company = ? ' . $where_date . '
				ORDER BY tanggal DESC, waktu DESC';
		return $this->db->query($sql, [$id_company])->getResult();
	}
	
	public function getActivityById($id) {
		$sql = 'SELECT activity.*, company.nama_company, user.nama, user.nip,
					approver.nama AS approved_by_name
				FROM activity
				LEFT JOIN company USING(id_company)
				LEFT JOIN user USING(id_user)
				LEFT JOIN user AS approver ON activity.approved_by = approver.id_user
				WHERE id_activity = ?';
		return $this->db->query($sql, [$id])->getRow();
	}
	
	public function saveData($data) {
		// Handle foto_activity - if it's already a JSON string, use it; otherwise convert array to JSON
		$foto_activity = null;
		if (!empty($data['foto_activity'])) {
			if (is_string($data['foto_activity'])) {
				// Check if it's already JSON
				$decoded = json_decode($data['foto_activity'], true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
					// It's valid JSON, store as is
					$foto_activity = $data['foto_activity'];
				} else {
					// It's a single photo (base64), convert to array
					$foto_activity = json_encode([[
						'file_name' => 'photo_' . date('YmdHis') . '.jpg',
						'image' => $data['foto_activity'],
						'lat' => $data['latitude'] ?? null,
						'lon' => $data['longitude'] ?? null
					]]);
				}
			} elseif (is_array($data['foto_activity'])) {
				// It's already an array, convert to JSON
				$foto_activity = json_encode($data['foto_activity']);
			}
		}
		
		$data_db = [
			'id_user' => $data['id_user'],
			'id_company' => $data['id_company'],
			'id_user_presensi' => $data['id_user_presensi'] ?? null,
			'tanggal' => $data['tanggal'],
			'waktu' => $data['waktu'],
			'judul_activity' => $data['judul_activity'],
			'deskripsi_activity' => $data['deskripsi_activity'],
			'foto_activity' => $foto_activity,
			'latitude' => $data['latitude'] ?? null,
			'longitude' => $data['longitude'] ?? null,
			'status' => 'pending',
		];
		
		$this->db->transStart();
		$save = $this->db->table('activity')->insert($data_db);
		$id_activity = $this->db->insertID();
		$this->db->transComplete();
		
		if ($this->db->transStatus() === false) {
			return ['status' => 'error', 'message' => 'Data gagal disimpan'];
		}
		
		return ['status' => 'ok', 'message' => 'Activity berhasil disimpan', 'id_activity' => $id_activity];
	}
	
	public function approveActivity($id_activity, $id_user) {
		$data_db = [
			'status' => 'approved',
			'approved_by' => $id_user,
			'approved_at' => date('Y-m-d H:i:s'),
		];
		
		$this->db->transStart();
		$this->db->table('activity')->update($data_db, ['id_activity' => $id_activity]);
		$this->db->transComplete();
		
		return $this->db->transStatus();
	}
	
	public function rejectActivity($id_activity, $id_user, $reason) {
		$data_db = [
			'status' => 'rejected',
			'approved_by' => $id_user,
			'approved_at' => date('Y-m-d H:i:s'),
			'rejection_reason' => $reason,
		];
		
		$this->db->transStart();
		$this->db->table('activity')->update($data_db, ['id_activity' => $id_activity]);
		$this->db->transComplete();
		
		return $this->db->transStatus();
	}
	
	public function countAllData() {
		$sql = 'SELECT COUNT(*) AS jml FROM activity';
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
		$sql = 'SELECT COUNT(*) AS jml_data FROM activity 
				LEFT JOIN company USING(id_company)
				LEFT JOIN user USING(id_user)
				' . $where;
		$total_filtered = $this->db->query($sql)->getRow()->jml_data;
		
		// Query Data
		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		$sql = 'SELECT activity.*, company.nama_company, user.nama
				FROM activity 
				LEFT JOIN company USING(id_company)
				LEFT JOIN user USING(id_user)
				' . $where . $order . ' LIMIT ' . $start . ', ' . $length;
		$data = $this->db->query($sql)->getResult();
		
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
}

