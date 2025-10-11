<?php
helper('html');
?>
<div class="card">
	<div class="card-header">
		<h5 class="card-title">Setting Presensi</h5>
	</div>
	<div class="card-body">
		<?php
		if (!empty($message)) {
			show_message($message);
		}
	
		?>
		<form method="post" action="" style="max-width: 750px" class="form-horizontal p-3" enctype="multipart/form-data">
			<div>
				<div class="bg-lightgrey p-3 ps-4 mb-3">
					<h5 class="m-0">Presensi</h5>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Jumlah Riwayat Presensi</label>
					<div class="col-sm-9">
						<?=options(['name' => 'jml_riwayat_presensi_home', 'style' => 'width:auto'], ['3' => '3', '4' => '4', '5' => '5'], $setting_presensi['jml_riwayat_presensi_home'])?>
						<small>Jumlah riwayat presensi yang ditampilkan di halaman depan presensi mobile</small>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Hari Kerja</label>
					<div class="col-sm-9">
						<?php
						$nama_hari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
						$setting_hari_kerja = json_decode($setting_presensi['hari_kerja'], true);
						foreach ($nama_hari as $key => $val) {
							$checked = in_array($key, $setting_hari_kerja) ? ' checked' : '';
							echo '<div class="form-check">
									<input class="form-check-input" name="hari_kerja[]" type="checkbox" value="' . $key . '" id="' . $val . '" ' . $checked . '>
									<label class="form-check-label" for="' . $val . '">' . $val . '</label>
								</div>';
						}
						?>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Gunakan Foto Selfi</label>
					<div class="col-sm-9">
						<?=options(['name' => 'gunakan_foto_selfi', 'style' => 'width:auto'], ['Y' => 'Ya', 'N' => 'Tidak'], $setting_presensi['gunakan_foto_selfi'])?>
					</div>
				</div>
				<!--<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Gunakan Face Recognition</label>
					<div class="col-sm-9">
						<?php //echo options(['name' => 'gunakan_face_recognition', 'style' => 'width:auto'], ['Y' => 'Ya', 'N' => 'Tidak'], $setting_presensi['gunakan_face_recognition'])?>
					</div>
				</div>-->
				<div class="bg-lightgrey p-3 ps-4 mb-3">
					<h5 class="m-0">Radius Lokasi</h5>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Gunakan Radius Lokasi</label>
					<div class="col-sm-9">
						<?=options(['name' => 'gunakan_radius_lokasi', 'style' => 'width:auto', 'id' => 'gunakan-radius-lokasi'], ['Y' => 'Ya', 'N' => 'Tidak'], $setting_presensi['gunakan_radius_lokasi'])?>
						<small>Jika Ya, maka presensi harus dilakukan didalam radius yang telah ditetapkan</small>
					</div>
				</div>
				<?php
				$display = $setting_presensi['gunakan_radius_lokasi'] == 'N' ? ' style="display:none"' : '';
				?>
				<div id="row-radius-lokasi" <?=$display?>>
					<div class="row mb-3">
						<label class="col-sm-3 col-form-label"></label>
						<div class="col-sm-9">
							<div id="map" style="height:250px"></div>
							<div class="input-group mt-2">
								<span class="input-group-text">Latitude</span>
								<input class="form-control text-end" type="text" id="latitude" name="latitude" value="<?=$setting_presensi['latitude']?>">
								<span class="input-group-text">Longitude</span>
								<input class="form-control text-end" type="text" id="longitude" name="longitude" value="<?=$setting_presensi['longitude']?>">
							</div>
						</div>
					</div>
					<div class="row mb-3">
						<label class="col-sm-3 col-form-label">Radius</label>
						<div class="col-sm-9">
							<div class="input-group mt-2" style="width:200px">
								<input class="form-control text-end" type="text" id="radius-nilai" name="radius_nilai" value="<?=$setting_presensi['radius_nilai']?>" style="width:75px">
								<?=options(['name' => 'radius_satuan', 'style' => 'width:auto', 'id' => 'radius-satuan'], ['m' => 'Meter', 'km' => 'Kilometer'], $setting_presensi['radius_satuan'])?>
							</div>
							<small>Jarak maksimal lokasi presensi dari koordinat yang telah ditentukan diatas</small>
						</div>
					</div>
				</div>
				<input type="submit" class="btn btn-primary" name="submit" value="Submit"/>
				<span style="display:none" id="setting-presensi"><?=json_encode($setting_presensi)?></span>
			</div>
		</form>
	</div>
</div>