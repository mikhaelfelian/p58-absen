<?= $this->extend('themes/modern/layout-mobile') ?>
<?= $this->section('content') ?>
<?php
$nama_bulan = nama_bulan();
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
// echo date('j'); die;
/* echo '<pre>';
print_r($setting_aplikasi);
die; */
?>
<div class="container mt-4">
	<div class="text-center text-light">
		<h5 class="m-0"><?=$user['nama']?></h5>
		<p class="p-0"><?=$data_setelah_nama_user?></p>
	</div>
	<div class="bg-light p-4 mt-4 mb-4 rounded-3">
		<div class="d-flex justify-content-between">
			<div class="hari-tanggal"><?=$nama_hari[date('w')] . ', ' . date('d') . ' ' . $nama_bulan[date('n')] . ' ' . date('Y')?></div>
			<div class="text-end" id="live-jam"><?=date('H:i:s')?></div>
		</div>
	</div>
	
	<?php 
	// Debug: Check what we have
	$companies_count = is_array($companies) ? count($companies) : (is_object($companies) ? count((array)$companies) : 0);
	log_message('debug', 'mobile-presensi-home: companies variable type=' . gettype($companies) . ', count=' . $companies_count);
	if (isset($companies) && !empty($companies)) {
		log_message('debug', 'mobile-presensi-home: First company id=' . (isset($companies[0]) ? ($companies[0]->id_company ?? 'NO ID') : 'NO FIRST'));
	}
	?>
	<?php if (empty($companies)): ?>
	<div class="alert alert-warning">
		<i class="fas fa-exclamation-triangle me-2"></i>
		Anda belum di-assign ke perusahaan manapun. Silakan hubungi admin untuk melakukan penugasan.
		<?php if (isset($debug_info) && !empty($debug_info)): ?>
		<br><small>Debug: Total penugasan ditemukan: <?=$debug_info['total_assignments']?>, Perusahaan aktif: <?=$debug_info['active_companies']?></small>
		<?php endif; ?>
	</div>
	
	<?php else: ?>
	<?php
	// Check if user has an active shift based on latest record (no date filtering)
	$last = $last_presensi ?? null;
	$active_company_id = null;
	$active_company_name = '';
	$is_readonly = false;
	
	// Convert to array if object for consistent access
	if ($last && is_object($last)) {
		$last = (array) $last;
	}
	
	// If latest record has tgl_keluar IS NULL → user is in active shift
	if ($last && empty($last['tgl_keluar'])) {
		$active_company_id = $last['id_company'] ?? null;
		$is_readonly = true;
		// Get company name
		foreach ($companies as $comp) {
			if ($comp->id_company == $active_company_id) {
				$active_company_name = $comp->nama_company;
				break;
			}
		}
	}
	?>
	<div class="bg-light p-3 mb-3 rounded-3">
		<label class="form-label mb-2"><strong>Lokasi Perusahaan</strong></label>
		<?php if ($is_readonly): ?>
		<input type="text" class="form-control" value="<?=$active_company_name?>" readonly>
		<input type="hidden" id="id_company" name="id_company" value="<?=$active_company_id?>">
		<small class="text-success d-block mt-1">
			<i class="fas fa-lock me-1"></i>
			Perusahaan sudah terpilih untuk shift aktif. Tidak dapat diubah setelah absen masuk.
		</small>
		<?php else: ?>
		<!-- Company Detection with Tabs -->
		<div class="detection-wrapper">
			<ul class="nav nav-pills nav-fill mb-3" id="company-detection-tabs" role="tablist" style="position: relative; z-index: 10;">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" data-bs-toggle="pill" type="button" data-bs-target="#auto-detect-tab">
						<i class="fas fa-location-crosshairs me-2"></i>Auto GPS
					</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" data-bs-toggle="pill" type="button" data-bs-target="#manual-detect-tab">
						<i class="fas fa-list-ul me-2"></i>Pilih Manual
					</button>
				</li>
			</ul>
			<div class="tab-content border rounded-bottom p-3">
				<div class="tab-pane fade show active" id="auto-detect-tab">
					<!-- Auto-detect company based on GPS location -->
					<div id="company-detecting" class="text-center py-3">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Memuat...</span>
						</div>
						<p class="mt-2 mb-0"><small>Mendeteksi lokasi Anda...</small></p>
					</div>
					<div id="company-detected" style="display:none;">
						<div class="alert alert-success mb-0">
							<i class="fas fa-map-marker-alt me-2"></i>
							<strong id="detected-company-name"></strong>
							<span id="detected-company-setting" class="badge bg-info ms-2" style="display:none;"></span>
							<br>
							<small id="detected-company-distance"></small>
						</div>
					</div>
					<div id="company-not-found" style="display:none;">
						<div class="alert alert-danger mb-0">
							<i class="fas fa-exclamation-triangle me-2"></i>
							<strong>Anda tidak berada di lokasi perusahaan manapun!</strong>
							<br>
							<small>Silakan pergi ke lokasi perusahaan yang sudah ditugaskan atau pilih manual.</small>
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="manual-detect-tab">
					<div class="alert alert-warning">
						<i class="fas fa-info-circle me-2"></i>
						GPS sulit mendeteksi lokasi? Pilih perusahaan secara manual.
					</div>
					<div class="mb-3">
						<label class="form-label fw-semibold">Pilih Perusahaan</label>
						<select class="form-select" id="manual-company-select">
							<option value="">-- Pilih Perusahaan --</option>
						</select>
					</div>
					<button type="button" class="btn btn-primary w-100" id="btn-confirm-manual-company" disabled>
						<i class="fas fa-check me-2"></i>Gunakan
					</button>
				</div>
			</div>
		</div>
		<input type="hidden" id="id_company" name="id_company" value="">
		<input type="hidden" id="detected-latitude" value="">
		<input type="hidden" id="detected-longitude" value="">
		<?php endif; ?>
	</div>
	
	<!-- Store companies data for JavaScript -->
	<script>
	var assignedCompanies = <?=json_encode($companies ?? [])?>;
	console.log('assignedCompanies loaded:', assignedCompanies);
	console.log('assignedCompanies type:', typeof assignedCompanies);
	console.log('assignedCompanies length:', assignedCompanies ? assignedCompanies.length : 'N/A');
	if (assignedCompanies && assignedCompanies.length > 0) {
		console.log('First company sample:', assignedCompanies[0]);
	}
	</script>
	<?php endif; ?>
	
	<?php
	// Get company-specific settings (use first company as default for now)
	$company_setting = null;
	if (!empty($companies)) {
		$company_setting = $companies[0]->setting_data ?? null;
	}
	
	// Fallback to global setting if no company setting
	if (!$company_setting) {
		$company_setting = [
			'hari_kerja' => json_decode($setting_presensi['hari_kerja'], true) ?: [1,2,3,4,5],
			'gunakan_foto_selfi' => $setting_presensi['gunakan_foto_selfi'] ?? 'Y',
			'gunakan_radius_lokasi' => $setting_presensi['gunakan_radius_lokasi'] ?? 'Y',
			'latitude' => $setting_presensi['latitude'] ?? '-7.797068',
			'longitude' => $setting_presensi['longitude'] ?? '110.370529',
			'radius_nilai' => $setting_presensi['radius_nilai'] ?? '1.00',
			'radius_satuan' => $setting_presensi['radius_satuan'] ?? 'km'
		];
	}
	
	// Debug: Show company setting hari_kerja
	if (isset($_GET['debug'])) {
		echo '<div class="alert alert-info">';
		echo '<h6>Informasi Debug:</h6>';
		echo '<strong>Hari Kerja Pengaturan Perusahaan:</strong> ';
		print_r($company_setting['hari_kerja']);
		echo '<br><strong>Hari Kerja Pengaturan Global:</strong> ';
		print_r(json_decode($setting_presensi['hari_kerja'], true));
		echo '<br><strong>Jumlah Perusahaan:</strong> ' . count($companies ?? []);
		if (!empty($companies)) {
			echo '<br><strong>Data Pengaturan Perusahaan Pertama:</strong> ';
			print_r($companies[0]->setting_data ?? 'NULL');
		}
		echo '</div>';
	}
	
	// Determine waktu_masuk and waktu_pulang from latest record (no date filtering)
	$waktu_masuk = $waktu_pulang = 'Belum absen';
	$tanggal_masuk = $tanggal_pulang = '';
	
	// Ensure $last is array for consistent access
	$last_array = $last;
	if ($last && is_object($last)) {
		$last_array = (array) $last;
	}
	
	if ($last_array) {
		// If latest record has tgl_keluar IS NULL → show active shift
		if (empty($last_array['tgl_keluar'])) {
			// Extract time and date from tgl_masuk DATETIME
			if (!empty($last_array['tgl_masuk'])) {
				$waktu_masuk = date('H:i', strtotime($last_array['tgl_masuk'])); // HH:MM format
				$tanggal_masuk = date('d/m/Y', strtotime($last_array['tgl_masuk']));
			}
		}
		// If latest record has tgl_keluar → show completed shift
		else if (!empty($last_array['tgl_keluar'])) {
			// Extract time and date from tgl_keluar DATETIME
			$waktu_pulang = date('H:i', strtotime($last_array['tgl_keluar'])); // HH:MM format
			$tanggal_pulang = date('d/m/Y', strtotime($last_array['tgl_keluar']));
			// Also show masuk time from tgl_masuk
			if (!empty($last_array['tgl_masuk'])) {
				$waktu_masuk = date('H:i', strtotime($last_array['tgl_masuk']));
				$tanggal_masuk = date('d/m/Y', strtotime($last_array['tgl_masuk']));
			}
		}
	}
	
	// Check if today is a working day
	$today_day_of_week = date('w'); // 0 = Sunday, 1 = Monday, etc.
	$is_today_working_day = in_array($today_day_of_week, $company_setting['hari_kerja']);
	?>
	
	<?php if (!$is_today_working_day): ?>
	<div class="alert alert-info text-center">
		<i class="fas fa-calendar-times me-2"></i>
		<strong>Hari ini bukan hari kerja</strong><br>
		<small>Presensi hanya dapat dilakukan pada hari kerja yang telah ditentukan.</small>
	</div>
	<?php else: ?>
	<div class="row">
		<div class="col-6 pe-2">
			<a id="presensi-masuk" href="#" class="presensi-container box-absen-masuk d-flex rounded-3 px-4 py-4 w-100">
				<div class="d-flex align-items-center w-100">
					<i class="bi bi-box-arrow-in-right me-3 text-success icon-box-presensi" style="font-size:30px"></i>
					<div class="w-100">
						<h5 class="m-0 p-0">Masuk</h5>
						<p class="mt-0 mb-0 waktu-presensi"><?=$waktu_masuk?></p>
						<?php if ($tanggal_masuk): ?>
						<p class="mt-1 mb-0 text-muted" style="font-size:0.75rem;"><?=$tanggal_masuk?></p>
						<?php endif; ?>
						<?php
						// Show jam kerja target requirement
						$jam_kerja_target = 12; // Default
						if (!empty($companies) && !empty($companies[0]->jam_kerja_target)) {
							$jam_kerja_target = intval($companies[0]->jam_kerja_target);
						}
						?>
					</div>
				</div>
			</a>
		</div>
		<div class="d-flex col-6 ps-2">
			<a id="presensi-pulang" href="#" class="bg-light presensi-container box-absen-pulang rounded-3 px-4 py-4" style="background:#fff6e8 !important;width:100%">
				<div class="d-flex align-items-center">
					<i class="bi bi-box-arrow-right me-3 text-warning icon-box-presensi" style="font-size:27px"></i>
					<div class="w-100">
						<h5 class="m-0 p-0">Pulang</h5>
						<p class="mt-0 mb-0 waktu-presensi"><?=$waktu_pulang?></p>
						<?php if ($tanggal_pulang): ?>
						<p class="mt-1 mb-0 text-muted" style="font-size:0.75rem;"><?=$tanggal_pulang?></p>
						<?php endif; ?>
						<?php
						// Show jam kerja target requirement
						$jam_kerja_target = 12; // Default
						if (!empty($companies) && !empty($companies[0]->jam_kerja_target)) {
							$jam_kerja_target = intval($companies[0]->jam_kerja_target);
						}
						?>
					</div>
				</div>
			</a>
		</div>
	</div>
	<div id="alert-lokasi">
	</div>
	<?php endif; ?>
	<p class="text-light mt-4">
	Riwayat Presensi
	</p>
		<div class="bg-light p-4 rounded-3">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered mb-0">
					<thead class="table-dark">
						<tr>
							<th class="text-center" style="width: 50px;">No</th>
							<th>Tanggal</th>
							<th class="text-center">Masuk</th>
							<th class="text-center">Pulang</th>
							<th class="text-center">Jam Kerja</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$nama_bulan = nama_bulan();
						$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
						$end_date = strtotime(date('Y-m-d'));
						$start_date = strtotime('-' . $setting_presensi['jml_riwayat_presensi_home'] . ' days', $end_date);
						$hari_kerja = $company_setting['hari_kerja'] ?? [1,2,3,4,5];
						$no = 1;
						
						// Collect all attendance records
						$attendance_records = [];
						for ($i = $end_date; $i > $start_date; $i = strtotime('-1 day', $i)) {
							$curr = date('Y-m-d', $i);
							$date_w = date('w', $i);
							
							if (in_array($date_w, $hari_kerja) && key_exists($curr, $riwayat_presensi)) {
								$presensi_masuk = $riwayat_presensi[$curr]['masuk']['presensi_masuk'] ?? null;
								$presensi_pulang = $riwayat_presensi[$curr]['pulang']['presensi_pulang'] ?? null;
								$durasi = $riwayat_presensi[$curr]['durasi'] ?? null;
								$is_valid = $riwayat_presensi[$curr]['is_valid'] ?? 0;
								
								if ($presensi_masuk || $presensi_pulang) {
									$attendance_records[] = [
										'date' => $curr,
										'masuk' => $presensi_masuk,
										'pulang' => $presensi_pulang,
										'durasi' => $durasi,
										'is_valid' => $is_valid
									];
								}
							}
						}
						
						// Display records in table
						if (empty($attendance_records)) {
							echo '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data presensi untuk periode ini</td></tr>';
						} else {
							foreach ($attendance_records as $record) {
								// Format waktu masuk
								$waktu_masuk_display = '-';
								if ($record['masuk']) {
									$waktu_masuk_time = substr($record['masuk'], 0, 5); // Get HH:MM
									$waktu_masuk_display = '<span>' . $waktu_masuk_time . '</span>';
								}
								
								// Format waktu pulang
								$waktu_pulang_display = '-';
								if ($record['pulang']) {
									$waktu_pulang_time = substr($record['pulang'], 0, 5); // Get HH:MM
									$waktu_pulang_display = '<span>' . $waktu_pulang_time . '</span>';
								}
								
								// Determine date display (check if crosses midnight)
								$tanggal_display = date('d/m/Y', strtotime($record['date']));
								if ($record['masuk'] && $record['pulang']) {
									// Check if pulang time is earlier than masuk time (crosses midnight)
									$masuk_timestamp = strtotime($record['date'] . ' ' . $record['masuk']);
									$pulang_timestamp = strtotime($record['date'] . ' ' . $record['pulang']);
									
									// If pulang is before masuk, it means it crossed midnight
									if ($pulang_timestamp < $masuk_timestamp) {
										$pulang_date = date('Y-m-d', strtotime($record['date'] . ' +1 day'));
										$tanggal_display = date('d/m/Y', strtotime($record['date'])) . ' - ' . date('d/m/Y', strtotime($pulang_date));
									}
								}
								
								// Format jam kerja
								$jam_kerja_display = '-';
								if ($record['durasi'] !== null && $record['durasi'] > 0) {
									$durasi_formatted = number_format($record['durasi'], 2);
									// Remove trailing zeros
									$durasi_formatted = rtrim(rtrim($durasi_formatted, '0'), '.');
									$jam_kerja_display = $durasi_formatted . ' jam';
									
									// Add validation badge
									$valid_class = $record['is_valid'] ? 'bg-success' : 'bg-warning';
									$valid_text = $record['is_valid'] ? 'Valid' : 'Tidak Valid';
									$jam_kerja_display .= ' <span class="badge ' . $valid_class . ' ms-1">' . $valid_text . '</span>';
								}
								
								echo '<tr>';
								echo '<td class="text-center fw-semibold">' . $no . '</td>';
								echo '<td class="fw-medium">' . $tanggal_display . '</td>';
								echo '<td class="text-center">' . $waktu_masuk_display . '</td>';
								echo '<td class="text-center">' . $waktu_pulang_display . '</td>';
								echo '<td class="text-center">' . $jam_kerja_display . '</td>';
								echo '</tr>';
								
								$no++;
							}
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	<input type="hidden" id="page-type" value="kasir"/>
	<input type="hidden" id="selected-company-id" value=""/>
	<input type="hidden" id="selected-company-lat" value=""/>
	<input type="hidden" id="selected-company-lng" value=""/>
	<input type="hidden" id="selected-company-radius" value=""/>
	<input type="hidden" id="selected-company-satuan" value=""/>
	
	<?php if ($is_readonly && $active_company_id): ?>
	<!-- Populate company location fields for active shift -->
	<script>
	(function() {
		var activeCompanyId = <?=$active_company_id?>;
		if (typeof assignedCompanies !== 'undefined' && assignedCompanies && Array.isArray(assignedCompanies)) {
			for (var i = 0; i < assignedCompanies.length; i++) {
				var company = assignedCompanies[i];
				if (company.id_company == activeCompanyId) {
					// Set company location fields for active shift
					var latField = document.getElementById('selected-company-lat');
					var lngField = document.getElementById('selected-company-lng');
					var idField = document.getElementById('selected-company-id');
					var radiusField = document.getElementById('selected-company-radius');
					var satuanField = document.getElementById('selected-company-satuan');
					
					if (latField && company.latitude) {
						latField.value = company.latitude;
					}
					if (lngField && company.longitude) {
						lngField.value = company.longitude;
					}
					if (idField) {
						idField.value = company.id_company;
					}
					if (radiusField && company.radius_nilai) {
						radiusField.value = company.radius_nilai;
					}
					if (satuanField && company.radius_satuan) {
						satuanField.value = company.radius_satuan;
					}
					
					console.log('Populated company location fields for active shift:', company.latitude, company.longitude);
					break;
				}
			}
		}
	})();
	</script>
	<?php endif; ?>
</div>
<span id="setting-presensi" style="display:none"><?=json_encode($setting_presensi)?></span>
<span id="companies-data" style="display:none"><?=json_encode($companies ?? [])?></span>
<span id="company-setting-data" style="display:none"><?=json_encode($company_setting ?? [])?></span>

<script>
// Declare companySetting secara global untuk main-mobile.js
var companySetting = <?=json_encode($company_setting ?? [])?>;
</script>

<script>
// Manual Company Selection Functions (attached to window for global access)
window.populateManualCompanyOptions = function() {
	var select = document.getElementById('manual-company-select');
	if (!select) {
		console.error('Manual company select element not found');
		return;
	}
	
	if (typeof assignedCompanies === 'undefined') {
		console.error('assignedCompanies is undefined');
		return;
	}
	
	if (!Array.isArray(assignedCompanies)) {
		console.error('assignedCompanies is not an array:', typeof assignedCompanies);
		return;
	}
	
	if (assignedCompanies.length === 0) {
		console.warn('No companies assigned to user');
		return;
	}
	
	console.log('Populating dropdown with', assignedCompanies.length, 'companies');
	
	// Clear existing options except the first one
	select.innerHTML = '<option value="">-- Pilih Perusahaan --</option>';
	
	// Populate with assigned companies
	for (var i = 0; i < assignedCompanies.length; i++) {
		var company = assignedCompanies[i];
		
		// Handle both object and array formats - access properties directly
		var companyId = null;
		var companyName = null;
		
		// Try to get id_company
		if (company.id_company !== undefined && company.id_company !== null) {
			companyId = company.id_company;
		}
		
		// Try to get nama_company
		if (company.nama_company !== undefined && company.nama_company !== null) {
			companyName = company.nama_company;
		}
		
		if (!companyId) {
			console.warn('Company at index', i, 'has no id_company. Full object:', JSON.stringify(company));
			continue;
		}
		
		var option = document.createElement('option');
		option.value = companyId;
		option.textContent = companyName || 'Perusahaan #' + companyId;
		select.appendChild(option);
		console.log('Added company option:', companyId, '-', companyName);
	}
	
	console.log('Dropdown populated with', select.options.length - 1, 'companies');
};

window.showManualCompanySelector = function() {
	var manualSelect = document.getElementById('manual-company-select');
	if (manualSelect) {
		window.populateManualCompanyOptions();
	}
	
	var manualTabTrigger = document.querySelector('[data-bs-target="#manual-detect-tab"]');
	if (manualTabTrigger && typeof bootstrap !== 'undefined') {
		var tab = bootstrap.Tab.getOrCreateInstance(manualTabTrigger);
		tab.show();
	}
};

window.selectCompanyManually = function(companyId) {
	console.log('selectCompanyManually called with companyId:', companyId);
	
	if (!companyId) {
		console.error('No company ID provided');
		if (typeof Swal !== 'undefined') {
			Swal.fire({
				icon: 'error',
				title: 'Error',
				text: 'ID perusahaan tidak valid.',
				confirmButtonText: 'OK'
			});
		}
		return;
	}
	
	if (typeof assignedCompanies === 'undefined') {
		console.error('assignedCompanies is undefined');
		if (typeof Swal !== 'undefined') {
			Swal.fire({
				icon: 'error',
				title: 'Error',
				text: 'Data perusahaan tidak tersedia. Silakan refresh halaman.',
				confirmButtonText: 'OK'
			});
		}
		return;
	}
	
	// Find the selected company
	var selectedCompany = null;
	for (var i = 0; i < assignedCompanies.length; i++) {
		var company = assignedCompanies[i];
		// Handle both object and array formats
		var companyIdToCompare = company.id_company;
		if (companyIdToCompare == companyId) {
			selectedCompany = company;
			break;
		}
	}
	
	if (!selectedCompany) {
		console.error('Company not found for ID:', companyId);
		if (typeof Swal !== 'undefined') {
			Swal.fire({
				icon: 'error',
				title: 'Error',
				text: 'Perusahaan tidak ditemukan.',
				confirmButtonText: 'OK'
			});
		}
		return;
	}
	
	console.log('Selected company:', selectedCompany);
	
	// Hide not found message
	document.getElementById('company-not-found').style.display = 'none';
	
	// Show company detected with manual badge
	document.getElementById('company-detected').style.display = 'block';
	document.getElementById('detected-company-name').innerHTML = selectedCompany.nama_company + ' <span class="badge bg-warning text-dark">Manual</span>';
	document.getElementById('detected-company-distance').textContent = 'Dipilih secara manual';
	var settingBadge = document.getElementById('detected-company-setting');
	if (settingBadge) {
		if (selectedCompany.nama_setting) {
			settingBadge.textContent = selectedCompany.nama_setting;
			settingBadge.style.display = 'inline-block';
		} else {
			settingBadge.style.display = 'none';
		}
	}
	
	// Set hidden field
	document.getElementById('id_company').value = selectedCompany.id_company;
	
	// Presensi-specific: Set additional hidden fields
	var selectedCompanyIdField = document.getElementById('selected-company-id');
	if (selectedCompanyIdField) {
		selectedCompanyIdField.value = selectedCompany.id_company;
	}
	
	var latField = document.getElementById('selected-company-lat');
	if (latField) {
		latField.value = selectedCompany.latitude || '';
	}
	
	var lngField = document.getElementById('selected-company-lng');
	if (lngField) {
		lngField.value = selectedCompany.longitude || '';
	}
	
	var radiusField = document.getElementById('selected-company-radius');
	if (radiusField) {
		radiusField.value = selectedCompany.radius_nilai || '';
	}
	
	var satuanField = document.getElementById('selected-company-satuan');
	if (satuanField) {
		satuanField.value = selectedCompany.radius_satuan || '';
	}
	
	// Presensi-specific: Enable presensi buttons
	var presensiButtons = document.querySelectorAll('.presensi-container');
	presensiButtons.forEach(function(btn) {
		btn.style.opacity = '1';
		btn.style.pointerEvents = 'auto';
	});
	
	// Attempt to get GPS location when company is manually selected
	// This helps with presensi submission even if GPS wasn't available initially
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(
			function(position) {
				// GPS succeeded - set detected coordinates
				var userLat = position.coords.latitude;
				var userLon = position.coords.longitude;
				
				var detectedLatField = document.getElementById('detected-latitude');
				var detectedLngField = document.getElementById('detected-longitude');
				
				if (detectedLatField) {
					detectedLatField.value = userLat;
				}
				if (detectedLngField) {
					detectedLngField.value = userLon;
				}
				
				console.log('GPS location obtained for manual selection:', userLat, userLon);
			},
			function(error) {
				// GPS failed - this is OK, we'll use company location as fallback
				console.log('GPS not available for manual selection, will use company location as fallback');
				// Don't block the selection - allow it to proceed
			},
			{
				enableHighAccuracy: true,
				timeout: 5000,
				maximumAge: 0
			}
		);
	} else {
		console.log('Geolocation not supported, will use company location as fallback');
	}
	
	// Switch to auto-detect tab to show the result
	var autoTabTrigger = document.querySelector('[data-bs-target="#auto-detect-tab"]');
	if (autoTabTrigger && typeof bootstrap !== 'undefined') {
		var tab = bootstrap.Tab.getOrCreateInstance(autoTabTrigger);
		tab.show();
	}
	
	console.log('Company selection completed successfully');
	
	// Show success message
	if (typeof Swal !== 'undefined') {
		Swal.fire({
			icon: 'success',
			title: 'Perusahaan Dipilih',
			text: 'Perusahaan ' + selectedCompany.nama_company + ' telah dipilih.',
			confirmButtonText: 'OK',
			timer: 2000,
			timerProgressBar: true
		});
	}
};

