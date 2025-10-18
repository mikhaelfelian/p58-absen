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
		
		<form id="form-activity">
			<div class="mb-3">
				<label class="form-label">Pilih Company <span class="text-danger">*</span></label>
				<select class="form-select" id="id_company" name="id_company" required>
					<option value="">-- Pilih Company --</option>
					<?php foreach ($companies as $company): ?>
					<option value="<?=$company->id_company?>"><?=$company->nama_company?></option>
					<?php endforeach; ?>
				</select>
			</div>
			
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
			
			<div id="alert-container"></div>
			
			<button type="submit" class="btn btn-success w-100 mt-3" id="btn-submit">
				<i class="fas fa-save me-2"></i>Simpan Activity
			</button>
		</form>
		
		<?php endif; ?>
	</div>
	
	<div class="text-center mt-4 mb-5">
		<a href="<?=base_url()?>mobile-activity/riwayat" class="btn btn-outline-light">
			<i class="fas fa-history me-2"></i>Lihat Riwayat Activity
		</a>
	</div>
</div>

<script>
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
	
	// Submit form
	jQuery('#form-activity').on('submit', function(e) {
		e.preventDefault();
		
		var id_company = jQuery('#id_company').val();
		var judul_activity = jQuery('#judul_activity').val();
		var deskripsi_activity = jQuery('#deskripsi_activity').val();
		var foto = jQuery('#foto_activity').val();
		
		if (!id_company) {
			showAlert('error', 'Company harus dipilih');
			return false;
		}
		
		if (!judul_activity) {
			showAlert('error', 'Judul activity harus diisi');
			return false;
		}
		
		if (!deskripsi_activity) {
			showAlert('error', 'Deskripsi activity harus diisi');
			return false;
		}
		
		var data = {
			id_company: id_company,
			judul_activity: judul_activity,
			deskripsi_activity: deskripsi_activity,
			foto: foto,
			location: currentLocation
		};
		
		var data_encoded = btoa(JSON.stringify(data));
		
		jQuery('#btn-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
		
		jQuery.ajax({
			url: base_url + 'mobile-activity/ajaxSaveActivity',
			type: 'POST',
			data: {data: data_encoded},
			dataType: 'json',
			success: function(response) {
				if (response.status == 'ok') {
					Swal.fire({
						icon: 'success',
						title: 'Berhasil!',
						text: response.message,
						confirmButtonText: 'OK'
					}).then(() => {
						location.reload();
					});
				} else {
					showAlert('error', response.message);
					jQuery('#btn-submit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Activity');
				}
			},
			error: function() {
				showAlert('error', 'Terjadi kesalahan saat menyimpan data');
				jQuery('#btn-submit').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Activity');
			}
		});
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

