<?= $this->extend('themes/modern/layout-mobile') ?>
<?= $this->section('content') ?>
<?php
$nama_bulan = nama_bulan();
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
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
	
	<div class="bg-light p-4 rounded-3">
		<h5 class="mb-3">Input Activity</h5>
		
		<?php if (empty($companies)): ?>
		<div class="alert alert-warning">
			<i class="fas fa-exclamation-triangle me-2"></i>
			Anda belum di-assign ke company manapun. Silahkan hubungi admin.
		</div>
		<?php else: ?>
		
		<!-- Step 1: QR Code Scanning -->
		<div id="step-1" class="step-container">
			<div class="text-center mb-4">
				<div class="step-number">1</div>
				<h5>Scan QR Code Patrol</h5>
				<p class="text-muted">Silakan scan QR code di titik patroli untuk memulai activity</p>
			</div>
			
			<div class="mb-3">
				<label class="form-label">Lokasi Company</label>
				<!-- Auto-detect company based on GPS location -->
				<div id="company-detecting" class="text-center py-3 bg-light rounded">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Loading...</span>
					</div>
					<p class="mt-2 mb-0"><small>Mendeteksi lokasi Anda...</small></p>
				</div>
				<div id="company-detected" style="display:none;">
					<div class="alert alert-success mb-0">
						<i class="fas fa-map-marker-alt me-2"></i>
						<strong id="detected-company-name"></strong>
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
				<input type="hidden" id="id_company" name="id_company" value="">
			</div>
			
			<div class="text-center">
				<button type="button" class="btn btn-primary btn-lg" id="btn-scan-qr">
					<i class="fas fa-qrcode me-2"></i>Scan QR Code Patrol
				</button>
			</div>
			
			<!-- QR Scan Result -->
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
		
		<!-- Step 2: Activity Form -->
		<div id="step-2" class="step-container" style="display:none;">
			<div class="text-center mb-4">
				<div class="step-number">2</div>
				<h5>Isi Detail Activity</h5>
				<p class="text-muted">Lengkapi informasi activity dan upload foto</p>
			</div>
			
			<form id="form-activity">
				<input type="hidden" id="id_patrol" name="id_patrol" value="">
				<input type="hidden" id="scanned_barcode" name="scanned_barcode" value="">
		
		<!-- Store companies data for JavaScript -->
		<script>
		var assignedCompanies = <?=json_encode($companies ?? [])?>;
		</script>
		
		<style>
		.step-container {
			animation: fadeIn 0.5s ease-in-out;
		}
		
		.step-number {
			width: 50px;
			height: 50px;
			border-radius: 50%;
			background: #007bff;
			color: white;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 20px;
			font-weight: bold;
			margin-bottom: 15px;
		}
		
		@keyframes fadeIn {
			from { opacity: 0; transform: translateY(20px); }
			to { opacity: 1; transform: translateY(0); }
		}
		</style>
			
				<div class="mb-3">
					<label class="form-label">Judul Activity <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="judul_activity" name="judul_activity" placeholder="Contoh: Meeting dengan client" required>
				</div>
				
				<div class="mb-3">
					<label class="form-label">Deskripsi Activity <span class="text-danger">*</span></label>
					<textarea class="form-control" id="deskripsi_activity" name="deskripsi_activity" rows="4" placeholder="Jelaskan detail pekerjaan yang dilakukan..." required></textarea>
				</div>
				
				<div class="mb-3">
					<label class="form-label">Foto Activity</label>
					<div id="camera-container" style="display:none">
						<div id="my_camera" class="mb-2"></div>
						<button type="button" class="btn btn-primary btn-sm w-100" id="btn-capture">
							<i class="fas fa-camera me-2"></i>Ambil Foto
						</button>
					</div>
					<div id="preview-container" style="display:none">
						<img id="preview-image" src="" class="img-fluid rounded mb-2" style="max-height:300px">
						<button type="button" class="btn btn-warning btn-sm w-100" id="btn-retake">
							<i class="fas fa-redo me-2"></i>Ambil Ulang
						</button>
					</div>
					<button type="button" class="btn btn-info btn-sm w-100" id="btn-open-camera">
						<i class="fas fa-camera me-2"></i>Buka Kamera
					</button>
					<input type="hidden" id="foto_activity" name="foto_activity">
				</div>
				
				<div class="text-center">
					<button type="button" class="btn btn-secondary me-2" id="btn-back-to-step1">
						<i class="fas fa-arrow-left me-1"></i>Kembali
					</button>
					<button type="button" class="btn btn-success" id="btn-proceed-to-step3">
						<i class="fas fa-arrow-right me-1"></i>Lanjut ke Step 3
					</button>
				</div>
			</form>
		</div>
		
		<!-- Step 3: Review and Save -->
		<div id="step-3" class="step-container" style="display:none;">
			<div class="text-center mb-4">
				<div class="step-number">3</div>
				<h5>Review & Simpan</h5>
				<p class="text-muted">Periksa kembali data activity sebelum disimpan</p>
			</div>
			
			<div class="card">
				<div class="card-body">
					<h6 class="card-title">Detail Activity</h6>
					<div id="review-content">
						<!-- Review content will be populated here -->
					</div>
				</div>
			</div>
			
			<div class="text-center mt-3">
				<button type="button" class="btn btn-secondary me-2" id="btn-back-to-step2">
					<i class="fas fa-arrow-left me-1"></i>Kembali
				</button>
				<button type="button" class="btn btn-success" id="btn-save-activity">
					<i class="fas fa-save me-2"></i>Simpan Activity
				</button>
			</div>
		</div>
		
		<?php endif; ?>
	</div>
	
	<div class="text-center mt-4 mb-5">
		<a href="<?=base_url()?>mobile-activity/riwayat" class="btn btn-outline-light">
			<i class="fas fa-history me-2"></i>Lihat Riwayat Activity
		</a>
	</div>
