<?= $this->extend('themes/modern/layout-mobile') ?>
<?= $this->section('content') ?>
<?php
$nama_bulan = nama_bulan();
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
?>
<div class="container py-4">
	<!-- Header: User info -->
	<div class="text-center text-light mb-3">
		<h5 class="fw-semibold mb-1"><?= $user['nama'] ?></h5>
		<p class="mb-0 small opacity-75"><?= $data_setelah_nama_user ?></p>
	</div>

	<!-- Current date & time -->
	<div class="bg-light text-dark p-3 rounded-3 shadow-sm mb-4">
		<div class="d-flex justify-content-between align-items-center">
			<div class="hari-tanggal fw-semibold">
				<?= $nama_hari[date('w')] . ', ' . date('d') . ' ' . $nama_bulan[date('n')] . ' ' . date('Y') ?>
			</div>
			<div class="text-end">
				<div class="small text-muted">Waktu sekarang</div>
				<div class="fw-semibold fs-5" id="live-jam"><?= date('H:i:s') ?></div>
			</div>
		</div>
	</div>

	<?php
	$companies_count = is_array($companies) ? count($companies) : (is_object($companies) ? count((array) $companies) : 0);
	log_message('debug', 'mobile-presensi-home: companies variable type=' . gettype($companies) . ', count=' . $companies_count);
	if (isset($companies) && !empty($companies)) {
		log_message('debug', 'mobile-presensi-home: First company id=' . (isset($companies[0]) ? ($companies[0]->id_company ?? 'NO ID') : 'NO FIRST'));
	}
	// Initialize $last from $last_presensi at the top level
	$last = $last_presensi ?? null;
	// Initialize variables that are used outside the if/else block
	$is_readonly = false;
	$active_company_id = null;
	$active_company_name = '';
	?>
	<?php if (empty($companies)): ?>
		<div class="alert alert-warning shadow-sm border-0 rounded-3">
			<div class="d-flex">
				<div class="me-3 d-flex align-items-start">
					<i class="fas fa-exclamation-triangle fs-5 mt-1"></i>
				</div>
				<div>
					<div class="fw-semibold mb-1">Belum ada penugasan perusahaan</div>
					<div class="small mb-0">
						Anda belum di-assign ke perusahaan manapun. Silakan hubungi admin untuk melakukan penugasan.
					</div>
					<?php if (isset($debug_info) && !empty($debug_info)): ?>
						<div class="mt-2">
							<small class="text-muted">
								Debug: Total penugasan ditemukan: <?= $debug_info['total_assignments'] ?>,
								Perusahaan aktif: <?= $debug_info['active_companies'] ?>
							</small>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php else: ?>
		<?php
		// $last, $is_readonly, $active_company_id, and $active_company_name are already initialized at top level
		if ($last && is_object($last)) {
			$last = (array) $last;
		}
		if ($last && empty($last['tgl_keluar'])) {
			$active_company_id = $last['id_company'] ?? null;
			$is_readonly = true;
			foreach ($companies as $comp) {
				if ($comp->id_company == $active_company_id) {
					$active_company_name = $comp->nama_company;
					break;
				}
			}
		}
		?>
		<!-- Company selection -->
		<div class="bg-light p-3 mb-4 rounded-3 shadow-sm">
			<div class="d-flex justify-content-between align-items-center mb-2">
				<label class="form-label mb-0 fw-semibold">Lokasi Perusahaan</label>
				<small class="text-muted">Pilih perusahaan tempat Anda bertugas</small>
			</div>
			<?php if ($is_readonly): ?>
				<input type="text" class="form-control" value="<?= $active_company_name ?>" readonly>
				<input type="hidden" id="id_company" name="id_company" value="<?= $active_company_id ?>">
				<small class="text-success d-block mt-2">
					<i class="fas fa-lock me-1"></i>
					Perusahaan sudah terpilih untuk shift aktif dan tidak dapat diubah setelah absen masuk.
				</small>
			<?php else: ?>
				<!-- Company Detection with Tabs -->
				<div class="detection-wrapper">
					<ul class="nav nav-pills nav-fill mb-3" id="company-detection-tabs" role="tablist"
						style="position: relative; z-index: 10;">
						<li class="nav-item" role="presentation">
							<button class="nav-link active fw-semibold" data-bs-toggle="pill" type="button"
								data-bs-target="#auto-detect-tab">
								<i class="fas fa-location-crosshairs me-2"></i>Auto GPS
							</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link fw-semibold" data-bs-toggle="pill" type="button"
								data-bs-target="#manual-detect-tab">
								<i class="fas fa-list-ul me-2"></i>Pilih Manual
							</button>
						</li>
					</ul>
					<div class="tab-content border rounded-3 p-3 bg-white">
						<div class="tab-pane fade show active" id="auto-detect-tab">
							<!-- Auto-detect company based on GPS location -->
							<div id="company-detecting" class="text-center py-4">
								<div class="spinner-border text-primary" role="status">
									<span class="visually-hidden">Memuat...</span>
								</div>
								<p class="mt-3 mb-0 small text-muted">Mendeteksi lokasi Anda menggunakan GPS...</p>
							</div>
							<div id="company-detected" style="display:none;">
								<div class="alert alert-success mb-0 rounded-3">
									<i class="fas fa-map-marker-alt me-2"></i>
									<strong id="detected-company-name"></strong>
									<span id="detected-company-setting" class="badge bg-info ms-2" style="display:none;"></span>
									<br>
									<small id="detected-company-distance" class="text-muted"></small>
								</div>
							</div>
							<div id="company-not-found" style="display:none;">
								<div class="alert alert-danger mb-0 rounded-3">
									<i class="fas fa-exclamation-triangle me-2"></i>
									<strong>Anda tidak berada di lokasi perusahaan manapun!</strong>
									<br>
									<small>Silakan pergi ke lokasi perusahaan yang sudah ditugaskan atau gunakan tab
										<span class="fw-semibold">Pilih Manual</span>.</small>
								</div>
							</div>
						</div>
						<div class="tab-pane fade" id="manual-detect-tab">
							<div class="alert alert-warning rounded-3">
								<div class="d-flex">
									<div class="me-3 d-flex align-items-start">
										<i class="fas fa-info-circle mt-1"></i>
									</div>
									<div class="small">
										<div class="fw-semibold mb-1">GPS sulit mendeteksi lokasi?</div>
										<div>Pilih perusahaan secara manual dari daftar di bawah.</div>
									</div>
								</div>
							</div>
							<div class="mb-3">
								<label class="form-label fw-semibold">Pilih Perusahaan</label>
								<select class="form-select" id="manual-company-select">
									<!-- Option elements will be filled in JS. This fixes load/target issues. -->
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
			var assignedCompanies = <?= json_encode($companies ?? []) ?>;
		</script>
	<?php endif; ?>

	<?php
	$company_setting = null;
	if (!empty($companies)) {
		$company_setting = $companies[0]->setting_data ?? null;
	}
	if (!$company_setting) {
		$company_setting = [
			'hari_kerja' => json_decode($setting_presensi['hari_kerja'], true) ?: [1, 2, 3, 4, 5],
			'gunakan_foto_selfi' => $setting_presensi['gunakan_foto_selfi'] ?? 'Y',
			'gunakan_radius_lokasi' => $setting_presensi['gunakan_radius_lokasi'] ?? 'Y',
			'latitude' => $setting_presensi['latitude'] ?? '-7.797068',
			'longitude' => $setting_presensi['longitude'] ?? '110.370529',
			'radius_nilai' => $setting_presensi['radius_nilai'] ?? '1.00',
			'radius_satuan' => $setting_presensi['radius_satuan'] ?? 'km'
		];
	}
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
	$waktu_masuk = $waktu_pulang = 'Belum absen';
	$tanggal_masuk = $tanggal_pulang = '';
	$last_array = $last;
	if ($last && is_object($last)) {
		$last_array = (array) $last;
	}
	if ($last_array) {
		if (empty($last_array['tgl_keluar'])) {
			if (!empty($last_array['tgl_masuk'])) {
				$waktu_masuk = date('H:i', strtotime($last_array['tgl_masuk']));
				$tanggal_masuk = date('d/m/Y', strtotime($last_array['tgl_masuk']));
			}
		}
	}
	$today_day_of_week = date('w');
	$is_today_working_day = in_array($today_day_of_week, $company_setting['hari_kerja']);
	?>

	<?php if (!$is_today_working_day): ?>
		<div class="alert alert-info text-center shadow-sm border-0 rounded-3">
			<i class="fas fa-calendar-times me-2"></i>
			<strong>Hari ini bukan hari kerja</strong><br>
			<small>Presensi hanya dapat dilakukan pada hari kerja yang telah ditentukan.</small>
		</div>
	<?php else: ?>
		<div id="presensi-buttons-container">
			<div class="bg-light rounded-3 shadow-sm p-3 mb-3">
				<div class="row g-2">
					<div class="col-6">
						<a id="presensi-masuk" href="#"
							class="presensi-container box-absen-masuk d-flex rounded-3 px-3 py-3 w-100">
							<div class="d-flex align-items-center w-100">
								<i class="bi bi-box-arrow-in-right me-3 text-success icon-box-presensi"
									style="font-size:30px"></i>
								<div class="w-100">
									<div class="d-flex justify-content-between align-items-center">
										<h6 class="m-0 fw-semibold">Masuk</h6>
									</div>
									<p class="mt-1 mb-0 waktu-presensi fs-5 fw-semibold"><?= $waktu_masuk ?></p>
									<?php if ($tanggal_masuk): ?>
										<p class="mt-1 mb-0 text-muted small"><?= $tanggal_masuk ?></p>
									<?php endif; ?>
									<?php
									$jam_kerja_target = 12;
									if (!empty($companies) && !empty($companies[0]->jam_kerja_target)) {
										$jam_kerja_target = intval($companies[0]->jam_kerja_target);
									}
									?>
								</div>
							</div>
						</a>
					</div>
					<div class="col-6">
						<a id="presensi-pulang" href="#"
							class="bg-light presensi-container box-absen-pulang rounded-3 px-3 py-3 d-block"
							style="background:#fff6e8 !important;">
							<div class="d-flex align-items-center">
								<i class="bi bi-box-arrow-right me-3 text-warning icon-box-presensi"
									style="font-size:27px"></i>
								<div class="w-100">
									<div class="d-flex justify-content-between align-items-center">
										<h6 class="m-0 fw-semibold">Pulang</h6>
									</div>
									<p class="mt-1 mb-0 waktu-presensi fs-5 fw-semibold"><?= $waktu_pulang ?></p>
									<?php if ($tanggal_pulang): ?>
										<p class="mt-1 mb-0 text-muted small"><?= $tanggal_pulang ?></p>
									<?php endif; ?>
									<?php
									$jam_kerja_target = 12;
									if (!empty($companies) && !empty($companies[0]->jam_kerja_target)) {
										$jam_kerja_target = intval($companies[0]->jam_kerja_target);
									}
									
									// Check patrol status for active shift
									if (isset($active_shift_patrol) && $active_shift_patrol && $active_shift_patrol['is_required']):
										$patrolProgress = $active_shift_patrol['progress'] ?? ['percentage' => 0, 'completed' => 0, 'total' => 0];
										$percentage = $patrolProgress['percentage'] ?? 0;
										$isComplete = $active_shift_patrol['is_complete'] ?? false;
										$nextPatrol = $active_shift_patrol['next_patrol'] ?? null;
									?>
										<div class="mt-2">
											<?php if (!$isComplete): ?>
												<p class="mb-1 text-danger small fw-semibold">
													<i class="fas fa-exclamation-circle me-1"></i>Patroli belum lengkap
												</p>
											<?php endif; ?>
											<div class="d-flex align-items-center mb-1">
												<div class="progress flex-grow-1 me-2" style="height: 8px;">
													<div class="progress-bar <?= $isComplete ? 'bg-success' : 'bg-warning' ?>" 
														role="progressbar" 
														style="width: <?= $percentage ?>%"
														aria-valuenow="<?= $percentage ?>" 
														aria-valuemin="0" 
														aria-valuemax="100">
													</div>
												</div>
												<small class="text-muted fw-semibold"><?= $percentage ?>%</small>
											</div>
											<?php if ($nextPatrol): ?>
												<p class="mb-0 text-muted small">
													<strong>Patroli berikutnya:</strong><br>
													<?= htmlspecialchars($nextPatrol['nama_patrol'] ?? 'Unknown') ?>
													<?php if (isset($nextPatrol['urutan'])): ?>
														<br><small>Urutan: <?= $nextPatrol['urutan'] ?></small>
													<?php endif; ?>
												</p>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</a>
					</div>
				</div>
			</div>
			<div id="alert-lokasi"></div>
		</div>
	<?php endif; ?>

	<!-- Riwayat presensi -->
	<div id="presensi-history-container">
		<div class="d-flex align-items-center justify-content-between mt-4 mb-2">
			<p class="text-light mb-0 fw-semibold">
				Riwayat Presensi
			</p>
		</div>
		<div class="bg-light p-3 rounded-3 shadow-sm">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered table-sm mb-0 align-middle">
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
						$hari_kerja = $company_setting['hari_kerja'] ?? [1, 2, 3, 4, 5];
						$no = 1;
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
						if (empty($attendance_records)) {
							echo '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data presensi untuk periode ini</td></tr>';
						} else {
							foreach ($attendance_records as $record) {
								$waktu_masuk_display = '-';
								if ($record['masuk']) {
									$waktu_masuk_time = substr($record['masuk'], 0, 5);
									$waktu_masuk_display = '<span>' . $waktu_masuk_time . '</span>';
								}
								$waktu_pulang_display = '-';
								if ($record['pulang']) {
									$waktu_pulang_time = substr($record['pulang'], 0, 5);
									$waktu_pulang_display = '<span>' . $waktu_pulang_time . '</span>';
								}
								$tanggal_display = date('d/m/Y', strtotime($record['date']));
								if ($record['masuk'] && $record['pulang']) {
									$masuk_timestamp = strtotime($record['date'] . ' ' . $record['masuk']);
									$pulang_timestamp = strtotime($record['date'] . ' ' . $record['pulang']);
									if ($pulang_timestamp < $masuk_timestamp) {
										$pulang_date = date('Y-m-d', strtotime($record['date'] . ' +1 day'));
										$tanggal_display = date('d/m/Y', strtotime($record['date'])) . ' - ' . date('d/m/Y', strtotime($pulang_date));
									}
								}
								$jam_kerja_display = '-';
								if ($record['durasi'] !== null && $record['durasi'] > 0) {
									$durasi_formatted = number_format($record['durasi'], 2);
									$durasi_formatted = rtrim(rtrim($durasi_formatted, '0'), '.');
									$jam_kerja_display = $durasi_formatted . ' jam';
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
	</div>
	<input type="hidden" id="page-type" value="kasir" />
	<input type="hidden" id="selected-company-id" value="" />
	<input type="hidden" id="selected-company-lat" value="" />
	<input type="hidden" id="selected-company-lng" value="" />
	<input type="hidden" id="selected-company-radius" value="" />
	<input type="hidden" id="selected-company-satuan" value="" />

	<?php if ($is_readonly && $active_company_id): ?>
		<!-- Populate company location fields for active shift -->
		<script>
			(function () {
				var activeCompanyId = <?= $active_company_id ?>;
				if (typeof assignedCompanies !== 'undefined' && assignedCompanies && Array.isArray(assignedCompanies)) {
					for (var i = 0; i < assignedCompanies.length; i++) {
						var company = assignedCompanies[i];
						if (company.id_company == activeCompanyId) {
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
							break;
						}
					}
				}
			})();
		</script>
	<?php endif; ?>
</div>
<span id="setting-presensi" style="display:none"><?= json_encode($setting_presensi) ?></span>
<span id="companies-data" style="display:none"><?= json_encode($companies ?? []) ?></span>
<span id="company-setting-data" style="display:none"><?= json_encode($company_setting ?? []) ?></span>

<script>
	var companySetting = <?= json_encode($company_setting ?? []) ?>;
</script>
<script>
window.populateManualCompanyOptions = function () {
	var select = document.getElementById('manual-company-select');
	if (!select) {
		// If not found, try again after DOM ready, or give a less noisy log.
		if (document.readyState === "loading") {
			// Will try again on DOMContentLoaded
			document.addEventListener('DOMContentLoaded', window.populateManualCompanyOptions, { once: true });
		} else {
			console.warn('Manual company select element not found; skipping manual population.');
		}
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

	// Only clear and populate if not already populated
	if (select.options.length === 0) {
		var defaultOpt = document.createElement('option');
		defaultOpt.value = '';
		defaultOpt.textContent = '-- Pilih Perusahaan --';
		select.appendChild(defaultOpt);
	}

	for (var i = 0; i < assignedCompanies.length; i++) {
		var company = assignedCompanies[i];
		var companyId = null, companyName = null;
		if (company.id_company !== undefined && company.id_company !== null) {
			companyId = company.id_company;
		}
		if (company.nama_company !== undefined && company.nama_company !== null) {
			companyName = company.nama_company;
		}
		if (!companyId) continue;
		var alreadyAdded = false;
		for (var j = 0; j < select.options.length; j++) {
			if (select.options[j].value == companyId) {
				alreadyAdded = true;
				break;
			}
		}
		if (!alreadyAdded) {
			var opt = document.createElement('option');
			opt.value = companyId;
			opt.textContent = companyName || "Perusahaan #" + companyId;
			select.appendChild(opt);
		}
	}
};

window.showManualCompanySelector = function () {
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

window.selectCompanyManually = function (companyId) {
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
	var selectedCompany = null;
	for (var i = 0; i < assignedCompanies.length; i++) {
		var company = assignedCompanies[i];
		if (company.id_company == companyId) {
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
	// Defensive checks before assigning (fixes "Cannot set properties of null"!)
	var eNotFound, eDetected, eName, eDistance;
	eNotFound = document.getElementById('company-not-found');
	if (eNotFound) eNotFound.style.display = 'none';
	eDetected = document.getElementById('company-detected');
	if (eDetected) eDetected.style.display = 'block';
	eName = document.getElementById('detected-company-name');
	if (eName) eName.innerHTML = selectedCompany.nama_company + ' <span class="badge bg-warning text-dark">Manual</span>';
	eDistance = document.getElementById('detected-company-distance');
	if (eDistance) eDistance.textContent = 'Dipilih secara manual';
	var settingBadge = document.getElementById('detected-company-setting');
	if (settingBadge) {
		if (selectedCompany.nama_setting) {
			settingBadge.textContent = selectedCompany.nama_setting;
			settingBadge.style.display = 'inline-block';
		} else {
			settingBadge.style.display = 'none';
		}
	}
	var el = document.getElementById('id_company');
	if (el) el.value = selectedCompany.id_company;
	var selectedCompanyIdField = document.getElementById('selected-company-id');
	if (selectedCompanyIdField) selectedCompanyIdField.value = selectedCompany.id_company;
	var latField = document.getElementById('selected-company-lat');
	if (latField) latField.value = selectedCompany.latitude || '';
	var lngField = document.getElementById('selected-company-lng');
	if (lngField) lngField.value = selectedCompany.longitude || '';
	var radiusField = document.getElementById('selected-company-radius');
	if (radiusField) radiusField.value = selectedCompany.radius_nilai || '';
	var satuanField = document.getElementById('selected-company-satuan');
	if (satuanField) satuanField.value = selectedCompany.radius_satuan || '';
	var presensiButtons = document.querySelectorAll('.presensi-container');
	presensiButtons.forEach(function (btn) {
		btn.style.opacity = '1';
		btn.style.pointerEvents = 'auto';
	});
	// Defensive: Set GPS only if fields are present
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(
			function (position) {
				var detectedLatField = document.getElementById('detected-latitude');
				var detectedLngField = document.getElementById('detected-longitude');
				if (detectedLatField) {
					detectedLatField.value = position.coords.latitude;
				}
				if (detectedLngField) {
					detectedLngField.value = position.coords.longitude;
				}
			},
			function (error) {
			},
			{
				enableHighAccuracy: true,
				timeout: 5000,
				maximumAge: 0
			}
		);
	}
	var autoTabTrigger = document.querySelector('[data-bs-target="#auto-detect-tab"]');
	if (autoTabTrigger && typeof bootstrap !== 'undefined') {
		var tab = bootstrap.Tab.getOrCreateInstance(autoTabTrigger);
		tab.show();
	}
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
(function () {
	function getDistance(lat1, lon1, lat2, lon2) {
		const R = 6371;
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLon = (lon2 - lon1) * Math.PI / 180;
		const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
			Math.sin(dLon / 2) * Math.sin(dLon / 2);
		const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		return R * c;
	}
	var gunakanRadiusLokasi = companySetting && companySetting.gunakan_radius_lokasi ? companySetting.gunakan_radius_lokasi : 'Y';
	if (navigator.geolocation && typeof assignedCompanies !== 'undefined') {
		navigator.geolocation.getCurrentPosition(function (position) {
			var userLat = position.coords.latitude;
			var userLon = position.coords.longitude;
			// Defensive check, only set if fields exist!
			var eLat = document.getElementById('detected-latitude');
			var eLng = document.getElementById('detected-longitude');
			if (eLat) eLat.value = userLat;
			if (eLng) eLng.value = userLon;

			var nearestCompany = null;
			var minDistance = Infinity;
			for (var i = 0; i < assignedCompanies.length; i++) {
				var company = assignedCompanies[i];
				var companyLat = parseFloat(company.latitude);
				var companyLon = parseFloat(company.longitude);
				var radiusNilai = parseFloat(company.radius_nilai);
				var radiusSatuan = company.radius_satuan;
				if (company.setting_data) {
					radiusNilai = parseFloat(company.setting_data.radius_nilai || company.radius_nilai);
					radiusSatuan = company.setting_data.radius_satuan || company.radius_satuan;
				}
				var radiusKm = radiusSatuan === 'm' ? radiusNilai / 1000 : radiusNilai;
				var distance = getDistance(userLat, userLon, companyLat, companyLon);
				if (gunakanRadiusLokasi === 'N') {
					if (distance < minDistance) {
						minDistance = distance;
						nearestCompany = company;
					}
				} else {
					if (distance <= radiusKm && distance < minDistance) {
						minDistance = distance;
						nearestCompany = company;
					}
				}
			}
			var eDetecting = document.getElementById('company-detecting');
			if (eDetecting) eDetecting.style.display = 'none';

			if (nearestCompany) {
				// Defensive value checks everywhere:
				var eDetected = document.getElementById('company-detected');
				if (eDetected) eDetected.style.display = 'block';
				var eName = document.getElementById('detected-company-name');
				if (eName) eName.textContent = nearestCompany.nama_company;
				var settingBadge = document.getElementById('detected-company-setting');
				if (settingBadge) {
					if (nearestCompany.nama_setting) {
						settingBadge.textContent = nearestCompany.nama_setting;
						settingBadge.style.display = 'inline-block';
					} else {
						settingBadge.style.display = 'none';
					}
				}
				var distanceText = minDistance < 1
					? Math.round(minDistance * 1000) + ' meter dari lokasi perusahaan'
					: minDistance.toFixed(2) + ' km dari lokasi perusahaan';
				var eDist = document.getElementById('detected-company-distance');
				if (eDist) {
					if (gunakanRadiusLokasi === 'N') {
						eDist.textContent = 'Anda berada ' + distanceText + ' (Validasi radius dinonaktifkan)';
					} else {
						eDist.textContent = 'Anda berada ' + distanceText;
					}
				}
				var eid = document.getElementById('id_company');
				var eid2 = document.getElementById('selected-company-id');
				var elat = document.getElementById('selected-company-lat');
				var elng = document.getElementById('selected-company-lng');
				var erad = document.getElementById('selected-company-radius');
				var esatuan = document.getElementById('selected-company-satuan');
				if (eid) eid.value = nearestCompany.id_company;
				if (eid2) eid2.value = nearestCompany.id_company;
				if (elat) elat.value = nearestCompany.latitude;
				if (elng) elng.value = nearestCompany.longitude;
				if (erad) erad.value = nearestCompany.radius_nilai;
				if (esatuan) esatuan.value = nearestCompany.radius_satuan;
			} else {
				var eNotFound = document.getElementById('company-not-found');
				if (eNotFound) eNotFound.style.display = 'block';
				if (window.showManualCompanySelector) {
					window.showManualCompanySelector();
				}
				var presensiButtons = document.querySelectorAll('.presensi-container');
				presensiButtons.forEach(function (btn) {
					btn.style.opacity = '0.5';
					btn.style.pointerEvents = 'none';
				});
			}
		}, function (error) {
			var eDetecting = document.getElementById('company-detecting');
			var eNotFound = document.getElementById('company-not-found');
			if (eDetecting) eDetecting.style.display = 'none';
			if (eNotFound) {
				eNotFound.style.display = 'block';
				var eAlert = eNotFound.querySelector('.alert');
				if (eAlert) {
					eAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' +
						'<strong>Gagal mendapatkan lokasi GPS!</strong><br>' +
						'<small>Pastikan GPS/Lokasi diaktifkan di browser Anda atau pilih perusahaan secara manual.</small>';
				}
			}
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
	jQuery(document).ready(function () {
		// On page load, forcibly re-populate manual-company-select (robust)
		window.populateManualCompanyOptions();

		// Tab change event listener
		jQuery('#company-detection-tabs button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
			var targetTab = jQuery(e.target).data('bs-target');
			if (targetTab === '#manual-detect-tab') {
				jQuery('#company-detecting').hide();
				window.populateManualCompanyOptions();
			}
		});
		jQuery('button[data-bs-target="#manual-detect-tab"]').on('click', function () {
			jQuery('#company-detecting').hide();
			setTimeout(function () {
				window.populateManualCompanyOptions();
			}, 100);
		});
		jQuery('#company-detection-tabs button').css({
			'pointer-events': 'auto',
			'z-index': '1000',
			'position': 'relative'
		});
		setTimeout(function () {
			var manualTab = jQuery('#manual-detect-tab');
			if (manualTab.hasClass('active') || manualTab.hasClass('show')) {
				window.populateManualCompanyOptions();
			}
		}, 500);
		// Dropdown change enables the button
		jQuery('#manual-company-select').on('change', function () {
			var selectedValue = jQuery(this).val();
			var confirmBtn = jQuery('#btn-confirm-manual-company');
			confirmBtn.prop('disabled', !selectedValue);
		});
		jQuery('#manual-company-select').on('focus', function () {
			var select = jQuery(this);
			if (select.find('option').length <= 1) {
				window.populateManualCompanyOptions();
			}
		});
		jQuery('#btn-confirm-manual-company').on('click', function () {
			var selectedCompanyId = jQuery('#manual-company-select').val();
			if (!selectedCompanyId || selectedCompanyId === '') {
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
			window.selectCompanyManually(selectedCompanyId);
		});
	});
	jQuery(document).ready(function () {
		jQuery('.presensi-container').on('click', function (e) {
			var today = new Date().getDay();
			var hariKerja = companySetting && companySetting.hari_kerja ? companySetting.hari_kerja : [1, 2, 3, 4, 5];
			var isWorkingDay = hariKerja.some(function (day) {
				return parseInt(day) === parseInt(today);
			});
			if (!isWorkingDay) {
				e.preventDefault();
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'info',
						title: 'Hari Libur',
						text: 'Anda tidak bisa absen di hari libur. Presensi hanya dapat dilakukan pada hari kerja.',
						confirmButtonText: 'OK'
					});
				}
				return false;
			}
			var companyId = jQuery('#id_company').val();
			if (!companyId) {
				e.preventDefault();
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'error',
						title: 'Tidak Dapat Absen!',
						text: 'Anda tidak berada di lokasi perusahaan yang ditugaskan.',
						confirmButtonText: 'OK'
					});
				}
				return false;
			}
		});
	});
})();
</script>
<?= $this->endSection() ?>