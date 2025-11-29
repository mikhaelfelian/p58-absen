<?php
namespace App\Models\Builtin;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use CodeIgniter\Database\Exceptions\DatabaseException;

class UserModel extends \App\Models\BaseModel
{
	public function getUserAdmin() {
		$sql = 'SELECT * 
				FROM user 
				LEFT JOIN user_role USING(id_user)
				LEFT JOIN role USING (id_role) 
				WHERE nama_role = "admin"';
		return $this->db->query($sql)->getResultArray();
	}
	
	public function writeExcel() 
	{
		require_once(ROOTPATH . "/app/ThirdParty/PHPXlsxWriter/xlsxwriter.class.php");
						
		// $sql = $this->sqlQuery();	
		
		$colls = [
					'no' 			=> ['type' => '#,##0', 'width' => 5, 'title' => 'No'],
					'nama' 			=> ['type' => 'string', 'width' => 30, 'title' => 'Nama'],
					'jenis_kelamin' => ['type' => 'string', 'width' => 13, 'title' => 'Jenis Kelamin'],
					'nip' 			=> ['type' => 'string', 'width' => 15, 'title' => 'NIP'],
					'nik' 			=> ['type' => 'string', 'width' => 18, 'title' => 'NIK'],
					'nama_jabatan' 	=> ['type' => 'string', 'width' => 20, 'title' => 'Jabatan'],
					'tempat_lahir' 	=> ['type' => 'string', 'width' => 15, 'title' => 'Tempat Lahir'],
					'tgl_lahir' 	=> ['type' => 'string', 'width' => 20, 'title' => 'Tanggal Lahir'],
					'email' 		=> ['type' => 'string', 'width' => 22, 'title' => 'Email'],
					'no_hp' 		=> ['type' => 'string', 'width' => 15, 'title' => 'No. HP'],
					'alamat' 		=> ['type' => 'string', 'width' => 30, 'title' => 'Alamat'],
					'nama_kelurahan' 	=> ['type' => 'string', 'width' => 20, 'title' => 'Kelurahan'],
					'nama_kecamatan' 	=> ['type' => 'string', 'width' => 20, 'title' => 'Kecamatan'],
					'nama_kabupaten' 	=> ['type' => 'string', 'width' => 20, 'title' => 'Kabupaten'],
					'nama_propinsi' 		=> ['type' => 'string', 'width' => 20, 'title' => 'Propinsi']
				];
		
		$col_type = $col_width = $col_header = [];
		foreach ($colls as $field => $val) {
			$col_type[$field] = $val['type'];
			$col_header[$field] = $val['title'];
			$col_header_type[$field] = 'string';
			$col_width[] = $val['width'];
		}
		
		// SQL
		$table_column = $colls;
		unset($table_column['no']);
		$table_column = array_keys($table_column);
		foreach ($table_column as &$val) {
			if ($val == 'nama_kabupaten') {
				$val = 'CONCAT(jenis_kabupaten_kota, " ", nama_kabupaten) AS nama_kabupaten';
			}
			if ($val == 'nama_jabatan') {
				$val = 'GROUP_CONCAT(DISTINCT nama_jabatan SEPARATOR " + ") AS nama_jabatan';
			}
		}
		$table_column = join(', ', $table_column);
		
		$sql = 'SELECT ' .  $table_column  . '
				FROM user
				LEFT JOIN user_jabatan USING(id_user)
				LEFT JOIN jabatan USING(id_jabatan)
				LEFT JOIN wilayah_kelurahan USING(id_wilayah_kelurahan)
				LEFT JOIN wilayah_kecamatan USING(id_wilayah_kecamatan)
				LEFT JOIN wilayah_kabupaten USING(id_wilayah_kabupaten)
				LEFT JOIN wilayah_propinsi USING(id_wilayah_propinsi)
				GROUP BY id_user';
		
		$query = $this->db->query($sql);
		
		// Excel
		$sheet_name = strtoupper('Daftar Pegawai');
		$writer = new \XLSXWriter();
		$writer->setAuthor('Jagowebdev');
		
		$writer->writeSheetHeader($sheet_name, $col_header_type, $col_options = ['widths'=> $col_width, 'suppress_row'=>true]);
		$writer->writeSheetRow($sheet_name, $col_header);
		$writer->updateFormat($sheet_name, $col_type);
		
		$no = 1;
		while ($row = $query->getUnbufferedRow('array')) {
			array_unshift($row, $no);
			if (key_exists('jenis_kelamin', $row)) {
				if ($row['jenis_kelamin'] == 'L') {
					$row['jenis_kelamin'] = 'Laki-Laki';
				} else if ($row['jenis_kelamin'] == 'P') {
					$row['jenis_kelamin'] = 'Perempuan';
				} else {
					$row['jenis_kelamin'] = '';
				}
			}
			
			if (key_exists('tgl_lahir', $row)) {
				$row['tgl_lahir'] = format_date($row['tgl_lahir']);
			}
			
			$writer->writeSheetRow($sheet_name, $row);
			$no++;
		}
		
		$tmp_file = ROOTPATH . 'public/tmp/user_' . time() . '.xlsx.tmp';
		$writer->writeToFile($tmp_file);
		return $tmp_file;
	}
	