</div>

<!-- QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Scan QR Code Patrol</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body text-center">
				<div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
				<div id="qr-scanning-status" class="mt-3">
					<div class="alert alert-info">
						<i class="fas fa-search me-2"></i>
						<strong>Mencari QR Code...</strong>
						<br>
						<small>Arahkan kamera ke QR code patrol</small>
					</div>
				</div>
				<div id="qr-result" class="mt-3" style="display:none;">
					<div class="alert alert-success">
						<i class="fas fa-check-circle me-2"></i>
						<strong>QR Code Terdeteksi:</strong>
						<div id="qr-code-text" class="mt-2"></div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-info btn-sm" id="btn-test-qr">
					<i class="fas fa-qrcode me-1"></i>Test QR
				</button>
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
				<button type="button" class="btn btn-primary" id="btn-validate-qr" style="display:none;">Validasi</button>
			</div>
		</div>
	</div>
</div>

<script>
// GPS-based company auto-detection (anti-cheating)
(function() {
	// Track current step
	var currentStep = 1;
	
	// Function to calculate distance between two coordinates
	function getDistance(lat1, lon1, lat2, lon2) {
		const R = 6371; // Radius of Earth in kilometers
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLon = (lon2 - lon1) * Math.PI / 180;
		const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
				  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
				  Math.sin(dLon/2) * Math.sin(dLon/2);
		const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
		const distance = R * c;
		return distance; // in kilometers
	}
	
	// Auto-detect company based on GPS
	if (navigator.geolocation && typeof assignedCompanies !== 'undefined') {
		navigator.geolocation.getCurrentPosition(function(position) {
			var userLat = position.coords.latitude;
			var userLon = position.coords.longitude;
			
			// Store location globally
			window.currentLocation = {
				coords: {
					latitude: userLat,
					longitude: userLon
				}
			};
			
			// Find nearest company within radius
			var nearestCompany = null;
			var minDistance = Infinity;
			
			for (var i = 0; i < assignedCompanies.length; i++) {
				var company = assignedCompanies[i];
				var companyLat = parseFloat(company.latitude);
				var companyLon = parseFloat(company.longitude);
				var radiusNilai = parseFloat(company.radius_nilai);
				var radiusSatuan = company.radius_satuan;
				
				// Convert radius to kilometers
				var radiusKm = radiusSatuan === 'm' ? radiusNilai / 1000 : radiusNilai;
				
				// Calculate distance
				var distance = getDistance(userLat, userLon, companyLat, companyLon);
				
				// Check if within radius
				if (distance <= radiusKm && distance < minDistance) {
					minDistance = distance;
					nearestCompany = company;
				}
			}
			
			// Hide detecting spinner
			document.getElementById('company-detecting').style.display = 'none';
			
			if (nearestCompany) {
				// Company detected!
				document.getElementById('company-detected').style.display = 'block';
				document.getElementById('detected-company-name').textContent = nearestCompany.nama_company;
				
				var distanceText = minDistance < 1 
					? Math.round(minDistance * 1000) + ' meter dari lokasi company'
					: minDistance.toFixed(2) + ' km dari lokasi company';
				document.getElementById('detected-company-distance').textContent = 'Anda berada ' + distanceText;
				
				// Set hidden field
				document.getElementById('id_company').value = nearestCompany.id_company;
				
			// Check if patrol is required
			// isPatrolRequired is already set on backend (company mode + user requirement)
			var isPatrolRequired = nearestCompany.isPatrolRequired === 1;
			
			console.log('isPatrolRequired:', isPatrolRequired);
			
			// If patrol is NOT required, skip step 1 and go to step 2
			if (!isPatrolRequired) {
				console.log('Skipping patrol scan - going directly to step 2');
				// Hide step 1, show step 2
				$('#step-1').hide();
				$('#step-2').show();
				currentStep = 2;
			} else {
				console.log('Patrol required - showing step 1');
				// Show step 1 for QR scanning
				$('#step-1').show();
				$('#step-2').hide();
				currentStep = 1;
			}
			} else {
				// No company found within radius
				document.getElementById('company-not-found').style.display = 'block';
				
				// Disable submit button
				var submitBtn = document.getElementById('btn-submit');
				if (submitBtn) {
					submitBtn.disabled = true;
					submitBtn.style.opacity = '0.5';
				}
			}
		}, function(error) {
			// GPS error
			document.getElementById('company-detecting').style.display = 'none';
			document.getElementById('company-not-found').style.display = 'block';
			var alertDiv = document.getElementById('company-not-found').querySelector('.alert');
			if (alertDiv) {
				alertDiv.innerHTML = 
					'<i class="fas fa-exclamation-triangle me-2"></i>' +
					'<strong>Gagal mendapatkan lokasi GPS!</strong><br>' +
					'<small>Pastikan GPS/Location diaktifkan di browser Anda.</small>';
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
	var currentLocation = null;
	
	// Get current location
	if (navigator.geolocation) {
		navigator.geolocation.getCurrentPosition(function(position) {
			currentLocation = {
				coords: {
					latitude: position.coords.latitude,
					longitude: position.coords.longitude
				}
			};
		}, function(error) {
			console.log('Error getting location:', error);
		});
	}
	
	// Open camera
	jQuery('#btn-open-camera').on('click', function() {
		jQuery('#camera-container').show();
		jQuery('#btn-open-camera').hide();
		
		Webcam.set({
			width: 320,
			height: 240,
			image_format: 'jpeg',
			jpeg_quality: 90
		});
		Webcam.attach('#my_camera');
	});
	
	// Capture photo
	jQuery('#btn-capture').on('click', function() {
		Webcam.snap(function(data_uri) {
			jQuery('#preview-image').attr('src', data_uri);
			jQuery('#foto_activity').val(data_uri);
			jQuery('#camera-container').hide();
			jQuery('#preview-container').show();
			Webcam.reset();
		});
	});
	
	// Retake photo
	jQuery('#btn-retake').on('click', function() {
		jQuery('#preview-container').hide();
		jQuery('#camera-container').show();
		jQuery('#foto_activity').val('');
		Webcam.attach('#my_camera');
	});
	
	// QR Scanner button click handler
	jQuery('#btn-scan-qr').on('click', function() {
		jQuery('#qrScannerModal').modal('show');
		// QR scanner will be initialized by main-mobile.js modal events
	});
	
	function showAlert(type, message) {
		var alertClass = type == 'error' ? 'alert-danger' : 'alert-success';
		var icon = type == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
		
		var html = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">';
		html += '<i class="fas ' + icon + ' me-2"></i>';
		html += Array.isArray(message) ? message.join('<br>') : message;
		html += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
		html += '</div>';
		
		jQuery('#alert-container').html(html);
	}
	
	// Update live time
	setInterval(function() {
		var now = new Date();
		var hours = String(now.getHours()).padStart(2, '0');
		var minutes = String(now.getMinutes()).padStart(2, '0');
		var seconds = String(now.getSeconds()).padStart(2, '0');
		var liveJam = document.getElementById('live-jam');
		if (liveJam) {
			liveJam.textContent = hours + ':' + minutes + ':' + seconds;
		}
	}, 1000);
})();
</script>
<?= $this->endSection() ?>

