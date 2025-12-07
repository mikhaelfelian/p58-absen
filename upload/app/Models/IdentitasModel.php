<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;

class IdentitasModel extends \App\Models\BaseModel
{
	public function getIdentitas() {
		$sql = 'SELECT * FROM identitas';
		$result = $this->db->query($sql)->getRowArray();
		return $result;
	}
	
	public function saveData() 
	{
		$result = [];
		
		$data_db['nama'] = $_POST['nama'];
		$data_db['alamat'] = $_POST['alamat'];
		$data_db['id_wilayah_kelurahan'] = $_POST['id_wilayah_kelurahan'];
		$data_db['email'] = $_POST['email'];
		$data_db['no_telp'] = $_POST['no_telp'];
		
		$query = $this->db->table('identitas')->update($data_db);
		if ($query) {
			return ['status' => 'ok', 'message' => 'Data berhasil disimpan'];
		}
		return ['status' => 'error', 'message' => 'Data gagal disimpan'];
	}
}
?>