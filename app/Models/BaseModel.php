<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Models;
use App\Libraries\Auth;

class BaseModel extends \CodeIgniter\Model 
{
	protected $request;
	protected $session;
	private $auth;
	protected $user;
	
	public function __construct() {
		parent::__construct();
		
		$this->request = \Config\Services::request();
		$this->session = \Config\Services::session();
		$user = $this->session->get('user');
		if ($user)
			$this->user = $this->getUserById($user['id_user']);
		
		$this->auth = new \App\Libraries\Auth;
	}
	
	public function getSettingPresensi() {
		$sql = 'SELECT * FROM setting_waktu_presensi WHERE gunakan = "Y"';
		$result = $this->db->query($sql)->getRowArray();
		return $result;
	}
	
	public function checkRememberme() 
	{
		if ($this->session->get('logged_in')) 
		{
			return true; 
		}
		
		helper('cookie');
		$cookie_login = get_cookie('remember');
	
		if ($cookie_login) 
		{
			list($selector, $cookie_token) = explode(':', $cookie_login);

			$sql = 'SELECT * FROM user_token WHERE selector = ?';		
			$data = $this->db->query($sql, $selector)->getRowArray();
			
			if ($this->auth->validateToken($cookie_token, @$data['token'])) {
				
				if ($data['expires'] > date('Y-m-d H:i:s')) 
				{
					$user_detail = $this->getUserById($data['id_user']);
					$this->session->set('user', $user_detail);
					$this->session->set('logged_in', true);
				}
			}
		}
		
		return false;
	}
	
