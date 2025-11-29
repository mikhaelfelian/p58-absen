<?php
/**
*	App Name	: Antrian	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class AktivasiModel extends \App\Models\BaseModel
{
	public function __construct() {
		parent::__construct();
	}
	
	public function saveActivationData($data) 
	{
		$data_db[] = ['type' => 'aktivasi', 'param' => 'activation_key', 'value' => $data['activation_key'] ];
		
		$this->db->transStart();
		$this->db->table('setting')->delete(['type' => 'aktivasi']);
		$this->db->table('setting')->insertBatch($data_db);
		$query = $this->db->transComplete();
		$query_result = $this->db->transStatus();
		
		return $query_result;
	}
	
	public function deleteAktivasi() {
		$delete = $this->db->table('setting')->delete(['type' => 'aktivasi']);
		return $delete;
	}
}
?>