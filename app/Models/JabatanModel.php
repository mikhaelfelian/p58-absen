<?php
/**
*	App Name	: Aplikasi Siswa dan Pembayaran SPP Sekolah	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2023-2023
*/

namespace App\Models;

class JabatanModel extends \App\Models\BaseModel
{
	public function __construct() {
		parent::__construct();
	}
	
	public function deleteData() {
		$result = $this->db->table('jabatan')->delete(['id_jabatan' => $_POST['id']]);
		return $result;
	}
	
	public function getJmlJabatan() {
		$sql = 'SELECT COUNT(*) AS jml_data FROM jabatan';
		$result = $this->db->query($sql)->getRowArray();
		return $result['jml_data'];
	}
	
	public function getListJabatan() {
		$sql = 'SELECT * FROM jabatan ORDER BY urut';
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function updateUrut() 
	{
		$urut = 1;
		$list = json_decode($_POST['list_id_jabatan'], true);
		// print_r($list); die;
		foreach ($list as $val) {
			$this->db->table('jabatan')->update(['urut' => $urut], ['id_jabatan' => $val]);
			$urut++;
		}
		return true;
	}
	
	public function deleteAllJabatan() {
		
		$list_table = [
						'jabatan',
					];
					
		try {
			$this->db->transException(true)->transStart();
			
			foreach ($list_table as $table) {
				$this->db->table($table)->emptyTable();
				$sql = 'ALTER TABLE ' . $table . ' AUTO_INCREMENT 1';
				$this->db->query($sql);
			}
			
			$this->db->transComplete();
			
			if ($this->db->transStatus() == true)
				return ['status' => 'ok', 'message' => 'Data berhasil dihapus'];
			
			return ['status' => 'error', 'message' => 'Database error'];
			
		} catch (DatabaseException $e) {
			return ['status' => 'error', 'message' => $e->getMessage()];
		}
	}
	
	public function getJabatanById($id) {
		$sql = 'SELECT * FROM jabatan WHERE id_jabatan = ?';
		$result = $this->db->query($sql, trim($id))->getRowArray();
		return $result;
	}
	
	public function saveData() 
	{
		$data_db['nama_jabatan'] = $_POST['nama_jabatan'];
		
		$query = false;
		
		if ($_POST['id']) 
		{
			$query = $this->db->table('jabatan')->update($data_db, ['id_jabatan' => $_POST['id']]);	
		} else {
			$query = $this->db->table('jabatan')->insert($data_db);
			if ($query) {
				$result['id_jabatan'] = $this->db->insertID();
			} 
		}
		
		if ($query) {
			$result['status'] = 'ok';
			$result['message'] = 'Data berhasil disimpan';
		} else {
			$result['status'] = 'error';
			$result['message'] = 'Data gagal disimpan';
		}
		
		return $result;
	}
	
	public function countAllData($where) {
		$sql = 'SELECT COUNT(*) AS jml FROM jabatan' . $where;
		$result = $this->db->query($sql)->getRow();
		return $result->jml;
	}
	
	public function getListData($where) {

		$columns = $this->request->getPost('columns');

		// Search
		$search_all = @$this->request->getPost('search')['value'];
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
		
		// Order		
		$order_data = $this->request->getPost('order');
		$order = '';
		if (strpos($_POST['columns'][$order_data[0]['column']]['data'], 'ignore_search') === false) {
			$order_by = $columns[$order_data[0]['column']]['data'] . ' ' . strtoupper($order_data[0]['dir']);
			$order = ' ORDER BY ' . $order_by;
		}

		// Query Total Filtered
		$sql = 'SELECT COUNT(*) AS jml_data FROM jabatan ' . $where;
		$total_filtered = $this->db->query($sql)->getRowArray()['jml_data'];
		
		// Query Data
		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		$sql = 'SELECT * FROM jabatan 
				' . $where . $order  . ' LIMIT ' . $start . ', ' . $length;
		$data = $this->db->query($sql)->getResultArray();
				
		return ['data' => $data, 'total_filtered' => $total_filtered];
	}
}
?>