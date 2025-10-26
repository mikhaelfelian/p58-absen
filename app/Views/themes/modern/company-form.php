<div class="card">
	<div class="card-header">
		<h5 class="card-title"><?=$title?></h5>
	</div>
	<div class="card-body">
		<?php 
			helper('html');
			
			if (has_permission('create')) {
				echo btn_link(['attr' => ['class' => 'btn btn-success btn-xs'],
					'url' => $module_url . '/add',
					'icon' => 'fa fa-plus',
					'label' => 'Tambah Company'
				]);
			}
			
			echo btn_link(['attr' => ['class' => 'btn btn-light btn-xs'],
				'url' => $module_url,
				'icon' => 'fa fa-arrow-circle-left',
				'label' => 'Daftar Company'
			]);
			
			echo '<hr/>';

			if (!empty($message)) {
				show_message($message);
			}
		?>
		
		<?php if (!empty($message)): ?>
		<script>
		$(document).ready(function() {
			<?php if ($message['status'] == 'success'): ?>
			// Show success message with SweetAlert
			Swal.fire({
				icon: 'success',
				title: 'Berhasil!',
				text: '<?= $message['message'] ?>',
				timer: 3000,
				showConfirmButton: false,
				toast: true,
				position: 'top-end'
			});
			<?php elseif ($message['status'] == 'error'): ?>
			// Show error message with SweetAlert
			Swal.fire({
				icon: 'error',
				title: 'Gagal!',
				text: '<?= $message['message'] ?>',
				confirmButtonText: 'OK'
			});
			<?php endif; ?>
			
			// Scroll to top to show the message
			$('html, body').animate({scrollTop: 0}, 500);
		});
		</script>
		<?php endif; ?>
		<form method="post" action="" class="form-company">
			<input type="hidden" name="id" value="<?=@$company->id_company?>">
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Nama Company <span class="text-danger">*</span></label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="text" class="form-control" name="nama_company" value="<?=@$company->nama_company?>" required>
					<?php if (!empty($form_errors['nama_company'])) echo '<small class="text-danger">' . $form_errors['nama_company'] . '</small>'?>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Alamat</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<textarea class="form-control" name="alamat" rows="3"><?=@$company->alamat?></textarea>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Wilayah</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="hidden" name="id_wilayah_kelurahan" value="<?=@$company->id_wilayah_kelurahan?>">
					<p class="form-control-plaintext"><?=@$company->nama_kelurahan ? $company->nama_kelurahan . ', ' . $company->nama_kecamatan . ', ' . $company->nama_kabupaten . ', ' . $company->nama_propinsi : '-'?></p>
					<small class="text-muted">Fitur wilayah dapat ditambahkan nanti jika diperlukan</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Lokasi GPS <span class="text-danger">*</span></label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<div class="row mb-2">
						<div class="col-5">
							<label>Latitude</label>
							<input type="text" class="form-control" name="latitude" id="latitude" value="<?=@$company->latitude ?: '-7.797068'?>" required>
							<?php if (!empty($form_errors['latitude'])) echo '<small class="text-danger">' . $form_errors['latitude'] . '</small>'?>
						</div>
						<div class="col-5">
							<label>Longitude</label>
							<input type="text" class="form-control" name="longitude" id="longitude" value="<?=@$company->longitude ?: '110.370529'?>" required>
							<?php if (!empty($form_errors['longitude'])) echo '<small class="text-danger">' . $form_errors['longitude'] . '</small>'?>
						</div>
						<div class="col-2">
							<label>&nbsp;</label>
							<button type="button" class="btn btn-info btn-sm w-100" id="btn-current-location" title="Gunakan lokasi saya">
								<i class="fas fa-crosshairs"></i>
							</button>
						</div>
					</div>
					<div id="map" style="height: 400px; border: 1px solid #ddd; border-radius: 4px;"></div>
					<small class="text-muted">
						<i class="fas fa-info-circle me-1"></i>
						Klik pada peta untuk memilih lokasi, drag marker, atau klik tombol <i class="fas fa-crosshairs"></i> untuk gunakan lokasi Anda saat ini
					</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Radius <span class="text-danger">*</span></label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<div class="row">
						<div class="col-6">
							<input type="number" step="0.01" class="form-control" name="radius_nilai" value="<?=@$company->radius_nilai ?: '1.00'?>" required>
							<?php if (!empty($form_errors['radius_nilai'])) echo '<small class="text-danger">' . $form_errors['radius_nilai'] . '</small>'?>
						</div>
						<div class="col-6">
							<select class="form-control" name="radius_satuan">
								<option value="m" <?=@$company->radius_satuan == 'm' ? 'selected' : ''?>>Meter</option>
								<option value="km" <?=@$company->radius_satuan == 'km' || !@$company->radius_satuan ? 'selected' : ''?>>Kilometer</option>
							</select>
						</div>
					</div>
					<small class="text-muted">Jarak maksimal untuk presensi dari lokasi company</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Contact Person</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="text" class="form-control" name="contact_person" value="<?=@$company->contact_person?>">
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">No. Telp</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="text" class="form-control" name="no_telp" value="<?=@$company->no_telp?>">
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Email</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="email" class="form-control" name="email" value="<?=@$company->email?>">
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Status</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control" name="status">
						<option value="active" <?=@$company->status == 'active' || !@$company->status ? 'selected' : ''?>>Active</option>
						<option value="inactive" <?=@$company->status == 'inactive' ? 'selected' : ''?>>Inactive</option>
					</select>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Keterangan</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<textarea class="form-control border-2" name="keterangan" rows="3" placeholder="Masukkan keterangan tambahan (opsional)"><?=@$company->keterangan?></textarea>
				</div>
			</div>
			
			<!-- Setting Presensi Section -->
			<div class="row mb-3">
				<div class="col-12">
					<div class="bg-light p-3 ps-4 mb-3 rounded">
						<h5 class="m-0 fw-semibold text-dark">
							<i class="fas fa-cog me-2"></i>Setting Presensi
						</h5>
						<small class="text-muted">Konfigurasi pengaturan presensi untuk perusahaan ini</small>
					</div>
				</div>
			</div>
			
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Hari Kerja</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<?php
					$nama_hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
					$setting_hari_kerja = @$company_setting['hari_kerja'] ?: [1,2,3,4,5];
					foreach ($nama_hari as $key => $val) {
						$checked = in_array($key, $setting_hari_kerja) ? ' checked' : '';
						echo '<div class="form-check">
								<input class="form-check-input" name="hari_kerja[]" type="checkbox" value="' . $key . '" id="hari_' . $val . '" ' . $checked . '>
								<label class="form-check-label fw-medium" for="hari_' . $val . '">' . $val . '</label>
							</div>';
					}
					?>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Gunakan Foto Selfi</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control border-2" name="gunakan_foto_selfi" style="width:auto">
						<option value="Y" <?=@$company_setting['gunakan_foto_selfi'] == 'Y' ? 'selected' : ''?>>Ya</option>
						<option value="N" <?=@$company_setting['gunakan_foto_selfi'] == 'N' ? 'selected' : ''?>>Tidak</option>
					</select>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Gunakan Radius Lokasi</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control border-2" name="gunakan_radius_lokasi" id="gunakan-radius-lokasi" style="width:auto">
						<option value="Y" <?=@$company_setting['gunakan_radius_lokasi'] == 'Y' ? 'selected' : ''?>>Ya</option>
						<option value="N" <?=@$company_setting['gunakan_radius_lokasi'] == 'N' ? 'selected' : ''?>>Tidak</option>
					</select>
					<small class="text-muted fw-medium">Jika Ya, maka presensi harus dilakukan didalam radius yang telah ditetapkan</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Aktifkan Modul Patroli</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control border-2" name="is_patrol_mode" id="is-patrol-mode" style="width:auto">
						<option value="Y" <?=@$company_setting['is_patrol_mode'] == 'Y' ? 'selected' : ''?>>Ya</option>
						<option value="N" <?=@$company_setting['is_patrol_mode'] == 'N' || !isset($company_setting['is_patrol_mode']) ? 'selected' : ''?>>Tidak</option>
					</select>
					<small class="text-muted fw-medium">Jika Ya, maka pengguna akan diminta untuk scan titik patroli saat input aktivitas</small>
				</div>
			</div>

			<!-- Patrol Points Section -->
			<div class="row mb-3" id="patrol-section">
				<div class="col-12">
					<div class="bg-light p-3 ps-4 mb-3 rounded">
						<h5 class="m-0 fw-semibold text-dark">
							<i class="fas fa-map-marker-alt me-2"></i>Titik Patroli
						</h5>
						<small class="text-muted">Tentukan titik-titik patroli untuk perusahaan ini (opsional)</small>
					</div>
				</div>
			</div>

			<div class="row mb-3" id="patrol-card">
				<div class="col-12">
					<div class="card border-0 bg-light">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<h6 class="mb-0 fw-semibold text-dark">
									<i class="fas fa-route me-2"></i>Daftar Titik Patroli
								</h6>
								<div>
									<?php if (!empty($existing_patrols) && count($existing_patrols) > 0): ?>
									<button type="button" class="btn btn-primary btn-sm me-2" onclick="printAllBarcodes(<?=@$company->id_company?>)">
										<i class="fas fa-print me-1"></i>Print All QR Codes
									</button>
									<?php endif; ?>
									<button type="button" class="btn btn-success btn-sm" id="btn-add-patrol">
										<i class="fas fa-plus me-1"></i>Tambah Titik
									</button>
								</div>
							</div>
							
							<div id="patrol-container">
								<!-- Patrol points will be added here dynamically -->
							</div>
							
							<div id="no-patrol-message" class="text-center text-muted py-4" style="display: none;">
								<i class="fas fa-map-marker-alt fa-2x mb-2"></i>
								<p class="mb-0">Belum ada titik patroli. Klik "Tambah Titik" untuk menambahkan.</p>
							</div>
						</div>
					</div>
				</div>
			</div>	
			<div class="row mb-3">
				<div class="col-sm-8 col-md-6 col-lg-5 offset-sm-3 offset-md-2 offset-lg-3 offset-xl-2">
					<button type="submit" name="submit" value="true" class="btn btn-primary">Simpan</button>
					<a href="<?=$module_url?>" class="btn btn-secondary">Batal</a>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
// Pass existing patrol data to JavaScript
window.existingPatrols = <?=json_encode($existing_patrols ?? [])?>;
</script>
