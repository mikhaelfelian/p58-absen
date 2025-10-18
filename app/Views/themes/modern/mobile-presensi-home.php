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
		<label class="form-label mb-2"><strong>Pilih Company</strong> <span class="text-danger">*</span></label>
		<?php if ($is_readonly): ?>
		<input type="text" class="form-control" value="<?=$today_company_name?>" readonly>
		<input type="hidden" id="id_company" name="id_company" value="<?=$today_company_id?>">
		<small class="text-success d-block mt-1">
			<i class="fas fa-lock me-1"></i>
			Company sudah terpilih untuk hari ini. Tidak dapat diubah setelah absen masuk.
		</small>
		<?php else: ?>
		<select class="form-select" id="id_company" name="id_company" required>
			<option value="">-- Pilih Company --</option>
			<?php foreach ($companies as $company): ?>
			<option value="<?=$company->id_company?>" 
					data-latitude="<?=$company->latitude?>" 
					data-longitude="<?=$company->longitude?>"
					data-radius="<?=$company->radius_nilai?>"
					data-satuan="<?=$company->radius_satuan?>">
				<?=$company->nama_company?>
			</option>
			<?php endforeach; ?>
		</select>
		<small class="text-muted d-block mt-1">Pilih company tempat Anda bekerja hari ini</small>
		<?php endif; ?>
	</div>
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
// Wait for jQuery to load
(function checkjQuery() {
	if (typeof jQuery === 'undefined') {
		setTimeout(checkjQuery, 50);
		return;
	}
	
	// Initialize company data from hidden input if already selected
	var companySelect = jQuery('#id_company');
	if (companySelect.is('input[type="hidden"]')) {
		// Company is locked (readonly mode)
		var companyId = companySelect.val();
		if (companyId) {
			// Get company data from companies-data span
			var companiesData = JSON.parse(jQuery('#companies-data').text() || '[]');
			var selectedCompany = companiesData.find(function(c) { return c.id_company == companyId; });
			if (selectedCompany) {
				jQuery('#selected-company-id').val(companyId);
				jQuery('#selected-company-lat').val(selectedCompany.latitude);
				jQuery('#selected-company-lng').val(selectedCompany.longitude);
				jQuery('#selected-company-radius').val(selectedCompany.radius_nilai);
				jQuery('#selected-company-satuan').val(selectedCompany.radius_satuan);
			}
		}
	} else {
		// Company is selectable (dropdown mode)
		// Handle company selection
		companySelect.on('change', function() {
			var selectedOption = jQuery(this).find('option:selected');
			jQuery('#selected-company-id').val(jQuery(this).val());
			jQuery('#selected-company-lat').val(selectedOption.data('latitude'));
			jQuery('#selected-company-lng').val(selectedOption.data('longitude'));
			jQuery('#selected-company-radius').val(selectedOption.data('radius'));
			jQuery('#selected-company-satuan').val(selectedOption.data('satuan'));
			
			// Show alert with company info
			if (jQuery(this).val()) {
				var companyName = selectedOption.text();
				var radius = selectedOption.data('radius');
				var satuan = selectedOption.data('satuan');
				jQuery('#alert-lokasi').html(
					'<div class="alert alert-info mt-3">' +
					'<i class="fas fa-info-circle me-2"></i>' +
					'Anda akan absen di <strong>' + companyName + '</strong>. ' +
					'Pastikan Anda berada dalam radius <strong>' + radius + ' ' + satuan + '</strong> dari lokasi company.' +
					'</div>'
				);
			} else {
				jQuery('#alert-lokasi').html('');
			}
		});
	}
	
	// Disable presensi buttons if no company selected
	jQuery('.presensi-container').on('click', function(e) {
		var companyId = jQuery('#selected-company-id').val();
		if (!companyId) {
			e.preventDefault();
			Swal.fire({
				icon: 'warning',
				title: 'Perhatian!',
				text: 'Silahkan pilih company terlebih dahulu',
				confirmButtonText: 'OK'
			});
			return false;
		}
	});
})();
</script>
<?= $this->endSection() ?>