<?= $this->extend('themes/modern/layout-mobile') ?>
<?= $this->section('content') ?>
<?php
$nama_bulan = nama_bulan();
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
?>
<style>
	.activity-stepper {
		display: flex;
		gap: .75rem;
		flex-wrap: wrap;
	}

	.activity-stepper .step-pill {
		flex: 1;
		min-width: 140px;
		background: rgba(255, 255, 255, .1);
		border-radius: 12px;
		padding: .7rem .9rem;
		display: flex;
		align-items: center;
		gap: .6rem;
		color: #cfd8dc;
		border: 1px solid rgba(255, 255, 255, .15);
	}

	.activity-stepper .step-pill.active {
		background: #0d6efd;
		border-color: #0d6efd;
		color: #fff;
		box-shadow: 0 4px 14px rgba(13, 110, 253, .35);
	}

	.activity-stepper .step-pill .step-number {
		width: 34px;
		height: 34px;
		background: rgba(255, 255, 255, .2);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: 600;
	}

	.activity-card {
		background: #fff;
		border-radius: 18px;
		box-shadow: 0 12px 40px rgba(22, 43, 76, .08);
		padding: 1.25rem;
		margin-bottom: 1.5rem;
	}

	.activity-card h5,
	.activity-card h6 {
		font-weight: 600;
	}

	.inline-qr-shell {
		border: 2px dashed #d0d7e6;
		border-radius: 16px;
		padding: 1rem;
		background: #f8fbff;
	}

	.inline-qr-shell.active {
		border-color: #0d6efd;
		background: #eef5ff;
	}

	#qr-reader {
		width: 100% !important;
		min-height: 280px;
	}

	#qr-reader__scan_region video {
		border-radius: 8px;
	}

	#photo-evidence-card {
		border: 1px solid #e5ebf5;
		border-radius: 18px;
		padding: 1rem;
		background: #fdfefe;
	}

	.proof-card-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: .75rem;
	}

	.activity-photo-actions .btn {
		border-radius: 12px;
		font-weight: 600;
	}

	.activity-photo-actions .btn i {
		font-size: 1.1rem;
	}

	.optional-card {
		border: 1px dashed #d5ddee;
		border-radius: 16px;
		padding: .75rem 1rem;
		background: #f8fbff;
	}

	.optional-toggle {
		background: transparent;
		border: none;
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-weight: 600;
		color: #0d2a45;
	}

	.optional-toggle i {
		transition: transform .2s ease;
	}

	.optional-toggle[aria-expanded="true"] i {
		transform: rotate(180deg);
	}

	.floating-photo-btn {
		position: sticky;
		bottom: 1rem;
		margin-left: auto;
		display: inline-flex;
		align-items: center;
		gap: .5rem;
		border-radius: 999px;
		box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
		z-index: 5;
	}

	.photo-highlight {
		box-shadow: 0 0 0 3px rgba(255, 193, 7, .5);
		transition: box-shadow .3s ease;
	}

	#patrol-status-table-wrapper.camera-hidden {
		display: none !important;
	}

	#camera-container video {
		border-radius: 16px;
		min-height: 220px;
		background: #000;
	}

	@media (max-width: 576px) {
		.activity-card {
			padding: 1rem;
		}

		.activity-stepper {
			flex-direction: column;
		}
	}
