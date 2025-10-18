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
	
	<?php if (empty($companies)): ?>
	<div class="alert alert-warning">
		<i class="fas fa-exclamation-triangle me-2"></i>
		Anda belum di-assign ke company manapun. Silahkan hubungi admin untuk melakukan assignment.
	</div>
	
	<?php else: ?>
	<?php
	// Check if user has already checked in today
	$curr_date = date('Y-m-d');
	$today_company_id = null;
	$today_company_name = '';
	$is_readonly = false;
	
	if (key_exists($curr_date, $riwayat_presensi)) {
		if (key_exists('masuk', $riwayat_presensi[$curr_date])) {
			if (!empty($riwayat_presensi[$curr_date]['masuk']['id_company'])) {
				$today_company_id = $riwayat_presensi[$curr_date]['masuk']['id_company'];
				$is_readonly = true;
				// Get company name
				foreach ($companies as $comp) {
					if ($comp->id_company == $today_company_id) {
						$today_company_name = $comp->nama_company;
						break;
					}
				}
			}
		}
	}
	?>
	<div class="bg-light p-3 mb-3 rounded-3">
		<label class="form-label mb-2"><strong>Lokasi Company</strong></label>
		<?php if ($is_readonly): ?>
		<input type="text" class="form-control" value="<?=$today_company_name?>" readonly>
		<input type="hidden" id="id_company" name="id_company" value="<?=$today_company_id?>">
		<small class="text-success d-block mt-1">
			<i class="fas fa-lock me-1"></i>
			Company sudah terpilih untuk hari ini. Tidak dapat diubah setelah absen masuk.
		</small>
		<?php else: ?>
		<!-- Auto-detect company based on GPS location -->
		<div id="company-detecting" class="text-center py-3">
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
		<input type="hidden" id="detected-latitude" value="">
		<input type="hidden" id="detected-longitude" value="">
		<?php endif; ?>
	</div>
	
	<!-- Store companies data for JavaScript -->
	<script>
	var assignedCompanies = <?=json_encode($companies ?? [])?>;
	</script>
	<?php endif; ?>
	
	<?php
	$waktu_masuk = $waktu_pulang = 'Belum absen';
	$curr_date = date('Y-m-d');
	if (key_exists($curr_date, $riwayat_presensi)) 
	{
		if (key_exists('masuk', $riwayat_presensi[$curr_date])) {
			if ($riwayat_presensi[$curr_date]['masuk']['presensi_masuk']) {
				$waktu_masuk = $riwayat_presensi[$curr_date]['masuk']['presensi_masuk'];
			}
		}
		
		if (key_exists('pulang', $riwayat_presensi[$curr_date])) {
			if ($riwayat_presensi[$curr_date]['pulang']['presensi_pulang']) {
				$waktu_pulang = $riwayat_presensi[$curr_date]['pulang']['presensi_pulang'];
			}
		}
		
	}
	?>
	<div class="row">
		<div class="col-6 pe-2">
			<a id="presensi-masuk" href="#" class="presensi-container box-absen-masuk d-flex rounded-3 px-4 py-4 w-100">
				<div class="d-flex align-items-center w-100">
					<i class="bi bi-box-arrow-in-right me-3 text-success icon-box-presensi" style="font-size:30px"></i>
					<div class="w-100">
						<h5 class="m-0 p-0">Masuk</h5>
						<p class="mt-0 mb-0 waktu-presensi"><?=$waktu_masuk?></p>
						<hr class="mt-2 mb-2 w-100"/>
						<?php
						$exp = explode(':', $setting_presensi['waktu_masuk_awal']);
						$waktu_awal = $exp[0] .':' . $exp[1];
						$exp = explode(':', $setting_presensi['waktu_masuk_akhir']);
						$waktu_akhir = $exp[0] .':' . $exp[1];
						?>
						<p class="mt-0 mb-0"><?=$waktu_awal?> s.d. <?=$waktu_akhir?></p>
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
						<hr class="mt-2 mb-2 w-100"/>
						<?php
						$exp = explode(':', $setting_presensi['waktu_pulang_awal']);
						$waktu_awal = $exp[0] .':' . $exp[1];
						$exp = explode(':', $setting_presensi['waktu_pulang_akhir']);
						$waktu_akhir = $exp[0] .':' . $exp[1];
						?>
						<p class="mt-0 mb-0"><?=$waktu_awal?> s.d. <?=$waktu_akhir?></p>
					</div>
				</div>
			</a>
		</div>
	</div>
	<div id="alert-lokasi">
	</div>
	<p class="text-light mt-4">
	Riwayat Presensi
	</p>
		<div class="bg-light p-4 rounded-3">
			<?php
			$nama_bulan = nama_bulan();
			$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
			$end_date = strtotime(date('Y-m-d'));
			$start_date = strtotime('-' . $setting_presensi['jml_riwayat_presensi_home'] . ' days', $end_date);
			$num = 1;
			$hari_kerja = json_decode($setting_presensi['hari_kerja'], true);
			for ($i = $end_date; $i > $start_date; $i = strtotime('-1 day', $i)) {
		
				$waktu_masuk = $waktu_pulang = '-';
				$curr = date('Y-m-d', $i);
				
				$date_w = date('w', $i);
				if (in_array($date_w, $hari_kerja)) {
					if (key_exists($curr, $riwayat_presensi)) 
					{
						if (key_exists('masuk', $riwayat_presensi[$curr])) {
							$waktu_masuk = $riwayat_presensi[$curr]['masuk']['presensi_masuk'];
						}
						
						if (key_exists('pulang', $riwayat_presensi[$curr])) {
							$waktu_pulang = $riwayat_presensi[$curr]['pulang']['presensi_pulang'];
						}
						
					}
				}
				
				$style = '';
				if (!in_array($date_w, $hari_kerja)) {
					$style = ';color:#CCCCCC !important';
				}
				
				
				echo '<div class="mb-2" style="' . $style . '">
					<div class="fs-bold">' . $nama_hari[date('w', $i)] . ', ' . date('d', $i) . ' ' . $nama_bulan[date('n', $i)] . ' ' . date('Y', $i) . '</div>
					<div class="d-flex justify-content-between">
						<div class="d-flex align-items-center">	
							<i class="bi bi-box-arrow-in-right me-2 text-success" style="font-size:20px' . $style . '"></i>
							<span>Masuk</span>
						</div>
						<div class="d-flex align-items-center">	
							<span>' . $waktu_masuk . '</span>
						</div>
					</div>
					<div class="d-flex justify-content-between">
						<div class="d-flex align-items-center">	
							<i class="bi bi-box-arrow-right me-3 text-warning" style="font-size:17px' . $style . '"></i>
							<span>Pulang</span>
						</div>
						<div class="d-flex align-items-center">	
							<span>' . $waktu_pulang . '</span>
						</div>
					</div>
				</div>';
				if ($num < $setting_presensi['jml_riwayat_presensi_home']) {
					echo '<hr/>';
				}
				$num++;
			}
			?>
		</div>
	<input type="hidden" id="page-type" value="kasir"/>
	<input type="hidden" id="selected-company-id" value=""/>
	<input type="hidden" id="selected-company-lat" value=""/>
	<input type="hidden" id="selected-company-lng" value=""/>
	<input type="hidden" id="selected-company-radius" value=""/>
	<input type="hidden" id="selected-company-satuan" value=""/>