// Deteksi otomatis perusahaan berbasis GPS (anti-kecurangan)
(function() {
	// Fungsi untuk menghitung jarak antar dua koordinat
	function getDistance(lat1, lon1, lat2, lon2) {
		const R = 6371; // Radius bumi dalam kilometer
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLon = (lon2 - lon1) * Math.PI / 180;
		const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
				  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
				  Math.sin(dLon/2) * Math.sin(dLon/2);
		const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
		const distance = R * c;
		return distance; // dalam kilometer
	}
	
	// Cek apakah radius lokasi diaktifkan
	var gunakanRadiusLokasi = companySetting && companySetting.gunakan_radius_lokasi ? companySetting.gunakan_radius_lokasi : 'Y';
	
	// Deteksi otomatis perusahaan berdasarkan GPS
	if (navigator.geolocation && typeof assignedCompanies !== 'undefined') {
		navigator.geolocation.getCurrentPosition(function(position) {
			var userLat = position.coords.latitude;
			var userLon = position.coords.longitude;
			
			// Simpan lokasi pengguna
			document.getElementById('detected-latitude').value = userLat;
			document.getElementById('detected-longitude').value = userLon;
			
			// Temukan perusahaan terdekat dalam radius
			var nearestCompany = null;
			var minDistance = Infinity;
			
			for (var i = 0; i < assignedCompanies.length; i++) {
				var company = assignedCompanies[i];
				var companyLat = parseFloat(company.latitude);
				var companyLon = parseFloat(company.longitude);
				var radiusNilai = parseFloat(company.radius_nilai);
				var radiusSatuan = company.radius_satuan;
				
				// Gunakan pengaturan radius khusus perusahaan jika tersedia
				if (company.setting_data) {
					radiusNilai = parseFloat(company.setting_data.radius_nilai || company.radius_nilai);
					radiusSatuan = company.setting_data.radius_satuan || company.radius_satuan;
				}
				
				// Konversi radius ke kilometer
				var radiusKm = radiusSatuan === 'm' ? radiusNilai / 1000 : radiusNilai;
				
				// Hitung jarak
				var distance = getDistance(userLat, userLon, companyLat, companyLon);
				
				// Cek dalam radius (hanya jika pemeriksaan radius diaktifkan)
				if (gunakanRadiusLokasi === 'N') {
					// Pemeriksaan radius dimatikan - cari perusahaan terdekat saja
					if (distance < minDistance) {
						minDistance = distance;
						nearestCompany = company;
					}
				} else {
					// Pemeriksaan radius diaktifkan - harus dalam radius
					if (distance <= radiusKm && distance < minDistance) {
						minDistance = distance;
						nearestCompany = company;
					}
				}
			}
			
			// Sembunyikan spinner mendeteksi
			document.getElementById('company-detecting').style.display = 'none';
			
			if (nearestCompany) {
				// Perusahaan terdeteksi!
				document.getElementById('company-detected').style.display = 'block';
				document.getElementById('detected-company-name').textContent = nearestCompany.nama_company;
				
				// Display nama_setting if available
				var settingBadge = document.getElementById('detected-company-setting');
				if (nearestCompany.nama_setting) {
					settingBadge.textContent = nearestCompany.nama_setting;
					settingBadge.style.display = 'inline-block';
				} else {
					settingBadge.style.display = 'none';
				}
				
				var distanceText = minDistance < 1 
					? Math.round(minDistance * 1000) + ' meter dari lokasi perusahaan'
					: minDistance.toFixed(2) + ' km dari lokasi perusahaan';
				
				if (gunakanRadiusLokasi === 'N') {
					document.getElementById('detected-company-distance').textContent = 'Anda berada ' + distanceText + ' (Validasi radius dinonaktifkan)';
				} else {
					document.getElementById('detected-company-distance').textContent = 'Anda berada ' + distanceText;
				}
				
				// Set field tersembunyi
				document.getElementById('id_company').value = nearestCompany.id_company;
				document.getElementById('selected-company-id').value = nearestCompany.id_company;
				document.getElementById('selected-company-lat').value = nearestCompany.latitude;
				document.getElementById('selected-company-lng').value = nearestCompany.longitude;
				document.getElementById('selected-company-radius').value = nearestCompany.radius_nilai;
				document.getElementById('selected-company-satuan').value = nearestCompany.radius_satuan;
			} else {
				// Tidak ada perusahaan ditemukan dalam radius
				document.getElementById('company-not-found').style.display = 'block';
				
				// Show manual selector option
				if (window.showManualCompanySelector) {
					window.showManualCompanySelector();
				}
				
				// Nonaktifkan tombol presensi
				var presensiButtons = document.querySelectorAll('.presensi-container');
				presensiButtons.forEach(function(btn) {
					btn.style.opacity = '0.5';
					btn.style.pointerEvents = 'none';
				});
			}
		}, function(error) {
			// Error GPS
			document.getElementById('company-detecting').style.display = 'none';
			document.getElementById('company-not-found').style.display = 'block';
			document.getElementById('company-not-found').querySelector('.alert').innerHTML = 
				'<i class="fas fa-exclamation-triangle me-2"></i>' +
				'<strong>Gagal mendapatkan lokasi GPS!</strong><br>' +
				'<small>Pastikan GPS/Lokasi diaktifkan di browser Anda atau pilih perusahaan secara manual.</small>';
			// Show manual selector option
			if (window.showManualCompanySelector) {
				window.showManualCompanySelector();
			}
		}, {
			enableHighAccuracy: true,
			timeout: 10000,
			maximumAge: 0
		});
	}
})();

