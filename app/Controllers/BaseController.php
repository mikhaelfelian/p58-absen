<?php 
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;

use App\Libraries\Auth;
use Config\App;
use App\Models\BaseModel;

require_once(APPPATH . 'Controllers/MasterController.php');

class BaseController extends \MasterController
{
	protected $data;
	protected $config;
	protected $session;
	protected $router;
	public $request;
	protected $isLoggedIn;
	protected $auth;
	protected $user;
	protected $model;
	
	public $currentModule;
	private $controllerName;
	private $methodName;
	private $defaultPathUrl;
	// protected $actionUser;
	protected $moduleURL;
	// protected $moduleRole;
	protected $modulePermission;
	protected $userPermission;
	protected $settingAplikasi;
	private $listFieldLogin = ['username' => 'Username', 'nip' => 'NIP', 'email' => 'Email'];
	
	/*
	Alur:
	Sistem akan menegcek router.
	Default router adalah login (app\Config\Routes.php) $routes->get('/', 'Login::index');
	Selanjutnya kaan dieksekusi fungsi $this->loginRestricted();
	Kemudian sistem akan mengakses controller login
	Pada controller login dieksekusi fungsi $this->mustNotLoggedIn();
	*/
	public function __construct() 
	{
		// parent::__construct();
		
		date_default_timezone_set('Asia/Jakarta');
		$this->session = \Config\Services::session();
		$this->request = \Config\Services::request();
		$this->config = new App;
		$this->auth = new Auth;
		$this->model = new BaseModel;
		helper('util');
		$web = $this->session->get('web');

		$nama_module = $web['nama_module'];
		$module = $this->model->getModule($nama_module);
		
		if (!$module) {
			$this->data['status'] = 'error';
			$this->data['title'] = 'ERROR';
			$this->data['content'] = 'Module ' . $nama_module . ' tidak ditemukan di database';
			$this->viewError($this->data);
			exit();
		}
		// print_r($module);die;
		$this->currentModule = $module;
		$this->session->set('current_module', $module);
		$this->moduleURL = $web['module_url'];
		
		$this->model->checkRememberme();
		$this->isLoggedIn = $this->session->get('logged_in');
		$this->data['current_module'] = $this->currentModule;
		$this->data['scripts'] = array($this->config->baseURL . '/public/assets/vendors/jquery/jquery.min.js'
										// , $this->config->baseURL . '/public/assets/vendors/flatpickr/flatpickr.min.js'
										, $this->config->baseURL . '/public/themes/modern/assets/js/site.js?r='.time()
										, $this->config->baseURL . '/public/assets/vendors/bootstrap/js/bootstrap.js'
								);
		$this->data['styles'] = array(
									// $this->config->baseURL . '/public/assets/vendors/bootstrap/css/flatpickr.min.css'
									$this->config->baseURL . '/public/assets/vendors/bootstrap/css/bootstrap.css'
									, $this->config->baseURL . '/public/themes/modern/assets/css/site.css?r='.time()
								);
		
		$this->settingAplikasi = $this->model->getSettingAplikasi();
		$this->data['config'] = $this->config;
		$this->data['request'] = $this->request;
		$this->data['isloggedin'] = $this->isLoggedIn;
		$this->data['session'] = $this->session;
		$this->data['site_title'] = 'Aplikasi Absen Karyawan';
		$this->data['site_desc'] = 'Aplikasi absensi karyawan lengkap dengan berbagai fitur untuk memudahkan administrasi presensi karyawan';
		$this->data['setting_aplikasi'] = $this->settingAplikasi;
		$this->data['user'] = [];
		$this->data['auth'] = $this->auth;
		$this->data['scripts'] = [];
		$this->data['styles'] = [];
		$this->data['module_url'] = $this->moduleURL;
		$this->data['list_field_login'] = $this->listFieldLogin;
		// echo '<pre>';
			// print_r($this->session->get('user'));
			// die;
		
		if ($this->isLoggedIn) {
			$user_setting = $this->model->getUserSetting();
			
			if ($user_setting) {
				$this->data['app_layout'] = json_decode($user_setting->param, true);
			}
		} else {
			$query = $this->model->getAppLayoutSetting();
			foreach ($query as $val) {
				$app_layout[$val['param']] = $val['value'];
			}
			$this->data['app_layout'] = $app_layout;
		}
		
		// Login? Yes, No, Restrict
		if ($this->currentModule['login'] == 'Y' && $nama_module != 'login') {
			$this->loginRequired();
		} else if ($this->currentModule['login'] == 'R') {
			$this->loginRestricted();
		}
		
		if ($this->isLoggedIn) 
		{
			$this->user = $this->session->get('user');
			$this->data['user'] = $this->user;
			
			if ($this->user['default_page_type'] == 'id_module') {
				$this->defaultPathUrl = isset($this->user['default_module']['nama_module']) ? $this->user['default_module']['nama_module'] : 'dashboard';
			} else {
				// Safely access role with fallback
				$default_page_id_role = isset($this->user['default_page_id_role']) ? $this->user['default_page_id_role'] : null;
				if ($default_page_id_role && isset($this->user['role'][$default_page_id_role]['nama_module'])) {
					$this->defaultPathUrl = $this->user['role'][$default_page_id_role]['nama_module'];
				} else {
					$this->defaultPathUrl = 'dashboard'; // Fallback to dashboard
				}
			}
						
			// List action assigned to role
			$this->data['menu'] = $this->model->getMenu($this->currentModule['nama_module']);
			
			$this->data['breadcrumb'] = ['Home' => $this->config->baseURL, $this->currentModule['judul_module'] => $this->moduleURL];
			$this->data['module_role'] = $this->model->getDefaultUserModule();
			$this->data['data_setelah_nama_user'] = $this->getDataSetelahNamaUser();
						
			/* $this->getModuleRole();
			$this->getListAction(); */
			
			$result = $this->model->getAllModulePermission($this->user['id_user']);
			$all_module_permission = [];
			if ($result) {
				foreach ($result as $val) {
					$all_module_permission[$val['id_module']][$val['nama_permission']] = $val;
				}
			}
			
			$this->user['all_permission'] = $all_module_permission;
			$this->session->set('user', $this->user);
			
			$this->modulePermission = $this->getModulePermission($this->model, $this->session);
			$user_permission = $this->getListPermission($this->model, $this->session);
			if ($user_permission['status'] == 'error') {
				$this->printError($user_permission['message']);
				exit;
			}
		
			$this->userPermission = $user_permission['data'];
			$this->data['action_user'] = $this->userPermission;
			
			$seting_waktu_presensi = $this->model->getSettingPresensi();
			$setting_presensi = $this->getSetting('presensi');
			$this->data['setting_presensi'] = array_merge($seting_waktu_presensi, $setting_presensi);
			
			// Check Global Role Action
			$this->checkRoleAction();
			if ($nama_module == 'login') {
				$this->redirectOnLoggedIn();
			}
		}
		
		if ($module['id_module_status'] != 1) {
			$message = 'Module ' . $module['judul_module'] . ' sedang ' . strtolower($module['nama_status']);
			$this->data['status'] = 'error';
			$this->data['title'] = 'ERROR';
			$this->data['content'] = $message;
			$this->printError(['message' => $message, 'status' => 'error']);
			exit();
		}
	}
	