</div>
<span id="setting-presensi" style="display:none"><?=json_encode($setting_presensi)?></span>
<span id="companies-data" style="display:none"><?=json_encode($companies ?? [])?></span>

<script>
// GPS-based company auto-detection (anti-cheating)
(function() {
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
	
	// Check if company is already locked (readonly mode)
	var companyInput = document.getElementById('id_company');
	if (companyInput && companyInput.type === 'hidden' && companyInput.value) {
		// Already assigned, set hidden fields
		document.getElementById('selected-company-id').value = companyInput.value;
		return;
	}
	
	// Auto-detect company based on GPS
	if (navigator.geolocation && typeof assignedCompanies !== 'undefined') {
		navigator.geolocation.getCurrentPosition(function(position) {
			var userLat = position.coords.latitude;
			var userLon = position.coords.longitude;
			
			// Store user location
			document.getElementById('detected-latitude').value = userLat;
			document.getElementById('detected-longitude').value = userLon;
			
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
				document.getElementById('selected-company-id').value = nearestCompany.id_company;
				document.getElementById('selected-company-lat').value = nearestCompany.latitude;
				document.getElementById('selected-company-lng').value = nearestCompany.longitude;
				document.getElementById('selected-company-radius').value = nearestCompany.radius_nilai;
				document.getElementById('selected-company-satuan').value = nearestCompany.radius_satuan;
			} else {
				// No company found within radius
				document.getElementById('company-not-found').style.display = 'block';
				
				// Disable presensi buttons
				var presensiButtons = document.querySelectorAll('.presensi-container');
				presensiButtons.forEach(function(btn) {
					btn.style.opacity = '0.5';
					btn.style.pointerEvents = 'none';
				});
			}
		}, function(error) {
			// GPS error
			document.getElementById('company-detecting').style.display = 'none';
			document.getElementById('company-not-found').style.display = 'block';
			document.getElementById('company-not-found').querySelector('.alert').innerHTML = 
				'<i class="fas fa-exclamation-triangle me-2"></i>' +
				'<strong>Gagal mendapatkan lokasi GPS!</strong><br>' +
				'<small>Pastikan GPS/Location diaktifkan di browser Anda.</small>';
		}, {
			enableHighAccuracy: true,
			timeout: 10000,
			maximumAge: 0
		});
	}
	
	// Disable presensi buttons if no company detected
	if (typeof jQuery !== 'undefined') {
		jQuery(document).ready(function() {
			jQuery('.presensi-container').on('click', function(e) {
				var companyId = jQuery('#id_company').val();
				if (!companyId) {
					e.preventDefault();
					Swal.fire({
						icon: 'error',
						title: 'Tidak Dapat Absen!',
						text: 'Anda tidak berada di lokasi company yang di-assign.',
						confirmButtonText: 'OK'
					});
					return false;
				}
			});
		});
	}
})();
</script>
<?= $this->endSection() ?>