	/* UPLOAD EXCEL */
	private function getUserByEmail($email) {
		$sql = 'SELECT * FROM user WHERE email = ?';
		return $this->db->query($sql, $email)->getRowArray();
	}
	
	private function getUserByNip($nip) {
		$sql = 'SELECT * FROM user WHERE nip = ?';
		return $this->db->query($sql, $nip)->getRowArray();
	}
	
	private function getWilayahKelurahan($nama_kelurahan, $nama_kecamatan, $nama_kabupaten, $nama_propinsi) {
		$sql = 'SELECT * FROM wilayah_kelurahan 
		LEFT JOIN wilayah_kecamatan USING(id_wilayah_kecamatan)
		LEFT JOIN wilayah_kabupaten USING(id_wilayah_kabupaten)
		LEFT JOIN wilayah_propinsi USING(id_wilayah_propinsi)
		WHERE nama_kelurahan = ? AND nama_kecamatan = ? AND nama_kabupaten = ? AND nama_propinsi = ?';
		$result = $this->db->query($sql, [ $nama_kelurahan, $nama_kecamatan, $nama_kabupaten, $nama_propinsi ])->getRowArray();
		return $result;
	}
	
	public function uploadExcel() 
	{
		helper(['upload_file', 'format']);
		$path = ROOTPATH . 'public/tmp/';
		
		$file = $this->request->getFile('file_excel');
		if (! $file->isValid())
		{
			throw new RuntimeException($file->getErrorString().'('.$file->getError().')');
		}
				
		require_once 'app/ThirdParty/Spout/src/Spout/Autoloader/autoload.php';
		
		$filename = upload_file($path, $_FILES['file_excel']);
		$reader = ReaderEntityFactory::createReaderFromFile($path . $filename);
		$reader->open($path . $filename);
		
		$sql = 'SELECT * FROM role';
		$data = $this->db->query($sql)->getResultArray();
		$roles = [];
		foreach($data as $val) {
			$roles[$val['nama_role']] = $val['id_role'];
		}
		
		$warning = [];
		$error_message = [];
		$row_inserted = 0;
		$sql = 'SELECT * FROM module WHERE nama_module = ?';
		$module_user = $this->db->query($sql, 'builtin/user')->getRowArray();
		foreach ($reader->getSheetIterator() as $sheet) 
		{
			$num_row = 0;
			
			if (strtolower($sheet->getName()) == 'data') 
			{
				foreach ($sheet->getRowIterator() as $num_row => $row) 
				{
					$error = false;
					$role_list = [];
					$data_db = [];
					$data_db_role = [];
					$error_message_row = [];
					
					$cols = $row->toArray();
									
					if ($num_row == 1) {
						$field_table = $cols;
						$field_name = array_map('strtolower', $field_table);
						continue;
					}
					
					$data_value = [];
					foreach ($field_name as $num_col => $field) 
					{
						$val = null;
						if (key_exists($num_col, $cols) && $cols[$num_col] != '') {
							$val = $cols[$num_col];
						}
						
						if ($val instanceof \DateTime) {
							$val = $val->format('d-m-Y H:i:s');
						}
						
						if ($field == 'role') {
							if (trim($val)) {
								$exp = explode(',', $val);
								$role_list = array_map('trim', $exp);
							}
							continue;
						}
						
						if ($field == 'password') {
							$val = password_hash($val, PASSWORD_DEFAULT);
						}
						
						if ($field == 'email') {
							if ($this->getUserByEmail($field)) {
								$error_message_row[] = 'Email ' . $field . ' sudah digunakan';
								$error = true;
							}
						}
						
						if ($field == 'nip') {
							if ($this->getUserByNip($field)) {
								$error_message_row[] = 'NIP Pegawai ' . $field . ' sudah digunakan';
								$error = true;
							}
						}
												
						$data_value[trim($field)] = $val;
					}

					if ($data_value && !$error) 
					{				
						$wilayah = $this->getWilayahKelurahan($data_value['kelurahan'], $data_value['kecamatan'], $data_value['kabupaten'], $data_value['propinsi']);
						$id_wilayah_kelurahan = $wilayah ? $wilayah['id_wilayah_kelurahan'] : null;
						
						$tgl_lahir = null;
						if (!empty($data_value['tanggal_lahir'])) {
							$exp = explode(' ', $data_value['tanggal_lahir']);
							$tanggal = $exp[0];
							$exp = explode('-', $tanggal);
							$tgl_lahir = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
						}
												
						$data_db = ['nama' => $data_value['nama']
										, 'username' => $data_value['username']
										, 'nik' => $data_value['nik']
										, 'nip' => $data_value['nip']
										, 'email' => $data_value['email']
										, 'tempat_lahir' => $data_value['tempat_lahir']
										, 'jenis_kelamin' => $data_value['jenis_kelamin']
										, 'tgl_lahir' => $tgl_lahir
										, 'alamat' => $data_value['alamat']
										, 'id_wilayah_kelurahan' => $id_wilayah_kelurahan
										, 'no_hp' => $data_value['no_hp']
										, 'password' => password_hash($data_value['password'], PASSWORD_DEFAULT)
										, 'status' => 'active'
										, 'verified' => 1
										, 'default_page_type' => 'id_module'
										, 'default_page_url' => null
										, 'default_page_id_module' => $module_user['id_module']
										, 'default_page_id_role' => null
										, 'tgl_input' => date('Y-m-d')
										, 'id_user_input' => $this->session->get('user')['id_user']
									];
						
						$this->db->table('user')->insert($data_db);
						$id_user = $this->db->insertID();
						
						// Jabatan
						$exp = explode('+', $data_value['jabatan']);
						foreach ($exp as $nama_jabatan) 
						{
							$nama_jabatan = trim($nama_jabatan);
							$sql = 'SELECT * FROM jabatan WHERE nama_jabatan = ?';
							$result = $this->db->query($sql, $nama_jabatan)->getRowArray();
							if ($result) {
								$id_jabatan = $result['id_jabatan'];
							} else {
								$this->db->table('jabatan')->insert(['nama_jabatan' => $nama_jabatan]);
								$id_jabatan = $this->db->insertID();
							}
							$this->db->table('user_jabatan')->insert(['id_user' => $id_user, 'id_jabatan' => $id_jabatan]);
						}
															
						$data_db_role = [];
						if ($role_list) {
							foreach ($role_list as $role_name) {
								if (key_exists($role_name, $roles)) {
									$data_db_role[] = ['id_user' => $id_user, 'id_role' => $roles[$role_name]];
								} else {
									$warning[] = 'Role ' . $role_name . ' pada user ' . $nama . ' tidak ada di tabel user_role';
								}
							}
							
							if ($data_db_role) {
								$query = $this->db->table('user_role')->insertBatch($data_db_role);
							}
						} else {
							$warning[] = 'Role untuk user ' . $nama . ' belum didefinisikan';
						}

						if (true) {
							$row_inserted++;
						} else {
							$error_message_row[] =  'Data gagal disimpan';
						}
					}

					if ($error_message_row) {
						$error_message[] = 'Baris ' . $num_row . ': ' . join(', ', $error_message_row);
					}
					
					$num_row += 1;
				}
				break;
			}
		}
		
		$reader->close();
		delete_file($path . $filename);
		
		$message = [];
		$message['ok'] = 'Data berhasil di masukkan ke dalam tabel user sebanyak ' . format_ribuan($row_inserted) . ' baris';
		if ($warning) {
			$message['warning'] = $warning; 
		}

		if ($error_message) {
			$message['error'] = $error_message;
		}
		
		$result['status'] = 'upload_excel';
		$result['message'] = $message;
		
		return $result;
	}
	/*-- UPLOAD EXCEL */
	