	public function getIdentitas() {
		return $this->model->getIdentitas();
	}
	
	public function getSetting($type) {
		$setting = $this->model->getSetting($type);
		$result = [];
		foreach ($setting as $val) {
			$result[$val['param']] = $val['value'];
		}
		
		return $result;
	}
	
	private function setCurrentModule($module) {
		$this->currentModule['nama_module'] = $module;
	}
	
	protected function getControllerName() {
		return $this->controllerName;
	}
	
	protected function getMethodName() {
		return $this->methodName;
	}
	
	protected function addStyle($file) {
		$this->data['styles'][] = $file;
	}
	
	protected function addJs($file, $print = false) {
		if ($print) {
			$this->data['scripts'][] = ['print' => true, 'script' => $file];
		} else {
			$this->data['scripts'][] = $file;
		}
	}
	
	protected function viewError($data) {
	
		echo view('app_error.php', $data);
	}
	
	protected function loginRequired() 
	{
		if (!$this->isLoggedIn) {
			header('Location: ' . $this->config->baseURL . 'login');
			// redirect()->to($this->config->baseURL . 'login');
			exit();
		}
	}
	
	protected function loginRestricted() {
		if ($this->isLoggedIn) {
			if ($this->methodName !== 'logout') {
				header('Location: ' . $this->config->baseURL);
			}
		}
	}
	
