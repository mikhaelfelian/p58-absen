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
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Keterangan</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<textarea class="form-control" name="keterangan" rows="3"><?=@$company->keterangan?></textarea>
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