</style>
<div class="container mt-4">
	<div class="text-center text-light">
		<h5 class="m-0"><?= $user['nama'] ?></h5>
		<p class="p-0"><?= $data_setelah_nama_user ?></p>
	</div>

	<div class="bg-light p-4 mt-4 mb-4 rounded-3 shadow-sm">
		<div class="d-flex justify-content-between">
			<div class="hari-tanggal">
				<?= $nama_hari[date('w')] . ', ' . date('d') . ' ' . $nama_bulan[date('n')] . ' ' . date('Y') ?>
			</div>
			<div class="text-end fw-semibold" id="live-jam"><?= date('H:i:s') ?></div>
		</div>
	</div>

	<?php if (empty($companies)): ?>
		<div class="activity-card">
			<div class="alert alert-warning mb-0">
				<i class="fas fa-exclamation-triangle me-2"></i>
				Anda belum di-assign ke company manapun. Silahkan hubungi admin.
			</div>
		</div>
	<?php else: ?>
		<div class="activity-stepper mb-3" id="activity-stepper">
			<div class="step-pill active" data-step="1">
				<div class="step-number">1</div>
				<div>
					<div class="small text-uppercase">Langkah 1</div>
					<div class="fw-semibold">Scan Patrol</div>
				</div>
			</div>
			<div class="step-pill" data-step="2">
				<div class="step-number">2</div>
				<div>
					<div class="small text-uppercase">Langkah 2</div>
					<div class="fw-semibold">Isi Aktifitas</div>
				</div>
			</div>
			<div class="step-pill" data-step="3">
				<div class="step-number">3</div>
				<div>
					<div class="small text-uppercase">Langkah 3</div>
					<div class="fw-semibold">Review & Simpan</div>
				</div>
			</div>
		</div>

		<div id="inline-alert-container"></div>

		<div class="activity-card" id="step-1" data-step-container>
			<h5 class="mb-3"><i class="fas fa-route me-2 text-primary"></i>Pilih Lokasi & Scan QR Patrol</h5>

			<div class="detection-wrapper mb-4">
				<ul class="nav nav-pills nav-fill activity-detection-tabs" id="company-detection-tabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active" data-bs-toggle="pill" type="button"
							data-bs-target="#auto-detect-tab">
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
						<div id="company-detecting" class="text-center py-4 bg-light rounded">
							<div class="spinner-border text-primary" role="status"></div>
							<p class="mt-2 mb-0"><small>Mendeteksi lokasi Anda...</small></p>
						</div>
						<div id="company-detected" style="display:none;">
							<div class="alert alert-success mb-0">
								<i class="fas fa-map-marker-alt me-2"></i>
								<strong id="detected-company-name"></strong>
								<span class="badge bg-info text-dark ms-2" id="detected-company-setting"
									style="display:none;"></span>
								<br>
								<small id="detected-company-distance"></small>
							</div>
						</div>
						<div id="company-not-found" style="display:none;">
							<div class="alert alert-danger mb-0">
								<i class="fas fa-exclamation-triangle me-2"></i>
								<strong>Anda tidak berada di lokasi company manapun!</strong>
								<br>
								<small>Silahkan pergi ke lokasi company yang sudah di-assign.</small>
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
							<i class="fas fa-check me-2"></i>Gunakan Perusahaan Ini
						</button>
					</div>
				</div>
				<input type="hidden" id="id_company" name="id_company" value="">
				<input type="hidden" id="detected-latitude" value="">
				<input type="hidden" id="detected-longitude" value="">
			</div>

			<div id="next-patrol-info" class="alert alert-info mb-3" style="display:none;">
				<i class="fas fa-flag-checkered me-2"></i>
				<strong>Patroli berikutnya:</strong>
				<div id="next-patrol-name" class="mt-1"></div>
				<small id="next-patrol-urutan" class="text-muted"></small>
			</div>

			<div id="patrol-status-table-wrapper" style="display:none;">
				<div class="accordion mb-4" id="patrol-accordion">
					<div class="accordion-item">
						<h2 class="accordion-header" id="headingPatrol">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
								data-bs-target="#patrol-progress-collapse">
								<div class="w-100">
									<div class="d-flex align-items-center mb-2">
										<i class="fas fa-list-check me-2 text-primary"></i>
										<span>Progress Patroli</span>
										<span class="ms-auto badge bg-primary" id="patrol-progress-percent">0%</span>
									</div>
									<div class="progress" style="height: 6px;">
										<div class="progress-bar" role="progressbar" id="patrol-progress-bar"
											style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
									</div>
								</div>
							</button>
						</h2>
						<div id="patrol-progress-collapse" class="accordion-collapse collapse"
							aria-labelledby="headingPatrol">
							<div class="accordion-body">
								<div class="table-responsive">
									<table class="table table-sm table-striped patrol-status-table"
										id="patrol-status-table">
										<thead>
											<tr>
												<th class="text-nowrap">No.</th>
												<th>Lokasi</th>
												<th class="text-center text-nowrap">Aksi</th>
											</tr>
										</thead>
										<tbody>
											<tr class="text-muted">
												<td colspan="5" class="text-center py-3">Belum ada data patroli untuk
													company ini.</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="inline-qr-shell mb-3" id="qr-inline-wrapper" data-wrapper-home="true">
				<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3 gap-2">
					<div>
						<h6 class="mb-1 fw-semibold">Scanner QR Patrol</h6>
						<small class="text-muted">Gunakan tombol besar di bawah untuk mulai scan</small>
					</div>
					<div class="d-flex flex-wrap gap-2">
						<button class="btn btn-outline-secondary btn-sm" type="button" id="btn-retry-inline-qr">
							<i class="fas fa-rotate-right me-1"></i>Scan Ulang
						</button>
						<button class="btn btn-outline-dark btn-sm" type="button" id="btn-open-fullscreen-qr">
							<i class="fas fa-expand me-1"></i>Mode Layar Penuh
						</button>
					</div>
				</div>
				<div id="qr-reader"></div>
				<div id="qr-scanning-status" class="mt-3 small text-muted"></div>
				<!-- Flash Control for QR Scanner -->
				<div id="qr-flash-control-wrapper" class="mt-3 mb-2" style="display:none;">
					<div class="d-flex align-items-center gap-2">
						<small class="text-muted me-2">Flash:</small>
						<div class="btn-group" role="group">
							<button type="button" class="btn btn-sm btn-outline-secondary flash-toggle-qr active" data-flash-mode="auto">
								<i class="fas fa-adjust me-1"></i>Auto
							</button>
							<button type="button" class="btn btn-sm btn-outline-secondary flash-toggle-qr" data-flash-mode="on">
								<i class="fas fa-lightbulb me-1"></i>On
							</button>
							<button type="button" class="btn btn-sm btn-outline-secondary flash-toggle-qr" data-flash-mode="off">
								<i class="fas fa-lightbulb me-1"></i>Off
							</button>
						</div>
					</div>
					<small id="qr-flash-support-text" class="text-muted d-block mt-1"></small>
				</div>
				<div id="qr-result" class="mt-3" style="display:none;">
					<div class="alert alert-success">
						<i class="fas fa-check-circle me-2"></i>
						<strong>QR Code terdeteksi!</strong>
						<div id="qr-code-text" class="mt-2"></div>
					</div>
				</div>
				<div class="row mt-3 g-2">
					<div class="col-12 col-md-7">
						<button type="button" class="btn btn-primary btn-lg w-100" id="btn-start-inline-qr">
							<i class="fas fa-qrcode me-2"></i>Buka Kamera
						</button>
					</div>
					<div class="col-12 col-md-5">
						<button type="button" class="btn btn-outline-secondary btn-lg w-100" id="btn-manual-validation">
							<i class="fas fa-bolt me-2"></i>Validasi Manual
						</button>
					</div>
				</div>
				<div class="text-muted small mt-2">Gunakan “Validasi Manual” jika QR sulit terbaca.</div>
			</div>

			<div id="qr-scan-result" class="mt-3" style="display:none;">
				<div class="alert alert-success">
					<i class="fas fa-check-circle me-2"></i>
					<strong>QR Code Berhasil Di-scan!</strong>
					<div id="scanned-patrol-info" class="mt-2"></div>
					<button type="button" class="btn btn-success btn-sm mt-2" id="btn-proceed-to-step2">
						<i class="fas fa-arrow-right me-1"></i>Lanjut ke Step 2
					</button>
				</div>
			</div>
		</div>

		<!-- Step 2 -->
		<div class="activity-card" id="step-2" data-step-container style="display:none;">
			<h5 class="mb-3"><i class="fas fa-clipboard-list me-2 text-primary"></i>Isi Detail</h5>
			<form id="form-activity">
				<input type="hidden" id="id_patrol" name="id_patrol" value="">
				<input type="hidden" id="scanned_barcode" name="scanned_barcode" value="">
				<input type="hidden" id="foto_activity" name="foto_activity">

				<div class="mb-3">
					<label class="form-label fw-semibold text-uppercase small text-muted">Judul <span
							class="text-danger">*</span></label>
					<input type="text" class="form-control form-control-lg border-2" id="judul_activity"
						name="judul_activity" placeholder="Contoh: Cek area lobby" required>
					<small class="text-muted">Gunakan judul singkat agar aktivitas mudah dikenali.</small>
				</div>

				<div id="photo-evidence-card" class="mb-4">
					<div class="proof-card-header">
						<div>
							<h6 class="mb-0">Foto / Bukti Lapangan</h6>
							<small class="text-muted">Ambil foto atau pilih dari galeri sebagai bukti patroli.</small>
						</div>
						<button type="button" class="btn btn-outline-primary btn-sm" id="btn-scroll-to-photo">
							<i class="fas fa-camera me-1"></i>Fokus Foto
						</button>
					</div>
					<div class="activity-photo-actions mb-3 mt-3">
						<button type="button" class="btn btn-success w-100 mb-2" id="btn-open-camera">
							<i class="fas fa-camera me-2"></i>Ambil Foto (Kamera)
						</button>
						<button type="button" class="btn btn-outline-primary w-100" id="btn-open-gallery">
							<i class="fas fa-image me-2"></i>Pilih dari Galeri
						</button>
						<input type="file" accept="image/*" id="gallery-file-input" class="d-none">
						<div class="mt-3" id="flash-control-wrapper" style="display:none;">
							<label class="form-label fw-semibold small text-uppercase text-muted mb-1">Lampu</label>
							<div class="btn-group w-100" role="group" aria-label="Flash control">
								<button type="button" class="btn btn-outline-secondary flash-toggle active"
									data-flash-mode="auto">Auto</button>
								<button type="button" class="btn btn-outline-secondary flash-toggle"
									data-flash-mode="on">On</button>
								<button type="button" class="btn btn-outline-secondary flash-toggle"
									data-flash-mode="off">Off</button>
							</div>
							<small class="text-muted d-block mt-1" id="flash-support-text">Sesuaikan lampu saat mengambil
								foto.</small>
						</div>
					</div>
					<div id="camera-container" class="mt-3" style="display:none;">
						<video id="my_camera" autoplay playsinline class="img-fluid rounded mb-2"></video>
						<div class="d-flex gap-2">
							<button type="button" class="btn btn-outline-secondary w-50" id="btn-switch-camera">
								<i class="fas fa-sync-alt me-2"></i>Ganti Kamera
							</button>
							<button type="button" class="btn btn-primary w-50" id="btn-capture">
								<i class="fas fa-camera me-2"></i>Ambil Foto
							</button>
						</div>
						<small class="text-muted d-block mt-2" id="camera-status-text"></small>
					</div>
					<div id="photos-preview-container" class="mt-3"></div>
				</div>

				<div class="optional-card mb-4">
					<button class="optional-toggle" type="button" data-bs-toggle="collapse"
						data-bs-target="#description-collapse" aria-expanded="false">
						<span>Tambahkan Deskripsi (Opsional)</span>
						<i class="fas fa-chevron-down"></i>
					</button>
					<div class="collapse" id="description-collapse">
						<div class="pt-3">
							<textarea class="form-control border-2" rows="3" id="deskripsi_activity"
								name="deskripsi_activity"
								placeholder="Detail tambahan mengenai pekerjaan, kondisi, atau temuan (opsional)"></textarea>
							<small class="text-muted">Opsional. Isi jika perlu menambahkan catatan.</small>
						</div>
					</div>
				</div>

				<button type="button" class="btn btn-warning floating-photo-btn mb-3" id="btn-floating-photo">
					<i class="fas fa-plus"></i>
					Tambah Foto Cepat
				</button>

				<div class="text-center">
					<button type="button" class="btn btn-outline-secondary me-2" id="btn-back-to-step1">
						<i class="fas fa-arrow-left me-1"></i>Kembali
					</button>
					<button type="button" class="btn btn-success" id="btn-proceed-to-step3">
						<i class="fas fa-arrow-right me-1"></i>Review Data
					</button>
				</div>
			</form>
		</div>

		<!-- Step 3 -->
		<div class="activity-card" id="step-3" data-step-container style="display:none;">
			<h5 class="mb-3"><i class="fas fa-eye me-2 text-primary"></i>Review & Simpan Activity</h5>
			<div class="card border-0 shadow-sm mb-3">
				<div class="card-body" id="review-content">
					<div class="text-center text-muted">Data akan muncul setelah Anda melengkapi Step 2.</div>
				</div>
			</div>
			<div class="text-center">
				<button type="button" class="btn btn-outline-secondary me-2" id="btn-back-to-step2">
					<i class="fas fa-arrow-left me-1"></i>Kembali
				</button>
				<button type="button" class="btn btn-primary" id="btn-save-activity">
					<i class="fas fa-save me-2"></i>Simpan
				</button>
			</div>
		</div>
	<?php endif; ?>

	<div class="text-center mt-4 mb-5">
		<a href="<?= base_url() ?>mobile-activity/riwayat" class="btn btn-outline-light btn-lg px-4">
			<i class="fas fa-history me-2"></i>Lihat Riwayat
		</a>
	</div>
