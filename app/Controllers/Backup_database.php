<?php
/**
*	App Name	: Aplikasi Kasir Jagowebdev	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;

require_once(ROOTPATH . 'app/ThirdParty/Mysqldump/autoload.php');
use Ifsnop\Mysqldump as IMysqldump;

class Backup_database extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		$this->data['site_title'] = 'Backup Database';
	}
	
	public function index()
	{
		$message = [];
		$config_database = new \Config\Database();
		$config_database = $config_database->getConnections()['default'];
				
		if (!empty($_POST['submit'])) {
			$this->download();
		}
		$this->data['message'] = $message;
		$this->data['config_database'] = $config_database;
		$this->data['title'] = 'Backup Database';
		$this->view('backup-database-form.php', $this->data);
	}
	
	public function download() 
	{
		$config = new \Config\Database();
		$config = $config->getConnections()['default'];

		try {
		$dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=' . $config->database, $config->username, $config->password);
		
		$path = ROOTPATH . 'public/tmp/';
		$file_sql = 'database_' . time() . '.sql';
		$dump->start( $path . $file_sql);
		
		$file_zip = 'database_' . time() . '.zip';
		$zip = new \ZipArchive();
		$zip->open($path . $file_zip, \ZipArchive::CREATE);
		$zip->addFile($path . $file_sql, $config->database . '_' . date('Y-m-d') . '.sql');
		$zip->close();
		
		unlink($path . $file_sql);
		
		header('Content-Description: File Transfer');
		header("Content-Type: application/octet-stream");
		header("Content-Transfer-Encoding: Binary"); 
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header("Content-Disposition: attachment; filename=\"database_". $config->database . '_' . date('Y-m-d') . ".zip");
		header("Content-Length: " . filesize($path . $file_zip));
		ob_end_clean();
		ob_end_flush();
		readfile($path . $file_zip);
		unlink($path . $file_zip);
		exit;
		
		// $message = ['status' => 'ok', 'message' => 'Database berhasil dibackup'];
			
		} catch (\Exception $e) {
			$message = ['status' => 'error', 'message' => 'mysqldump-php error: ' . $e->getMessage()];
		}
	}
}
