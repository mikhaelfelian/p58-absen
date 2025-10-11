<div class="card">
	<div class="card-header">
		<h5 class="card-title"><?=$title?></h5>
	</div>
	
	<div class="card-body">
		<?php 
			helper ('html');
			echo btn_link(['attr' => ['class' => 'btn btn-light btn-xs me-2'],
				'url' => $config->baseURL . 'presensi-detail',
				'icon' => 'fa fa-arrow-circle-left',
				'label' => 'Presensi'
			]);
			
			echo btn_link(['attr' => ['class' => 'btn btn-success btn-xs'],
				'url' => $config->baseURL . 'presensi-detail/add',
				'icon' => 'fa fa-plus',
				'label' => 'Tambah Data'
			]);
		?>
		<hr/>
		<?php
		
		if (!empty($message)) {
			show_message($message);
		}
		
		?>
		<form method="post" action="" class="form-horizontal" enctype="multipart/form-data">
			<div>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Pegawai</label>
					<div class="col-sm-6">
						<?=options(['name' => 'id_user'], $user, @$presensi['id_user'])?>
					</div>
				</div>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Jenis Presensi</label>
					<div class="col-sm-6">
						<?=options(['name' => 'jenis_presensi'], ['masuk' => 'Masuk', 'pulang' => 'Pulang'], @$presensi['jenis_presensi'])?>
					</div>
				</div>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Tanggal</label>
					<div class="col-sm-6">
						<?php
						$tanggal = !empty($presensi['tanggal']) ? $presensi['tanggal'] : date('Y-m-d');
						?>
						<input class="form-control flatpickr" type="text" name="tanggal" value="<?=set_value('tanggal', format_tanggal($tanggal, 'dd-mm-yyyy'))?>" required="required"/>
					</div>
				</div>
				<?php
				$waktu = [];
				for ($i = 0; $i < 60; $i++) {
					$num = substr('0' . $i, -2);
					$waktu[$num] = $num;
				}
				
				$jam = [];
				for ($i = 1; $i < 23; $i++) {
					$num = substr('0' . $i, -2);
					$jam[$num] = $num;
				}
				?>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Waktu</label>
					<div class="col-sm-6">
						<?php
						$jam_selected = '06';
						$menit_selected = '30';
						$detik_selected = '22';
						if (!empty($_POST['waktu_jam'])) {
							$jam_selected = $_POST['waktu_jam'];
							$menit_selected = $_POST['waktu_menit'];
							$detik_selected = $_POST['waktu_detik'];
						} else {
							if (!empty($presensi['waktu'])) {
								$exp = explode(':', $presensi['waktu']);
								$jam_selected = $exp[0];
								$menit_selected = $exp[1];
								$detik_selected = $exp[2];
							}
						}
						echo '<div class="input-group" style="width:200px">' . options(['name' => 'waktu_jam', 'class' => 'select2'], $jam, $jam_selected)
						. options(['name' => 'waktu_menit', 'class' => 'select2'], $waktu, $menit_selected)
						. options(['name' => 'waktu_detik', 'class' => 'select2'], $waktu, $detik_selected) . '</div>';
						?>
					</div>
				</div>
				<?php
				$foto = @$presensi['foto'];
				// echo $foto; die;
				if ($setting_presensi['gunakan_foto_selfi'] == 'Y' || !empty($foto)) {
					
					echo 
					'<div class="form-group row mb-3">
						<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Foto</label>
						<div class="col-sm-6">';
							
							if (!empty($foto) ) 
							{
								$note = '';
								if (file_exists(ROOTPATH . 'public/images/presensi/' . $foto)) {
									$image = $config->baseURL . 'public/images/presensi/' . $foto;
								} else {
									$image = $config->baseURL . 'public/images/foto/noimage.png';
									$note = '<small><b>Note</strong>: File <strong>public/images/presensi/' . $foto . '</strong> tidak ditemukan</small>';
								}
								echo '<div class="img-choose mt-2" style="margin:inherit;margin-bottom:10px">
										<div class="img-choose-container">
											<img style="width:320px" src="'. $image . '?r=' . time() . '"/>
											<a href="javascript:void(0)" class="remove-img"><i class="fas fa-times"></i></a>
										</div>
									</div>
									' . $note .'
									';
							}
							
							if ($setting_presensi['gunakan_foto_selfi'] == 'Y') {
								echo options(['name' => 'jenis_foto'], ['upload' => 'Upload', 'webcam' => 'Webcam'], $foto);							
								// File upload
								echo 
								'<div id="upload-image-container">
									<input type="hidden" class="foto-delete-img" name="foto_delete_img" value="0">
									<input type="hidden" class="foto-max-size" name="foto_max_size" value="300000"/>
									<input type="file" class="file form-control mt-2" name="foto">';
									if (!empty($form_errors['foto'])) { echo '<small class="alert alert-danger">' . $form_errors['foto'] . '</small>'; }
									echo '<small class="small" style="display:block">Maksimal 2Mb, Minimal 250px x 250px, Tipe file: .JPG, .JPEG, .PNG</small>
									<div class="upload-file-thumb"><span class="file-prop"></span></div>
								</div>';
									
								// Webcam
								echo 
								'<div id="webcam-container" class="mt-3" style="display:none;margin:0">
									<div id="webcam" style="width:100%;margin:auto;margin:0"></div> 
									<div id="photo-result" style="display:none"></div>
									<div id="photo-raw" class="photo-raw" style="display:none"></div>
									<textarea name="foto_raw" class="photo-raw" style="display:none"></textarea>
									<button type="button" class="btn btn-success mt-3" id="btn-ambil-photo" disabled>Ambil Foto</button>
									<button type="button" class="btn btn-warning mt-3" id="btn-ambil-ulang-photo" style="display:none">Ambil Ulang Foto</button>
								</div>';
							}
						echo '
						</div>
					</div>';
						
				}
				?>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Lokasi Presensi</label>
					<div class="col-sm-6">
						<div id="map" style="height:250px"></div>
						<div class="input-group mt-2">
							<span class="input-group-text">Latitude</span>
							<input class="form-control text-end" type="text" id="latitude" name="latitude" value="<?=@$presensi['latitude']?>">
							<span class="input-group-text">Longitude</span>
							<input class="form-control text-end" type="text" id="longitude" name="longitude" value="<?=@$presensi['longitude']?>">
						</div>
					</div>
				</div>
				<div class="form-group row mb-0">
					<div class="col-sm-6">
						<button type="submit" name="submit" value="submit" id="submit" class="btn btn-primary me-2">Simpan</button>
						<input type="hidden" name="id" value="<?=@$_GET['id']?>"/>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<span style="display:none" id="setting-presensi"><?=json_encode($setting_presensi)?></span>