	protected function redirectOnLoggedIn() {
		if ($this->isLoggedIn) {
			// header('Location: ' . $this->config->baseURL . $this->user['default_module']['nama_module']);
			header('Location: ' . $this->config->baseURL . $this->defaultPathUrl);
			// header('Location: ' . $this->config->baseURL . $this->user['default_module']['nama_module']);
			// redirect($this->router->default_controller);
		}
	}
	
	protected function mustNotLoggedIn() {
		if ($this->isLoggedIn) {	
			if ($this->currentModule['nama_module'] == 'login') {
				// header('Location: ' . $this->config->baseURL . $this->data['module_role']->nama_module);
				header('Location: ' . $this->config->baseURL . $this->defaultPathUrl);
				exit();
			}
		}
	}
	
	protected function mustLoggedIn() {
		if (!$this->isLoggedIn) {
			header('Location: ' . $this->config->baseURL . 'login');
			exit();
		}
	}
	
	private function checkRoleAction() 
	{

		if ($this->config->checkRoleAction['enable_global']) 
		{
			$method = $this->session->get('web')['method_name'];
			$list_action = ['add' => 'create', 'edit' => 'update'];
			$list_error = ['add' => 'menambah', 'edit' => 'mengubah'];
			
			$error = false;
			if ($method == 'add' || $method=='edit') 
			{
				if (key_exists($method, $list_action)) {
					
					foreach ($this->userPermission as $val) 
					{
						$exp = explode('_', $val);
						$exists = false;
						
						if ($list_action[$method] == trim($exp[0])) {;
							$exists = true;
							break;
						}
						
					}
					if (!$exists) {
						$error = 'Role Anda tidak memiliki permission untuk ' . $list_error[$method] . ' data module ' . $this->currentModule['judul_module'];
					}
				}
			} else if (!empty($_POST['delete'])) {
				foreach ($this->userPermission as $val) {
					$exp = explode('_', $val);
					$exists = false;
					if (trim($exp[0]) == 'delete') {
						$exists = true;
						break;
					}
				}
				
				if (!$exists) {
					$error = 'Role Anda tidak diperkenankan untuk menghapus data';
				}
			}
			
			if ($error) {
				if (@$_GET['mobile']) {
					echo '<div class="alert alert-danger">' . $error . '</div>';
					exit;
				}
				
				$this->data['msg'] = ['status' => 'error', 'message' => $error];
				$this->view('error.php', $this->data);
				exit;
			}
		}
		
	}
	
	protected function userCan($action) {
		if (!$this->userPermission) {
			return '';
		}
				
		foreach ($this->userPermission as $val) {
			
			$exp = explode('_', $val);
			if (count($exp) == 1) {
				if (trim($exp[0]) == trim($action)) {
					return true;
				}
			} else {
						
				if ($exp[0] == $action) {
					if ($exp[1] == 'all') {
						return 'all';
					} else if ($exp[1] == 'own') {
						return 'own';
					}
				}
			}
		}
		return '';
	}
	
