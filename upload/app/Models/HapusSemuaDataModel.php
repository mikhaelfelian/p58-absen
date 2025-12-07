<?php
namespace App\Models;

class HapusSemuaDataModel extends \App\Models\BaseModel
{
	public $list_table = [];
	
	public function __construct() {
		parent::__construct();
		$this->list_table = [
						'user_presensi'
					];
	}
	
	public function getListTable() {
		return $this->list_table;
	}
	
	public function deleteAllData() 
	{
		try {
			$this->db->transException(true)->transStart();
			
			foreach ($this->list_table as $table) {
				$this->db->table($table)->emptyTable();
				$this->resetAutoIncrement($table);
			}
			
			$this->db->transComplete();
			
			if ($this->db->transStatus() == true)
				return ['status' => 'ok', 'message' => 'Data berhasil dihapus'];
			
			return ['status' => 'error', 'message' => 'Database error'];
			
		} catch (DatabaseException $e) {
			return ['status' => 'error', 'message' => $e->getMessage()];
		}
	}
	
	public function getDbName() {
		return $this->db->database;
	}
}
?>