	public function getListUsers($where) {
		
		// Get current logged-in user ID
		$current_user_id = isset($_SESSION['user']['id_user']) ? $_SESSION['user']['id_user'] : 0;
		
		// Security: Hide user ID 1 (super admin) unless current user is also ID 1
		if ($current_user_id != 1) {
			$where .= ' AND user.id_user != 1';
		}
		
		// Get user
		$columns = $this->request->getPost('columns');
		$order_by = '';
		
		// Search
		$search_all = @$this->request->getPost('search')['value'];
		if ($search_all) {
			
			foreach ($columns as $val) {
				if (strpos($val['data'], 'ignore') !== false)
					continue;
				
				$where_col[] = $val['data'] . ' LIKE "%' . $search_all . '%"';
			}
			 $where .= ' AND (' . join(' OR ', $where_col) . ') ';
		}
		
		// Order
		$start = $this->request->getPost('start') ?: 0;
		$length = $this->request->getPost('length') ?: 10;
		
		$order_data = $this->request->getPost('order');
		$order = '';
		if (!empty($_POST['columns']) && strpos($_POST['columns'][$order_data[0]['column']]['data'], 'ignore') === false) {
			$order_by = $columns[$order_data[0]['column']]['data'] . ' ' . strtoupper($order_data[0]['dir']);
			$order = ' ORDER BY ' . $order_by . ' LIMIT ' . $start . ', ' . $length;
		}
		
		$sql = 'SELECT COUNT(*) as jml FROM
				(SELECT user.*, GROUP_CONCAT(judul_role) AS judul_role FROM user 
				LEFT JOIN user_role USING(id_user) 
				LEFT JOIN role ON user_role.id_role = role.id_role
				' . $where . '
				GROUP BY id_user) AS tabel';
				
		$query = $this->db->query($sql)->getRowArray();
		$total_filtered = $query['jml'];
		
		$sql = 'SELECT user.*, GROUP_CONCAT(judul_role) AS judul_role FROM user 
				LEFT JOIN user_role USING(id_user) 
				LEFT JOIN role ON user_role.id_role = role.id_role
				' . $where . '
				GROUP BY id_user
				' . $order;
		
		
		$data = $this->db->query($sql)->getResultArray();
		return ['data' => $data, 'total_filtered' => $total_filtered];
		
	}
	
