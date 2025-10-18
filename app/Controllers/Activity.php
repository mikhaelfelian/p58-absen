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

class Activity extends BaseController
{
	public function __construct() {
		parent::__construct();
		$this->model = new ActivityModel;
		$this->data['title'] = 'Activity Report';
		
		$this->addJs($this->config->baseURL . 'public/vendors/moment/moment.min.js');
		$this->addJs($this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/daterangepicker/daterangepicker.css');
		$this->addJs($this->config->baseURL . 'public/vendors/glightbox/js/glightbox.min.js');
		$this->addStyle($this->config->baseURL . 'public/vendors/glightbox/css/glightbox.min.css');
		$this->addJs($this->config->baseURL . 'public/themes/modern/js/activity.js');
	}
	
	public function index() {
		if (has_permission('read_all')) {
			$companyModel = new CompanyModel;
			$this->data['companies'] = $companyModel->getActiveCompanies();
		}
		
		$this->data['start_date'] = date('01-m-Y');
		$this->data['end_date'] = date('d-m-Y');
		
		$this->view('activity-list.php', $this->data);
	}
	
	public function detail() {
		$id = $this->request->getGet('id');
		$activity = $this->model->getActivityById($id);
		
		if (!$activity) {
			$this->errorDataNotFound();
			return;
		}
		
		$this->data['activity'] = $activity;
		$this->view('activity-detail.php', $this->data);
	}
	
	public function ajaxApprove() {
		$this->hasPermission('approve');
		
		$id = $this->request->getPost('id');
		$id_user = $this->session->get('user')['id_user'];
		
		$result = $this->model->approveActivity($id, $id_user);
		
		if ($result) {
			echo json_encode(['status' => 'ok', 'message' => 'Activity berhasil diapprove']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Gagal approve activity']);
		}
	}
	
	public function ajaxReject() {
		$this->hasPermission('approve');
		
		$id = $this->request->getPost('id');
		$reason = $this->request->getPost('reason');
		$id_user = $this->session->get('user')['id_user'];
		
		$result = $this->model->rejectActivity($id, $id_user, $reason);
		
		if ($result) {
			echo json_encode(['status' => 'ok', 'message' => 'Activity berhasil direject']);
		} else {
			echo json_encode(['status' => 'error', 'message' => 'Gagal reject activity']);
		}
	}
	
	public function getDataDT() {
		$num_data = $this->model->countAllData();
		$result['draw'] = $this->request->getPost('draw') ?: 1;
		$result['recordsTotal'] = $num_data;
		
		$query = $this->model->getListData();
		$result['recordsFiltered'] = $query['total_filtered'];
		
		helper('html');
		
		$no = $this->request->getPost('start') + 1 ?: 1;
		foreach ($query['data'] as $key => &$val) {
			$val->ignore_urut = $no;
			
			// Status badge
			if ($val->status == 'approved') {
				$status_badge = '<span class="badge bg-success">Approved</span>';
			} elseif ($val->status == 'rejected') {
				$status_badge = '<span class="badge bg-danger">Rejected</span>';
			} else {
				$status_badge = '<span class="badge bg-warning">Pending</span>';
			}
			$val->status = $status_badge;
			
			// Photo thumbnail
			if ($val->foto_activity) {
				$val->foto_activity = '<a href="' . $this->config->baseURL . 'public/images/activity/' . $val->foto_activity . '" class="glightbox">
					<img src="' . $this->config->baseURL . 'public/images/activity/' . $val->foto_activity . '" style="width:50px;height:50px;object-fit:cover;" class="rounded">
				</a>';
			} else {
				$val->foto_activity = '-';
			}
			
			// Actions
			$val->ignore_action = '<div class="btn-action-group">' . 
				btn_label(['label' => 'Detail'
						, 'icon' => 'fas fa-eye'
						, 'attr' => ['class' => 'btn btn-info btn-xs me-1'
									, 'href' => $this->moduleURL . '/detail?id=' . $val->id_activity
									, 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'View Detail'
								]
						]
				);
			
			if (has_permission('approve') && $val->status == 'pending') {
				$val->ignore_action .= btn_label(['label' => 'Approve'
						, 'icon' => 'fas fa-check'
						, 'attr' => ['class' => 'btn btn-success btn-xs me-1 btn-approve'
									, 'data-id' => $val->id_activity
									, 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Approve'
								]
						]) . 
					btn_label(['label' => 'Reject'
						, 'icon' => 'fas fa-times'
						, 'attr' => ['class' => 'btn btn-danger btn-xs btn-reject'
									, 'data-id' => $val->id_activity
									, 'data-bs-toggle' => 'tooltip', 'data-bs-title' => 'Reject'
								]
						]);
			}
			
			$val->ignore_action .= '</div>';
			
			$no++;
		}
		
		$result['data'] = $query['data'];
		echo json_encode($result);
	}
}