// Wait for jQuery to be available
(function checkJQuery() {
	if (typeof jQuery === 'undefined') {
		setTimeout(checkJQuery, 50);
		return;
	}

	// jQuery is loaded, now run our code
	
	// Manual Company Selection Event Handlers
	jQuery(document).ready(function() {
		// Populate manual company options on page load
		if (window.populateManualCompanyOptions) {
			window.populateManualCompanyOptions();
		}
		
		// Tab change event listener - populate dropdown when manual tab is shown
		jQuery('#company-detection-tabs button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
			var targetTab = jQuery(e.target).data('bs-target');
			console.log('Tab shown:', targetTab);
			if (targetTab === '#manual-detect-tab') {
				// Manual tab is now active, hide loading spinner and populate the dropdown
				jQuery('#company-detecting').hide();
				console.log('Manual tab shown, populating dropdown...');
				if (window.populateManualCompanyOptions) {
					window.populateManualCompanyOptions();
				} else {
					console.error('populateManualCompanyOptions function not found');
				}
			}
		});
		
		// Also handle direct click on manual tab button (fallback)
		jQuery('button[data-bs-target="#manual-detect-tab"]').on('click', function() {
			console.log('Manual tab button clicked');
			// Hide loading spinner immediately when user clicks manual tab
			jQuery('#company-detecting').hide();
			// Small delay to ensure tab is shown before populating
			setTimeout(function() {
				console.log('Populating dropdown after tab click...');
				if (window.populateManualCompanyOptions) {
					window.populateManualCompanyOptions();
				} else {
					console.error('populateManualCompanyOptions function not found');
				}
			}, 100);
		});
		
		// Ensure tabs are always clickable - prevent any overlay from blocking
		jQuery('#company-detection-tabs button').css({
			'pointer-events': 'auto',
			'z-index': '1000',
			'position': 'relative'
		});
		
		// Also populate on page load if manual tab is already visible (for debugging)
		setTimeout(function() {
			var manualTab = jQuery('#manual-detect-tab');
			if (manualTab.hasClass('active') || manualTab.hasClass('show')) {
				console.log('Manual tab is active on page load, populating...');
				if (window.populateManualCompanyOptions) {
					window.populateManualCompanyOptions();
				}
			}
		}, 500);
		
		// Manual company selection dropdown change handler
		jQuery('#manual-company-select').on('change', function () {
			var selectedValue = jQuery(this).val();
			var confirmBtn = jQuery('#btn-confirm-manual-company');
			console.log('Dropdown changed, selected value:', selectedValue);
			if (selectedValue && selectedValue !== '') {
				confirmBtn.prop('disabled', false);
				console.log('Button enabled');
			} else {
				confirmBtn.prop('disabled', true);
				console.log('Button disabled');
			}
		});
		
		// Fallback: If dropdown has no options but companies exist, try to populate on focus
		jQuery('#manual-company-select').on('focus', function() {
			var select = jQuery(this);
			if (select.find('option').length <= 1 && typeof window.populateManualCompanyOptions === 'function') {
				console.log('Dropdown focused but empty, attempting to populate...');
				window.populateManualCompanyOptions();
			}
		});
		
		// Manual company confirmation button click handler
		jQuery('#btn-confirm-manual-company').on('click', function () {
			var selectedCompanyId = jQuery('#manual-company-select').val();
			console.log('Button clicked, selected company ID:', selectedCompanyId);
			
			if (!selectedCompanyId || selectedCompanyId === '') {
				// Show error message if no company selected
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'warning',
						title: 'Pilih Perusahaan',
						text: 'Silakan pilih perusahaan terlebih dahulu dari dropdown.',
						confirmButtonText: 'OK'
					});
				} else {
					alert('Silakan pilih perusahaan terlebih dahulu dari dropdown.');
				}
				return;
			}
			
			if (window.selectCompanyManually) {
				window.selectCompanyManually(selectedCompanyId);
			} else {
				console.error('selectCompanyManually function not found');
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'Fungsi pemilihan perusahaan tidak tersedia. Silakan refresh halaman.',
						confirmButtonText: 'OK'
					});
				}
			}
		});
	});
	
	// Nonaktifkan tombol presensi jika tidak ada perusahaan terdeteksi atau bukan hari kerja
	jQuery(document).ready(function() {
		jQuery('.presensi-container').on('click', function(e) {
			// Periksa apakah hari ini hari kerja
			var today = new Date().getDay(); // 0 = Minggu, 1 = Senin, dst.
			var hariKerja = companySetting && companySetting.hari_kerja ? companySetting.hari_kerja : [1,2,3,4,5];
			
			// Pastikan perbandingan integer (handle string dan int)
			var isWorkingDay = hariKerja.some(function(day) {
				return parseInt(day) === parseInt(today);
			});
			
			if (!isWorkingDay) {
				e.preventDefault();
				Swal.fire({
					icon: 'info',
					title: 'Hari Libur',
					text: 'Anda tidak bisa absen di hari libur. Presensi hanya dapat dilakukan pada hari kerja.',
					confirmButtonText: 'OK'
				});
				return false;
			}
			
			var companyId = jQuery('#id_company').val();
			if (!companyId) {
				e.preventDefault();
				Swal.fire({
					icon: 'error',
					title: 'Tidak Dapat Absen!',
					text: 'Anda tidak berada di lokasi perusahaan yang ditugaskan.',
					confirmButtonText: 'OK'
				});
				return false;
			}
		});
	});
})();
</script>
<?= $this->endSection() ?>