	public function getJabatan() {
		$sql = 'SELECT * FROM jabatan ORDER BY urut';
		$result = $this->db->query($sql)->getResultArray();
		$data = [];
		foreach($result as $val) {
			$data[$val['id_jabatan']] = $val['nama_jabatan'];
		}
		return $data;
	}
	
	public function getJabatanByIdUser($id) {
		$sql = 'SELECT * FROM user_jabatan LEFT JOIN jabatan USING(id_jabatan) WHERE id_user = ? ORDER BY urut';
		$data = $this->db->query($sql, $id)->getResultArray();
		$result = [];
		foreach ($data as $val) {
			$result[$val['id_jabatan']] = $val['id_jabatan'];
		}
		return $result;
	}
	
	public function countAllUsers($where = null) {
		$query = $this->db->query('SELECT COUNT(*) as jml FROM user' . $where)->getRow();
		return $query->jml;
	}
	
	public function getRoles() {
		$sql = 'SELECT * FROM role';
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function getSettingRegister() {
		$sql = 'SELECT * FROM setting WHERE type="register"';
		$result = $this->db->query($sql)->getResultArray();
		return $result;
	}
	
	public function getListModules() {
		
		$sql = 'SELECT * FROM module LEFT JOIN module_status USING(id_module_status) ORDER BY nama_module';
		return $this->db->query($sql)->getResultArray();
	}
	
	public function deleteAllUser() 
	{
		// Security: Never allow deletion of super admin (id_user = 1)
		$protected_users = [1]; // Super admin is always protected
		
		// List role
		$sql = 'SELECT id_user 
				FROM user 
				LEFT JOIN user_role USING(id_user) 
				LEFT JOIN role USING (id_role) 
				WHERE nama_role = "admin"';
		$result = $this->db->query($sql)->getResultArray();
		$list_role = [];
		foreach ($result as $val) {
			$list_role[] = $val['id_user'];
		}
		
		// Combine protected users (super admin + all admins)
		$all_protected = array_unique(array_merge($protected_users, $list_role));
		
		// List User
		$sql = 'SELECT * 
				FROM user 
				LEFT JOIN user_role USING(id_user) 
				LEFT JOIN role USING (id_role) 
				WHERE id_user NOT IN (' . join(',', $all_protected) . ')';
		$user = $this->db->query($sql)->getRowArray();
		if (!$user) {
			return ['status' => 'error', 'message' => 'Tidak ditemukan pegawai yang dapat dihapus (semua admin dilindungi)'];
		}
					
		$this->db->transStart();
				
		// User - Use the protected list that includes super admin
		$sql = 'DELETE FROM user WHERE id_user NOT IN (' . join(',', $all_protected) . ')';
		$this->db->query($sql);
		
		$sql = 'SELECT MAX(id_user) AS max FROM user';
		$data = $this->db->query($sql)->getRowArray();
		$max = $data['max'] + 1;
		$sql = 'ALTER TABLE user AUTO_INCREMENT ' . $max;
		$this->db->query($sql);
		
		// Jabatan - Use protected list
		$sql = 'DELETE FROM user_jabatan WHERE id_user NOT IN (' . join(',', $all_protected) . ')';
		$this->db->query($sql);
		
		// Role - Use protected list
		$sql = 'DELETE FROM user_role WHERE id_user NOT IN (' . join(',', $all_protected) . ')';
		$this->db->query($sql);
						
		$this->db->transComplete();
		$trans = $this->db->transStatus();
		
		if ($trans) {
			foreach ($user as $val) {
				if ($user['avatar']) {
					delete_file(ROOTPATH . 'public/images/user/' . $user['avatar']);
				}
			}
		} else {
			return ['status' => 'error', 'message' => 'Tidak ada data yang dihapus'];
		}
		
		return ['status' => 'ok', 'message' => 'Data berhasil dihapus'];
	}
		
	public function saveData($user_permission = []) 
	{ 
		$fields = ['nama', 'jenis_kelamin', 'no_hp', 'nip', 'nik', 'email', 'tempat_lahir', 'tgl_lahir', 'id_wilayah_kelurahan','alamat'];
		
		if (in_array('update_all', $user_permission)) {
			$add_field = ['username', 'status', 'verified', 'default_page_id_role', 'default_page_id_module', 'default_page_url'];
			$fields = array_merge($fields, $add_field);
		}

		foreach ($fields as $field) 
		{
			if ($field == 'tgl_lahir') {
				$exp = explode('-', $_POST[$field]);
				$tanggal = $exp[2].'-'.$exp[1].'-'.$exp[0];
				$data_db[$field] = $tanggal;
				continue;
			}
			
			$user_value = $this->request->getPost($field);
			if ($field == 'default_page_id_role') {
				if ($_POST['option_default_page'] != 'id_role') {
					$user_value = null;
				}
			}
			
			if ($field == 'default_page_id_module') {
				if ($_POST['option_default_page'] != 'id_module') {
					$user_value = null;
				}
			}
			
			$data_db[$field] = $user_value;
		}
		
		if ($this->request->getPost('password')) {
			$data_db['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
		}
		
		$data_db['default_page_type'] = $this->request->getPost('option_default_page');
		// $this->db->transStart();
		
		if (!$this->request->getPost('id')) {
			$data_db['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
		}
		
		// Save database
		if ($this->request->getPost('id')) {
			$id_user = $this->request->getPost('id');
			$this->db->table('user')->update($data_db, ['id_user' => $id_user]);
		} else {
			$this->db->table('user')->insert($data_db);
			$id_user = $this->db->insertID();
		}
		
		if (in_array('update_all', $user_permission)) {
			$data_db_jabatan = [];
			if (!empty($_POST['id_jabatan'])) {
				foreach ($_POST['id_jabatan'] as $val) {
					$data_db_jabatan[] = ['id_user' => $id_user, 'id_jabatan' => $val];
				}
				$this->db->table('user_jabatan')->delete(['id_user' => $id_user]);
				$this->db->table('user_jabatan')->insertBatch($data_db_jabatan);
			}
		}
				
		if (in_array('update_all', $user_permission)) {
			$data_db = [];
			foreach ($_POST['id_role'] as $id_role) {
				$data_db[] = ['id_user' => $id_user, 'id_role' => $id_role];
			}
		
			$this->db->table('user_role')->delete(['id_user' => $id_user]);
			$this->db->table('user_role')->insertBatch($data_db);
		}
		
		$this->db->transComplete();
		$trans = $this->db->transStatus();
		
		$save = false;
		if ($trans) {
			
			$file = $this->request->getFile('avatar');
			$path = ROOTPATH . 'public/images/user/';
			
			$sql = 'SELECT avatar FROM user WHERE id_user = ?';
			$img_db = $this->db->query($sql, $id_user)->getRowArray();
			$new_name = $img_db['avatar'];
			
			if (!empty($_POST['avatar_delete_img'])) 
			{
				$del = delete_file($path . $img_db['avatar']);
				$new_name = '';
				if (!$del) {
					$result = ['status' =>'error', 'message' => 'Gagal menghapus gambar lama'];
					$error = true;
				}
			}
					
			if ($file && $file->getName()) 
			{
				//old file
				if ($img_db['avatar']) {
					if (file_exists($path . $img_db['avatar'])) {
						$unlink = delete_file($path . $img_db['avatar']);
						if (!$unlink) {
							$result = ['status' => 'error', 'message' => 'Gagal menghapus gambar lama'];
						}
					}
				}
							
				helper('upload_file');
				$new_name =  get_filename($file->getName(), $path);
				$file->move($path, $new_name);
					
				if (!$file->hasMoved()) {
					$result = ['status' => 'error', 'message' => 'Error saat memperoses gambar'];
					return $result;
				}
			}
			
			// Update avatar
			$data_db = [];
			$data_db['avatar'] = $new_name;
			$save = $this->db->table('user')->update($data_db, ['id_user' => $id_user]);
		}

		if ($save) {
			$result = ['status' =>'ok', 'message' => 'Data berhasil disimpan', 'id_user' => $id_user];

			if ($this->session->get('user')['id_user'] == $id_user) {
				// Reload data user
				$this->session->set('user', $this->getUserById($this->session->get('user')['id_user']) );
			}
		} else {
			$result = ['status' => 'error', 'message' => 'Data gagal disimpan'];
		}
								
		return $result;
	}
	
	public function deleteUser() 
	{
		$id_user = $this->request->getPost('id');
		$sql = 'SELECT * FROM user WHERE id_user = ?';
		$user = $this->db->query($sql, $id_user)->getRowArray();
		if (!$user) {
			return false;
		}
			
		$this->db->transStart();
		$this->db->table('user')->delete(['id_user' => $id_user]);
		$this->db->table('user_role')->delete(['id_user' => $id_user]);
		$delete = $this->db->affectedRows();
		$this->db->transComplete();
		$trans = $this->db->transStatus();
		
		if ($trans) {
			if (!empty($user['avatar'])) {
				delete_file(ROOTPATH . 'public/images/user/' . $user['avatar']);
			}
		}
		
		return true;
	}

	public function updatePassword() {
		$password_hash = password_hash($this->request->getPost('password_new'), PASSWORD_DEFAULT);
		$update = $this->db->query('UPDATE user SET password = ? 
									WHERE id_user = ? ', [$password_hash, $this->user['id_user']]
								);		
		return $update;
	}
}
?>