	protected function mustHavePermission($permission) {
		
		if (!in_array($permission, $this->userPermission)) {
			$response = service('response');
			$response->setStatusCode(Response::HTTP_UNAUTHORIZED);
			$response->setJSON(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki permission ' . $permission]);
			$response->setHeader('Content-type', 'application/json');
			$response->noCache();
			$response->send();
			exit;
		}
	}
	
	protected function hasPermission($action, $nama_module = '', $exit = false) 
	{
		$exists = $this->hasPermissionModule($action, $nama_module = '', $exit = false);
		if (!$exists && $exit) {
			$this->printError('Anda tidak memiliki permission ' . $action);
			exit;
		}
		return $exists;
	}
	
	protected function hasPermissionPrefix($action, $return = false) {
		
		$has_permission = false;

		foreach ($this->userPermission as $val) 
		{
			$exp = explode('_', $val);
			$user_action = trim($exp[0]);
			if ($user_action == $action || $user_action == $action . '_all') {
				$has_permission = true;
				break;
			}
		}
		
		if (!$has_permission && $return = false) {
			
			$action_title = ['read' => 'melihat data', 'create' => 'menambah data', 'update' => 'mengubah data', 'delete' => 'menghapus data'];
			$this->currentModule['nama_module'] = 'error';
			$this->data['msg'] = ['status' => 'error', 'message' => 'Role Anda tidak diperkenankan untuk pada ' . $action_title[$action]];
			$this->view('error.php', $this->data);
			exit;
		}
		
		return $has_permission;	
	}
	
	public function whereOwn($column = null) 
	{	
		/* if (!$column)
			$column = $this->config->checkRoleAction['field'];
			
		if ($this->actionUser['read_data'] == 'own') {
			return ' WHERE ' . $column . ' = ' . $_SESSION['user']['id_user'];
		}
		
		return ' WHERE 1 = 1 '; */
		
		if (!$column)
			$column = $this->config->checkRoleAction['field'];
			
		if (key_exists('read_own', $this->userPermission) && !key_exists('read_all', $this->userPermission)) {
			return ' WHERE ' . $column . ' = ' . $_SESSION['user']['id_user'];
		}
		
		return ' WHERE 1 = 1 ';
	}
	
	protected function view($file, $data = false, $file_only = false) 
	{
		if (is_array($file)) {
			foreach ($file as $file_item) {
				echo view($file_item, $data);
			}
		} else {
			echo view('themes/modern/header.php', $data);
			echo view('themes/modern/' . $file, $data);
			echo view('themes/modern/footer.php');
		}
	}
	
	protected function printError($message) {
		$this->data['title'] = 'Error...';
		$this->data['message'] = $message;
		$this->view('error.php', $this->data);
	}
	
	protected function exitError($data) {
		echo view('app_error.php', $data);
		exit;
	}
	
	protected function getDataSetelahNamaUser() {
		// Safety check for setting
		$setting = isset($this->settingAplikasi['data_setelah_nama_user']) ? $this->settingAplikasi['data_setelah_nama_user'] : 'nip';
		
		switch ($setting) {
			case 'nip':
				$data_pegawai = 'NIP. ' . (isset($this->user['nip']) ? $this->user['nip'] : 'N/A');
				break;
			case 'nama_jabatan':
				$data_pegawai = isset($this->user['nama_jabatan']) ? $this->user['nama_jabatan'] : 'N/A';
				break;
			case 'email':
				$data_pegawai = 'Email: ' . (isset($this->user['email']) ? $this->user['email'] : 'N/A');
				break;
			default:
				$data_pegawai = 'NIP. ' . (isset($this->user['nip']) ? $this->user['nip'] : 'N/A');
				break;
		}
		return $data_pegawai;
	}
	
	/* Used for modules when edited data not found */
	protected function errorDataNotFound($addData = null) {
		$data = $this->data;
		$data['title'] = 'Error';
		$data['msg']['status'] = 'error';
		$data['msg']['content'] = 'Data tidak ditemukan';
		
		if ($addData) {
			$data = array_merge($data, $addData);
		}
		$this->view('error-data-notfound.php', $data);
	}
}