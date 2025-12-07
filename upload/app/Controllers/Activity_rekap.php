<?php
/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

namespace App\Controllers;
use App\Models\ActivityModel;
use App\Models\CompanyModel;
use App\Libraries\JWDPDF;

require ROOTPATH . 'app/ThirdParty/PhpSpreadsheet/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Activity_rekap extends \App\Controllers\BaseController
{
	public function __construct() {
		
		parent::__construct();
		
		$this->model = new ActivityModel;	
		$this->data['title'] = 'Rekap Activity';
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/moment/moment.min.js');
		$this->addJs ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.js');
		$this->addStyle ( $this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.css');
		$this->addJs ( $this->config->baseURL . 'public/vendors/filesaver/FileSaver.js');
		
		$this->addJs ( $this->config->baseURL . 'public/vendors/jquery.select2/js/select2.full.min.js' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/css/select2.min.css' );
		$this->addStyle ( $this->config->baseURL . 'public/vendors/jquery.select2/bootstrap-5-theme/select2-bootstrap-5-theme.min.css' );
		
		$this->addJs ( $this->config->baseURL . 'public/themes/modern/js/activity-rekap.js');
	}
	
	public function index() {
		
		// Get users
		$result = $this->model->getAllUser();
		
		// Build user array
		$user = ['' => 'Semua'];
		
		foreach ($result as $val) {
			if($val['id_user'] != '1'){
				$user[$val['id_user']] = $val['nama'];
			}
		}
		
		$this->data['user'] = $user;
		
		// Get companies
		$companyModel = new CompanyModel;
		$companies = $companyModel->getAllCompanies();
		$company_list = ['' => 'Semua'];
		foreach ($companies as $comp) {
			$company_list[$comp->id_company] = $comp->nama_company;
		}
		$this->data['company'] = $company_list;
		
		// Debug: Show what we received
		$this->data['debug_get'] = $_GET;
		
		// Get status filter (default: 'pending')
		$status_filter = $_GET['status'] ?? 'pending';
		
		// Get activities if filters are set
		$activities = [];
		$pager = null;
		if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
			$id_user = $_GET['id_user'] ?? null;
			$id_company = $_GET['id_company'] ?? null;
			
			// Convert empty string to null
			if ($id_user === '') $id_user = null;
			if ($id_company === '') $id_company = null;
			
			// Get pagination parameters
			$page = (int)($_GET['page'] ?? 1);
			$perPage = 20; // Items per page
			
			$result = $this->getActivitiesData(
				$_GET['start_date'], 
				$_GET['end_date'],
				$id_user,
				$id_company,
				$status_filter,
				$page,
				$perPage
			);
			
			$activities = $result['activities'];
			$pager = $result['pager'];
		}
		
		$this->data['activities'] = $activities;
		$this->data['pager'] = $pager;
		$this->data['start_date'] = $_GET['start_date'] ?? date('Y-m-01');
		$this->data['end_date'] = $_GET['end_date'] ?? date('Y-m-d');
		$this->data['status_filter'] = $status_filter;
		
		$this->view('activity-rekap.php', $this->data);
	}
	
	private function getActivitiesData($start_date, $end_date, $id_user = null, $id_company = null, $status = 'pending', $page = 1, $perPage = 20) {
		$where = ' WHERE tanggal BETWEEN "' . $start_date . '" AND "' . $end_date . '"';
		
		if ($id_user) {
			$where .= ' AND activity.id_user = ' . intval($id_user);
		}
		
		if ($id_company) {
			$where .= ' AND activity.id_company = ' . intval($id_company);
		}
		
		// Add status filter (if not 'all')
		if ($status && $status !== 'all') {
			$where .= ' AND activity.status = "' . addslashes($status) . '"';
		}
		
		// Check permission - only filter by user if they don't have read_all permission
		if (!has_permission('read_all') && has_permission('read_own')) {
			$where .= ' AND activity.id_user = ' . $this->session->get('user')['id_user'];
		}
		
		$db = \Config\Database::connect();
		
		// Count total records for pagination
		$countSql = 'SELECT COUNT(*) as total
				FROM activity
				LEFT JOIN company USING(id_company)
				LEFT JOIN user USING(id_user)
				LEFT JOIN user AS approver ON activity.approved_by = approver.id_user
				' . $where;
		
		$totalResult = $db->query($countSql)->getRow();
		$total = (int)($totalResult->total ?? 0);
		
		// Calculate pagination
		$offset = ($page - 1) * $perPage;
		
		// Build main query with pagination
		$sql = 'SELECT activity.*, 
					company.nama_company, 
					user.nama, 
					user.nip,
					approver.nama AS approved_by_name
				FROM activity
				LEFT JOIN company USING(id_company)
				LEFT JOIN user USING(id_user)
				LEFT JOIN user AS approver ON activity.approved_by = approver.id_user
				' . $where . '
				ORDER BY tanggal DESC, waktu DESC
				LIMIT ' . intval($perPage) . ' OFFSET ' . intval($offset);
		
		// Debug: Write SQL to a temp file for easy viewing
		if (ENVIRONMENT === 'development') {
			file_put_contents(WRITEPATH . 'logs/last_activity_query.txt', 
				"SQL: " . $sql . "\n" .
				"Start: " . $start_date . "\n" .
				"End: " . $end_date . "\n" .
				"User: " . ($id_user ?? 'null') . "\n" .
				"Company: " . ($id_company ?? 'null') . "\n" .
				"Status: " . $status . "\n" .
				"Page: " . $page . "\n" .
				"PerPage: " . $perPage . "\n" .
				"Total: " . $total . "\n" .
				"Time: " . date('Y-m-d H:i:s') . "\n"
			);
		}
		
		$result = $db->query($sql)->getResult();
		
		// Create pager
		$pager = service('pager');
		$pager->store('default', $page, $perPage, $total);
		
		// Set path for pager links to preserve GET parameters
		$currentUrl = current_url();
		$pager->setPath($currentUrl, 'default');
		
		return [
			'activities' => $result,
			'pager' => $pager
		];
	}
	
	public function generateExcel() 
	{
		$start_date = $_GET['start_date'] ?? date('Y-m-01');
		$end_date = $_GET['end_date'] ?? date('Y-m-d');
		$id_user = $_GET['id_user'] ?? null;
		$id_company = $_GET['id_company'] ?? null;
		$status = $_GET['status'] ?? 'pending';
		
		// Convert empty string to null
		if ($id_user === '') $id_user = null;
		if ($id_company === '') $id_company = null;
		
		// Get all activities (no pagination for Excel export)
		$result = $this->getActivitiesData($start_date, $end_date, $id_user, $id_company, $status, 1, 999999);
		$activities = $result['activities'];
		
		$excel = new Spreadsheet();
		
		// Set document properties
		$excel->getProperties()->setCreator('Jagowebdev.com')
			->setLastModifiedBy('Jagowebdev.com')
			->setTitle('Rekap Activity')
			->setSubject('Rekap Activity')
			->setDescription('Rekap Activity')
			->setKeywords('Rekap Activity')
			->setCategory('Rekap Activity');
		
		$excel->setActiveSheetIndex(0);
		$sheet = $excel->getActiveSheet()->setTitle('ACTIVITY');
		
		// Set column widths
		$sheet->getColumnDimension('A')->setWidth(5);
		$sheet->getColumnDimension('B')->setWidth(12);
		$sheet->getColumnDimension('C')->setWidth(20);
		$sheet->getColumnDimension('D')->setWidth(20);
		$sheet->getColumnDimension('E')->setWidth(12);
		$sheet->getColumnDimension('F')->setWidth(10);
		$sheet->getColumnDimension('G')->setWidth(30);
		$sheet->getColumnDimension('H')->setWidth(40);
		$sheet->getColumnDimension('I')->setWidth(15);
		$sheet->getColumnDimension('J')->setWidth(15);
		$sheet->getColumnDimension('K')->setWidth(12);
		
		// Header
		$sheet->setCellValue('A1', 'No');
		$sheet->setCellValue('B1', 'Tanggal');
		$sheet->setCellValue('C1', 'NIP');
		$sheet->setCellValue('D1', 'Nama');
		$sheet->setCellValue('E1', 'Company');
		$sheet->setCellValue('F1', 'Waktu');
		$sheet->setCellValue('G1', 'Judul Activity');
		$sheet->setCellValue('H1', 'Deskripsi');
		$sheet->setCellValue('I1', 'Latitude');
		$sheet->setCellValue('J1', 'Longitude');
		$sheet->setCellValue('K1', 'Status');
		
		// Style header
		$sheet->getStyle('A1:K1')->getFont()->setBold(true);
		$sheet->getStyle('A1:K1')
			->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()
			->setARGB('d1e7dd');
		
		// Data
		$no = 1;
		$row = 2;
		foreach ($activities as $activity) {
			$sheet->setCellValue('A' . $row, $no);
			$sheet->setCellValue('B' . $row, $activity->tanggal);
			$sheet->setCellValue('C' . $row, $activity->nip);
			$sheet->setCellValue('D' . $row, $activity->nama);
			$sheet->setCellValue('E' . $row, $activity->nama_company);
			$sheet->setCellValue('F' . $row, $activity->waktu);
			$sheet->setCellValue('G' . $row, $activity->judul_activity);
			$sheet->setCellValue('H' . $row, $activity->deskripsi_activity);
			$sheet->setCellValue('I' . $row, $activity->latitude);
			$sheet->setCellValue('J' . $row, $activity->longitude);
			
			// Status with color
			$status_text = strtoupper($activity->status);
			$bgcolor = 'fff3cd'; // pending = yellow
			if ($activity->status == 'approved') {
				$bgcolor = 'd1e7dd'; // approved = green
			} elseif ($activity->status == 'rejected') {
				$bgcolor = 'f8d7da'; // rejected = red
			}
			
			$sheet->setCellValue('K' . $row, $status_text);
			$sheet->getStyle('K' . $row)
				->getFill()
				->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
				->getStartColor()
				->setARGB($bgcolor);
			
			$no++;
			$row++;
		}
		
		// Alignment
		$sheet->getStyle('A1:K' . ($row - 1))
			->getAlignment()
			->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
		
		$sheet->getStyle('A1:A' . ($row - 1))
			->getAlignment()
			->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		
		$sheet->getStyle('K1:K' . ($row - 1))
			->getAlignment()
			->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
		
		// Borders
		$styleArray = [
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
					'color' => ['argb' => '000000'],
				],
			],
		];
		
		$sheet->getStyle('A1:K' . ($row - 1))->applyFromArray($styleArray);
		
		// Wrap text for description
		$sheet->getStyle('H2:H' . ($row - 1))->getAlignment()->setWrapText(true);
		
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Rekap_Activity_' . $start_date . '_to_' . $end_date . '.xlsx"');
		header('Cache-Control: max-age=0');
		header('Cache-Control: max-age=1');
		
		$writer = new Xlsx($excel);
		$writer->save('php://output');
	}
	
	public function ajaxExportExcel() 
	{
		$this->generateExcel(); 
	}
	
	public function ajaxApproveAll() {
		// Check permission
		$this->hasPermission('approve');
		
		// Get activity IDs from POST (new approach with checkboxes)
		$activity_ids = $this->request->getPost('activity_ids');
		
		// If activity_ids provided, use them (checkbox selection)
		if (!empty($activity_ids) && is_array($activity_ids)) {
			// Validate and sanitize IDs
			$activity_ids = array_map('intval', $activity_ids);
			$activity_ids = array_filter($activity_ids, function($id) {
				return $id > 0;
			});
			
			if (empty($activity_ids)) {
				echo json_encode(['status' => 'error', 'message' => 'Tidak ada activity yang dipilih']);
				return;
			}
			
			// Get current user ID
			$id_user_approver = $this->session->get('user')['id_user'];
			
			// Verify all activities are pending before approving
			$db = \Config\Database::connect();
			$pending_activities = $db->table('activity')
				->whereIn('id_activity', $activity_ids)
				->where('status', 'pending')
				->get()
				->getResult();
			
			if (empty($pending_activities)) {
				echo json_encode(['status' => 'error', 'message' => 'Tidak ada activity pending yang dapat diapprove']);
				return;
			}
			
			// Approve each selected activity
			$approved_count = 0;
			$failed_count = 0;
			
			foreach ($pending_activities as $activity) {
				$result = $this->model->approveActivity($activity->id_activity, $id_user_approver);
				if ($result) {
					$approved_count++;
				} else {
					$failed_count++;
				}
			}
			
			if ($approved_count > 0) {
				$message = 'Berhasil approve ' . $approved_count . ' activity';
				if ($failed_count > 0) {
					$message .= '. Gagal approve ' . $failed_count . ' activity';
				}
				echo json_encode(['status' => 'ok', 'message' => $message, 'approved_count' => $approved_count, 'failed_count' => $failed_count]);
			} else {
				echo json_encode(['status' => 'error', 'message' => 'Gagal approve semua activity']);
			}
			return;
		}
		
		// Fallback: Filter-based approach (backward compatibility)
		$start_date = $this->request->getPost('start_date') ?? $this->request->getGet('start_date') ?? null;
		$end_date = $this->request->getPost('end_date') ?? $this->request->getGet('end_date') ?? null;
		$id_user = $this->request->getPost('id_user') ?? $this->request->getGet('id_user') ?? null;
		$id_company = $this->request->getPost('id_company') ?? $this->request->getGet('id_company') ?? null;
		
		// Validate required parameters
		if (!$start_date || !$end_date) {
			echo json_encode(['status' => 'error', 'message' => 'Tanggal mulai dan selesai harus diisi']);
			return;
		}
		
		// Convert empty string to null
		if ($id_user === '') $id_user = null;
		if ($id_company === '') $id_company = null;
		
		// Get activities matching filters
		$activities = $this->getActivitiesData($start_date, $end_date, $id_user, $id_company);
		
		// Filter to only pending activities
		$pending_activities = [];
		foreach ($activities as $activity) {
			if ($activity->status == 'pending') {
				$pending_activities[] = $activity;
			}
		}
		
		if (empty($pending_activities)) {
			echo json_encode(['status' => 'error', 'message' => 'Tidak ada activity pending yang dapat diapprove']);
			return;
		}
		
		// Get current user ID
		$id_user_approver = $this->session->get('user')['id_user'];
		
		// Approve each pending activity
		$approved_count = 0;
		$failed_count = 0;
		
		foreach ($pending_activities as $activity) {
			$result = $this->model->approveActivity($activity->id_activity, $id_user_approver);
			if ($result) {
				$approved_count++;
			} else {
				$failed_count++;
			}
		}
		
		if ($approved_count > 0) {
			$message = 'Berhasil approve ' . $approved_count . ' activity';
			if ($failed_count > 0) {
				$message .= '. Gagal approve ' . $failed_count . ' activity';
			}
			echo json_encode(['status' => 'ok', 'message' => $message, 'approved_count' => $approved_count, 'failed_count' => $failed_count]);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Gagal approve semua activity']);
		}
	}
}

