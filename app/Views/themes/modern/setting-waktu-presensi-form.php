<?php
helper('html');
?>
<div class="card">
	<div class="card-header">
		<h5 class="card-title">Setting Waktu Presensi</h5>
	</div>
	<div class="card-body">
		<?php
		echo btn_link([
			'attr' => ['class' => 'btn btn-light btn-xs'],
			'url' => $config->baseURL . 'setting-waktu-presensi',
			'icon' => 'fa fa-arrow-circle-left',
			'label' => 'Daftar Setting'
		]);
		echo '<hr/>';
		
		if (!empty($message)) {
			show_message($message);
		}
		?>
		<form method="post" action="" style="max-width: 750px" class="form-horizontal p-3" enctype="multipart/form-data">
			<div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label fw-semibold text-dark">Nama Setting <span class="text-primary fw-bold">*</span></label>
					<div class="col-sm-9">
						<input type="text" name="nama_setting" class="form-control border-2" required="required" value="<?=@$setting_presensi['nama_setting']?>" placeholder="Masukkan nama setting waktu presensi"/>
					</div>
				</div>
				
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label fw-semibold text-dark">Pilih Perusahaan <span class="text-primary fw-bold">*</span></label>
					<div class="col-sm-9">
						<select class="form-control select2 border-2" name="id_company" id="id_company" required>
							<option value="">-- Pilih Perusahaan --</option>
							<?php if (!empty($companies)): ?>
								<?php foreach ($companies as $company): ?>
								<option value="<?=$company->id_company?>" <?=@$setting_presensi['id_company'] == $company->id_company ? 'selected' : ''?>>
									<?=$company->nama_company?>
								</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<small class="text-muted fw-medium">Pilih perusahaan untuk setting waktu presensi ini</small>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label fw-semibold text-dark">Periode Waktu Masuk</label>
					<div class="col-sm-9">
						<?php
						$waktu = [];
						for ($i = 0; $i < 60; $i++) {
							$num = substr('0' . $i, -2);
							$waktu[$num] = $num;
						}
						
						if (!key_exists('waktu_masuk_awal', $setting_presensi)) {
							$setting_presensi['waktu_masuk_awal'] = '06:00:00';
							$setting_presensi['waktu_masuk_akhir'] = '11:59:59';
						}
						
						$waktu_masuk_awal = explode(':', $setting_presensi['waktu_masuk_awal']);
						$waktu_masuk_akhir = explode(':', $setting_presensi['waktu_masuk_akhir']);

						echo '<div class="input-group" style="width:440px">'
						 . options(['name' => 'waktu_masuk_awal_jam', 'class' => 'select2'], ['06' => '06', '07' => '07', '08' => '08'], $waktu_masuk_awal[0])
						 . options(['name' => 'waktu_masuk_awal_menit', 'class' => 'select2'], $waktu, $waktu_masuk_awal[1])
						 . options(['name' => 'waktu_masuk_awal_detik', 'class' => 'select2'], $waktu, $waktu_masuk_awal[2])
						 . '<span class="input-group-text">s.d.</span>'
						 . options(['name' => 'waktu_masuk_akhir_jam', 'class' => 'select2'], ['09' => '09', '10' => '10', '11' => '11'], $waktu_masuk_akhir[0])
						 . options(['name' => 'waktu_masuk_akhir_menit', 'class' => 'select2'], $waktu, $waktu_masuk_akhir[1])
						 . options(['name' => 'waktu_masuk_akhir_detik', 'class' => 'select2'], $waktu, $waktu_masuk_akhir[2])
						 . '</div>';
						?>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Periode Waktu Pulang</label>
					<div class="col-sm-9">
						<?php
						$jam = [];
						for ($i = 12; $i <= 23; $i++) {
							$jam[$i] = substr('0' . $i, -2);
						}
						
						if (!key_exists('waktu_pulang_awal', $setting_presensi)) {
							$setting_presensi['waktu_pulang_awal'] = '12:00:00';
							$setting_presensi['waktu_pulang_akhir'] = '23:59:59';
						}
						
						$waktu_pulang_awal = explode(':', $setting_presensi['waktu_pulang_awal']);
						$waktu_pulang_akhir = explode(':', $setting_presensi['waktu_pulang_akhir']);
						
						echo '<div class="input-group" style="width:440px">'
						 . options(['name' => 'waktu_pulang_awal_jam', 'class' => 'select2'], $jam, $waktu_pulang_awal[0])
						 . options(['name' => 'waktu_pulang_awal_menit', 'class' => 'select2'], $waktu, $waktu_pulang_awal[1])
						 . options(['name' => 'waktu_pulang_awal_detik', 'class' => 'select2'], $waktu, $waktu_pulang_awal[2])
						 . '<span class="input-group-text">s.d.</span>'
						 . options(['name' => 'waktu_pulang_akhir_jam', 'class' => 'select2'], $jam, $waktu_pulang_akhir[0])
						 . options(['name' => 'waktu_pulang_akhir_menit', 'class' => 'select2'], $waktu, $waktu_pulang_akhir[1])
						 . options(['name' => 'waktu_pulang_akhir_detik', 'class' => 'select2'], $waktu, $waktu_pulang_akhir[2])
						 . '</div>';
						?>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Batas Waktu Masuk</label>
					<div class="col-sm-9">
						<?php
						$waktu = [];
						for ($i = 0; $i < 60; $i++) {
							$num = substr('0' . $i, -2);
							$waktu[$num] = $num;
						}
						
						if (!key_exists('batas_waktu_masuk', $setting_presensi)) {
							$setting_presensi['batas_waktu_masuk'] = '07:30:00';
						}
						
						$batas_waktu_masuk = explode(':', $setting_presensi['batas_waktu_masuk']);

						echo '<div class="input-group" style="width:200px">'
						 . options(['name' => 'batas_waktu_masuk_jam', 'class' => 'select2'], ['06' => '06', '07' => '07', '08' => '08'], $batas_waktu_masuk[0])
						 . options(['name' => 'batas_waktu_masuk_menit', 'class' => 'select2'], $waktu, $batas_waktu_masuk[1])
						 . options(['name' => 'batas_waktu_masuk_detik', 'class' => 'select2'], $waktu, $batas_waktu_masuk[2])
						 . '</div>';
						?>
						<small>Jika presensi melewati batas waktu masuk maka akan dihitung terlambat masuk</small>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Batas Waktu Pulang</label>
					<div class="col-sm-9">
						<?php
						$waktu = [];
						for ($i = 0; $i < 60; $i++) {
							$num = substr('0' . $i, -2);
							$waktu[$num] = $num;
						}
						
						if (!key_exists('batas_waktu_pulang', $setting_presensi)) {
							$setting_presensi['batas_waktu_pulang'] = '17:00:00';
						}
						
						$batas_waktu_pulang = explode(':', $setting_presensi['batas_waktu_pulang']);

						echo '<div class="input-group" style="width:200px">'
						 . options(['name' => 'batas_waktu_pulang_jam', 'class' => 'select2'], ['15' => '15', '16' => '16', '17' => '17'], $batas_waktu_pulang[0])
						 . options(['name' => 'batas_waktu_pulang_menit', 'class' => 'select2'], $waktu, $batas_waktu_pulang[1])
						 . options(['name' => 'batas_waktu_pulang_detik', 'class' => 'select2'], $waktu, $batas_waktu_pulang[2])
						 . '</div>';
						?>
						<small>Jika presensi dilakukan sebelum batas waktu pulang maka akan dihitung pulang sebelum waktunya</small>
					</div>
				</div>
				<button type="submit" class="btn btn-primary btn-lg px-4 offset-sm-3" name="submit" value="submit">
					<i class="fas fa-save me-2"></i>Simpan Setting
				</button>
				<input type="hidden" name="id" value="<?=@$id?>"/>
			</div>
		</form>
	</div>
</div>