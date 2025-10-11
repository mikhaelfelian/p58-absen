<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\Builtin\LoginModel;
use \Config\App;
use App\Libraries\Auth;

class Login extends \App\Controllers\BaseController
{
	protected $model = '';
	
	public function __construct() {
		parent::__construct();
		$this->model = new LoginModel;	
		$this->data['site_title'] = 'Login ke akun Anda';
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/login.js');
		
		helper(['cookie', 'form']);
	}
	
	public function index()
	{
		$this->mustNotLoggedIn();
		$this->data['status'] = '';
		if ($this->request->getPost('password')) {
			
			$this->login();
			if (@$_POST['ajax'] == 'true') {
				if ($this->data['status'] == 'error') {
					echo json_encode(['status' => 'error', 'message' => $this->data['message']]);
				} else {
					echo json_encode(['status' => 'ok']);
				}
				exit;
			}
			
			if ($this->session->get('logged_in')) {
				return redirect()->to($this->config->baseURL);
			}
		}
		
		$query = $this->model->getSettingRegistrasi();
		foreach($query as $val) {
			$this->data['setting_registrasi'][$val['param']] = $val['value'];
		}
		$this->response->setHeader('Required-auth', '1');
		csrf_settoken();
		$this->data['style'] = ' style="max-width:375px"';
		return view('themes/modern/builtin/login', $this->data);
	}
	
	private function login()
	{
		// Check Token
		$validation_message = csrf_validation();

		// Cek CSRF token
		if ($validation_message) {
			$this->data['status'] = 'error';
			$this->data['message'] = $validation_message['message'];
			return;
		}
		
		$error = false;
		$user = $this->model->checkUser();
		if ($user) {
			if ($user['verified'] == 0) {
				$message = 'User belum aktif';
				$error = true;
			}
			
			if (!password_verify($this->request->getPost('password'), $user['password'])) {
				$message = 'Username dan/atau Password tidak cocok';
				$error = true;
			}
			
		} else {
			$message = 'User tidak ditemukan';
			$error = true;
		}
		
		if ($error)
		{
			$this->data['status'] = 'error';
			$this->data['message'] = $message;
			return;
		}
		
		if ($this->request->getPost('remember')) 
		{
			$this->model->setUserToken($user);
		}

		$this->session->set('user', $user);
		$this->session->set('logged_in', true);
		$this->model->recordLogin();
	}
	
	public function refreshLoginData() 
	{
		$email = $this->session->get('user')['email'];
		$result = $this->model->checkUser($email);
		$this->session->set('user', $result);
	}
	
	public function logout() 
	{
		$user = $this->session->get('user');
		if ($user) {
			$this->model->deleteAuthCookie($this->session->get('user')['id_user']);
		}
		$this->session->destroy();
	
		header('location: ' . $this->config->baseURL . 'login');
		exit;
		// return redirect()->to($this->config->baseURL . 'login');
		// exit;
		// return redirect()->to($this->config->baseURL . 'login');
	}
}
