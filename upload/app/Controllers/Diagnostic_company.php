<?php
/**
*	Diagnostic Tool for Company Assignment
*	Temporary file for debugging
*/

namespace App\Controllers;
use App\Models\UserCompanyModel;

class Diagnostic_company extends \App\Controllers\BaseController
{
	public function index() {
		$id_user = $this->session->get('user')['id_user'];
		$userCompanyModel = new UserCompanyModel;
		
		echo '<h2>Company Assignment Diagnostic</h2>';
		echo '<p>User ID: ' . $id_user . '</p>';
		echo '<p>Today: ' . date('Y-m-d') . '</p>';
		echo '<hr>';
		
		// Check all user_company records
		$sql1 = 'SELECT * FROM user_company WHERE id_user = ?';
		$all_assignments = $this->model->db->query($sql1, [$id_user])->getResult();
		
		echo '<h3>1. All User-Company Assignments (' . count($all_assignments) . ')</h3>';
		if (empty($all_assignments)) {
			echo '<p style="color:red;">❌ No assignments found in user_company table</p>';
		} else {
			echo '<table border="1" cellpadding="5">';
			echo '<tr><th>ID</th><th>Company ID</th><th>Status</th><th>Start Date</th><th>End Date</th></tr>';
			foreach ($all_assignments as $row) {
				echo '<tr>';
				echo '<td>' . $row->id_user_company . '</td>';
				echo '<td>' . $row->id_company . '</td>';
				echo '<td>' . $row->status . '</td>';
				echo '<td>' . ($row->tanggal_mulai ?? 'NULL') . '</td>';
				echo '<td>' . ($row->tanggal_selesai ?? 'NULL') . '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
		
		echo '<hr>';
		
		// Check companies
		$sql2 = 'SELECT company.* FROM user_company 
				LEFT JOIN company USING(id_company)
				WHERE id_user = ?';
		$companies = $this->model->db->query($sql2, [$id_user])->getResult();
		
		echo '<h3>2. Companies Details (' . count($companies) . ')</h3>';
		if (empty($companies)) {
			echo '<p style="color:red;">❌ No companies found</p>';
		} else {
			echo '<table border="1" cellpadding="5">';
			echo '<tr><th>ID</th><th>Name</th><th>Status</th><th>Latitude</th><th>Longitude</th><th>Radius</th></tr>';
			foreach ($companies as $row) {
				$status_color = $row->status == 'active' ? 'green' : 'red';
				echo '<tr>';
				echo '<td>' . $row->id_company . '</td>';
				echo '<td>' . $row->nama_company . '</td>';
				echo '<td style="color:' . $status_color . '"><strong>' . $row->status . '</strong></td>';
				echo '<td>' . ($row->latitude ?? 'NULL') . '</td>';
				echo '<td>' . ($row->longitude ?? 'NULL') . '</td>';
				echo '<td>' . $row->radius_nilai . ' ' . $row->radius_satuan . '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
		
		echo '<hr>';
		
		// Check active companies (what the app uses)
		$today = date('Y-m-d');
		$sql3 = 'SELECT user_company.*, company.*
				FROM user_company
				LEFT JOIN company USING(id_company)
				WHERE id_user = ? 
				AND user_company.status = "active"
				AND company.status = "active"
				AND (tanggal_mulai IS NULL OR tanggal_mulai <= ?)
				AND (tanggal_selesai IS NULL OR tanggal_selesai >= ?)
				ORDER BY company.nama_company';
		$active_companies = $this->model->db->query($sql3, [$id_user, $today, $today])->getResult();
		
		echo '<h3>3. Active Companies (Filtered) (' . count($active_companies) . ')</h3>';
		echo '<p>This is what the app sees:</p>';
		if (empty($active_companies)) {
			echo '<p style="color:red;">❌ No active companies found</p>';
			echo '<p><strong>Possible reasons:</strong></p>';
			echo '<ul>';
			echo '<li>user_company.status is not "active"</li>';
			echo '<li>company.status is not "active"</li>';
			echo '<li>tanggal_mulai is in the future</li>';
			echo '<li>tanggal_selesai is in the past</li>';
			echo '</ul>';
		} else {
			echo '<p style="color:green;">✅ Found ' . count($active_companies) . ' active company(ies)</p>';
			echo '<table border="1" cellpadding="5">';
			echo '<tr><th>Company Name</th><th>Status</th><th>GPS</th></tr>';
			foreach ($active_companies as $row) {
				echo '<tr>';
				echo '<td>' . $row->nama_company . '</td>';
				echo '<td style="color:green">' . $row->status . '</td>';
				echo '<td>' . $row->latitude . ', ' . $row->longitude . '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
		
		echo '<hr>';
		echo '<p><a href="' . base_url() . 'mobile-presensi-home">← Back to Presensi</a></p>';
	}
}