	public function getUserById($id_user = null, $array = false) {
		
		if (!$id_user) {
			if (!$this->user) {
				return false;
			}
			$id_user = $this->user['id_user'];
		}
		
		$query = $this->db->query('SELECT * FROM user WHERE id_user = ?', $id_user);
		$user = $query->getRowArray();
		
		if (!$user) {
			return;
		}
		
		$user['jabatan'] = [];
		$query = $this->db->query('SELECT * FROM user_jabatan
						LEFT JOIN jabatan USING(id_jabatan)
						WHERE id_user = ' . $id_user);
		$result = $query->getResultArray();
		if ($result) {
			foreach ($result as $val) {
				$user['jabatan'][$val['id_jabatan']] = $val;
			}
		}
		
		$nama_jabatan = [];
		foreach ($user['jabatan'] as $val) {
			$nama_jabatan[] = $val['nama_jabatan'];
		}
		$user['nama_jabatan'] = join(' + ', $nama_jabatan);
		
		$user['role'] = [];
		$query = $this->db->query('SELECT * FROM user_role 
								LEFT JOIN role USING(id_role) 
								LEFT JOIN module USING(id_module) 
								WHERE id_user = ? 
								ORDER BY  nama_role', [$id_user]
							);
							
		$result = $query->getResultArray();
		if ($result) {
			foreach ($result as $val) {
				$user['role'][$val['id_role']] = $val;
			}
		}
				
		$query = $this->db->query('SELECT * FROM module WHERE id_module = ?', [$user['default_page_id_module']]);
		$user['default_module'] = $query->getRowArray();
		/* echo '<pre>';
		print_r($user);
		die; */
		return $user;
	}
	
	public function getUserSetting() {
		
		$result = $this->db->query('SELECT * FROM setting_user WHERE id_user = ? AND type = "layout"', [$this->session->get('user')['id_user']])
						->getRow();
		
		if (!$result) {
			$query = $this->db->query('SELECT * FROM setting WHERE type="layout"')
						->getResultArray();
			
			foreach ($query as $val) {
				$data[$val['param']] = $val['value'];
			}
			
			$result = new \StdClass;
			$result->param = json_encode($data);
		}
		return $result;
	}
	
	public function getAppLayoutSetting() {
		$result = $this->db->query('SELECT * FROM setting WHERE type="layout"')->getResultArray();
		return $result;
	}
	
	public function getDefaultUserModule() {
		
		if (empty($_SESSION['user']['role'])) {
			return [];
		}
		
		$query = $this->db->query('SELECT * 
							FROM role 
							LEFT JOIN module USING(id_module)
							WHERE id_role IN (' . join(',', array_keys($this->session->get('user')['role'])) . ')'
							)
						->getRow();
		return $query;
	}
	
	public function getModule($nama_module) {
		$result = $this->db->query('SELECT * FROM module LEFT JOIN module_status USING(id_module_status) WHERE nama_module = ?', [$nama_module])
						->getRowArray();
		return $result;
	}
	
	public function getMenu($current_module = '') {
				
		if (empty($_SESSION['user']['role'])) {
			return [];
		}
		// Menu
	$sql = 'SELECT menu.*, menu_role.*, module.*, 
				menu_kategori.id_menu_kategori, 
				menu_kategori.nama_kategori, 
				menu_kategori.aktif AS kategori_aktif,
				menu_kategori.urut AS kategori_urut
			FROM menu 
				LEFT JOIN menu_role USING (id_menu) 
				LEFT JOIN module USING (id_module)
				LEFT JOIN menu_kategori ON menu.id_menu_kategori = menu_kategori.id_menu_kategori
			WHERE (menu_kategori.aktif = "Y" OR menu.id_menu_kategori = 0 OR menu.id_menu_kategori IS NULL) 
				AND ( id_role IN ( ' . join(',', array_keys($_SESSION['user']['role'])) . ') )
			ORDER BY COALESCE(menu_kategori.urut, 999), menu.urut';
					
	$query_result = $this->db->query($sql)->getResultArray();
	
	$current_id = '';
	$menu = [];
	foreach ($query_result as $val) 
	{
		// Ensure id_menu_kategori is always set (default to 0 if NULL)
		if (!isset($val['id_menu_kategori']) || $val['id_menu_kategori'] === NULL) {
			$val['id_menu_kategori'] = 0;
		}
		
		$menu[$val['id_menu']] = $val;
		$menu[$val['id_menu']]['highlight'] = 0;
		$menu[$val['id_menu']]['depth'] = 0;

		if ($current_module == $val['nama_module']) {
			
			$current_id = $val['id_menu'];
			$menu[$val['id_menu']]['highlight'] = 1;
		}
		
	}

	if ($current_id) {
		$this->menuCurrent($menu, $current_id);
	}
	
	$menu_kategori = [];
	foreach ($menu as $id_menu => $val) {
		if (!$id_menu)
			continue;
		
		// Safely access id_menu_kategori with fallback to 0
		$kategori_id = isset($val['id_menu_kategori']) ? $val['id_menu_kategori'] : 0;
		
		// Use $id_menu from loop key instead of $val['id_menu'] to avoid undefined key error
		$menu_kategori[$kategori_id][$id_menu] = $val;
	}
		
		// Kategori
		$sql = 'SELECT * FROM menu_kategori WHERE aktif = "Y" ORDER BY urut';
		$query_result = $this->db->query($sql)->getResultArray();
		$result = [];
		foreach ($query_result as $val) {
			if (key_exists($val['id_menu_kategori'], $menu_kategori)) {
				$result[$val['id_menu_kategori']] = [ 'kategori' => $val, 'menu' => $menu_kategori[$val['id_menu_kategori']] ];
			}
		}		
		// echo '<pre>'; print_r($result); die;
		return $result;
	}
	
	// Highlight child and parent
	private function menuCurrent( &$result, $current_id) 
	{
		$parent = $result[$current_id]['id_parent'];

		$result[$parent]['highlight'] = 1; // Highlight menu parent
		if (@$result[$parent]['id_parent']) {
			$this->menuCurrent($result, $parent);
		}
	}
	
	public function getModulePermission($id_module) {
		$sql = 'SELECT * FROM module_permission LEFT JOIN role_module_permission USING (id_module_permission) WHERE id_module = ?';
		
		$result = $this->db->query($sql, [$id_module])->getResultArray();
		return $result;
	}
	
	public function getAllModulePermission($id_user) {
		$sql = 'SELECT * FROM role_module_permission
				LEFT JOIN module_permission USING(id_module_permission)
				LEFT JOIN module USING(id_module)
				LEFT JOIN user_role USING(id_role)
				WHERE id_user = ?';
						
		$result = $this->db->query($sql, $id_user)->getResultArray();
		return $result;
	}
	
	public function validateFormToken($session_name = null, $post_name = 'form_token') {				

		$form_token = explode (':', $this->request->getPost($post_name));
		
		$form_selector = $form_token[0];
		$sess_token = $this->session->get('token');
		if ($session_name)
			$sess_token = $sess_token[$session_name];
	
		if (!key_exists($form_selector, $sess_token))
				return false;
		
		try {
			$equal = $this->auth->validateToken($sess_token[$form_selector], $form_token[1]);

			return $equal;
		} catch (\Exception $e) {
			return false;
		}
		
		return false;
	}
	
	// For role check BaseController->cekHakAkses
	public function getDataById($table, $column, $id) {
		$sql = 'SELECT * FROM ' . $table . ' WHERE ' . $column . ' = ?';
		return $this->db->query($sql, $id)->getResultArray();
	}
	
	public function checkUser() 
	{
		$setting = $this->getSettingAplikasi();
		$login_data = $this->request->getPost('field_login');
		$query = $this->db->query('SELECT * FROM user WHERE ' . $setting['field_login'] . ' = ?', $login_data);
		$user = $query->getRowArray();
		
		if (!$user)
			return;
		
		$user = $this->getUserById($user['id_user']);
		return $user;
	}
	
	public function getSettingAplikasi() {
		$sql = 'SELECT * FROM setting WHERE type="app"';
		$query = $this->db->query($sql)->getResultArray();
		
		foreach($query as $val) {
			$settingAplikasi[$val['param']] = $val['value'];
		}
		return $settingAplikasi;
	}
	
	public function getSettingRegistrasi() {
		$sql = 'SELECT * FROM setting WHERE type="register"';
		$query = $this->db->query($sql)->getResultArray();
		foreach($query as $val) {
			$setting_register[$val['param']] = $val['value'];
		}
		return $setting_register;
	}
	
	public function getIdentitas() {
		$sql = 'SELECT * FROM identitas 
				LEFT JOIN wilayah_kelurahan USING(id_wilayah_kelurahan)
				LEFT JOIN wilayah_kecamatan USING(id_wilayah_kecamatan)
				LEFT JOIN wilayah_kabupaten USING(id_wilayah_kabupaten)
				LEFT JOIN wilayah_propinsi USING(id_wilayah_propinsi)';
		return $this->db->query($sql)->getRowArray();
	}
	
	public function getSetting($type) {
		$sql = 'SELECT * FROM setting WHERE type = ?'; 
		return $this->db->query($sql, $type)->getResultArray();
	}
	
	public function resetAutoIncrement($table) {
		$sql = 'SELECT COUNT(*) AS jml FROM ' . $table;
		$data = $this->db->query($sql)->getRowArray();
		$new_increment = $data ? $data['jml'] + 1 : 1;
		$sql = 'ALTER TABLE ' . $table . ' AUTO_INCREMENT ' . $new_increment;
		$this->db->query($sql);
		return $this->db->affectedRows();
	}
}