</div>

<script>
	var assignedCompanies = <?= json_encode($companies ?? []) ?>;
	var assignedPatrols = <?= json_encode($companies_patrols ?? []) ?>;
	
	// Populate manual company select dropdown
	function populateManualCompanyOptions() {
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
	}
	
	// Wait for jQuery to be available
	function waitForJQuery(callback) {
		if (typeof jQuery !== 'undefined') {
			callback(jQuery);
		} else {
			setTimeout(function() {
				waitForJQuery(callback);
			}, 100);
		}
	}
	
	// Populate on page load
	document.addEventListener('DOMContentLoaded', function() {
		populateManualCompanyOptions();
	});
	
	// Also populate when manual tab is shown - wait for jQuery
	waitForJQuery(function($) {
		$(document).ready(function() {
			// Tab change event listener - populate dropdown when manual tab is shown
			$('#company-detection-tabs button[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
				var targetTab = $(e.target).data('bs-target');
				console.log('Tab shown:', targetTab);
				if (targetTab === '#manual-detect-tab') {
					console.log('Manual tab shown, populating dropdown...');
					populateManualCompanyOptions();
				}
			});
			
			// Manual company selection dropdown change handler
			$('#manual-company-select').on('change', function () {
				var selectedValue = $(this).val();
				var confirmBtn = $('#btn-confirm-manual-company');
				console.log('Dropdown changed, selected value:', selectedValue);
				if (selectedValue && selectedValue !== '') {
					confirmBtn.prop('disabled', false);
				} else {
					confirmBtn.prop('disabled', true);
				}
			});
			
			// Manual company confirmation button handler
			$('#btn-confirm-manual-company').on('click', function () {
				var selectedCompanyId = $('#manual-company-select').val();
				if (!selectedCompanyId) {
					if (typeof Swal !== 'undefined') {
						Swal.fire('Error', 'Pilih perusahaan terlebih dahulu.', 'error');
					} else {
						alert('Pilih perusahaan terlebih dahulu.');
					}
					return;
				}
				
				// Find company data from assignedCompanies
				var selectedCompany = null;
				if (typeof assignedCompanies !== 'undefined' && Array.isArray(assignedCompanies)) {
					for (var i = 0; i < assignedCompanies.length; i++) {
						if (assignedCompanies[i].id_company == selectedCompanyId) {
							selectedCompany = assignedCompanies[i];
							break;
						}
					}
				}
				
				if (!selectedCompany) {
					if (typeof Swal !== 'undefined') {
						Swal.fire('Error', 'Data perusahaan tidak ditemukan.', 'error');
					} else {
						alert('Data perusahaan tidak ditemukan.');
					}
					return;
				}
				
				// Set company ID in hidden field
				$('#id_company').val(selectedCompanyId);
				
				// Set detected coordinates (use company location or current GPS if available)
				var detectedLat = selectedCompany.latitude || '';
				var detectedLng = selectedCompany.longitude || '';
				$('#detected-latitude').val(detectedLat);
				$('#detected-longitude').val(detectedLng);
				
				// Show success message
				$('#company-detecting').hide();
				$('#company-not-found').hide();
				$('#company-detected').show();
				$('#detected-company-name').text(selectedCompany.nama_company || 'Perusahaan');
				$('#detected-company-distance').text('Dipilih secara manual');
				
				// Trigger companyDetected event
				var event = new CustomEvent('companyDetected', {
					detail: {
						companyId: selectedCompanyId,
						company: selectedCompany
					}
				});
				window.dispatchEvent(event);
				
				// Switch to auto-detect tab to show the detected company
				var tabButton = $('#company-detection-tabs button[data-bs-target="#auto-detect-tab"]');
				if (tabButton.length && typeof tabButton.tab === 'function') {
					tabButton.tab('show');
				}
				
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'success',
						title: 'Perusahaan Dipilih',
						text: selectedCompany.nama_company + ' telah dipilih.',
						timer: 2000,
						showConfirmButton: false
					});
				}
			});
		});
	});
</script>

<!-- QR Scanner Modal for fullscreen fallback -->
<div class="modal fade" id="qrScannerModal" tabindex="-1">
	<div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Mode Layar Penuh - QR Patrol</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div id="qr-modal-placeholder"></div>
			</div>
			<div class="modal-footer justify-content-between align-items-center w-100 flex-column flex-md-row">
				<div class="text-start small text-muted w-100 mb-2 mb-md-0">
					<i class="fas fa-circle-info me-1"></i>Jika kamera bermasalah, tutup modal lalu coba ulang.
				</div>
				<div class="d-flex gap-2">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
				</div>
			</div>
		</div>
	</div>
</div>
<?= $this->endSection() ?>