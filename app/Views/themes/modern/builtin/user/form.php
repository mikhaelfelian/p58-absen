<?php
$is_mobile = false;
if (@$_GET['mobile'] == 'true') {
	$is_mobile = true;
	echo $this->extend('themes/modern/layout-mobile');
	echo $this->section('content');
}
?>
<div class="<?=$is_mobile ? 'container mt-4' : 'card'?>">
	<div class="<?=$is_mobile ? 'text-light' : 'card-header'?>">
		<?php 
		if ($is_mobile) {
			echo '<p class="mb-2">EDIT PROFIL USER</p>';
		} else {
			echo '<h5 class="card-title">' . $title . '</h5>';
		}
		?>
	</div>
	<div class="<?=$is_mobile ? 'rounded-mobile p-4 bg-light' : 'card-body'?>">
		<?php 
			helper('html');
			helper('builtin/util');
			if (empty($_GET['mobile'])) {
				if (in_array('create', $user_permission)) {
					echo btn_link(['attr' => ['class' => 'btn btn-success btn-xs'],
						'url' => $module_url . '/add',
						'icon' => 'fa fa-plus',
						'label' => 'Tambah User'
					]);
				}
				
				echo btn_link(['attr' => ['class' => 'btn btn-light btn-xs'],
					'url' => $module_url,
					'icon' => 'fa fa-arrow-circle-left',
					'label' => 'Daftar User'
				]);
				
				echo '<hr/>';
			}

			if (!empty($message)) {
				show_message($message);
			}
			
			if (@$user_data['tgl_lahir']) {
				$exp = explode('-', $user_data['tgl_lahir']);
				$tgl_lahir = $exp[2] . '-' . $exp[1] . '-' . $exp[0];
			} else {
				$tgl_lahir = date('d-m-Y');
			}
		?>
		<form method="post" action="" class="form-user" enctype="multipart/form-data">
			<div class="tab-content">
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Foto</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<?php 
						$avatar = @$_FILES['avatar']['name'] ?: @$user_data['avatar'];
						if (!empty($avatar) ) {
							echo '<div class="img-choose" style="margin:inherit;margin-bottom:10px">
									<div class="img-choose-container">
										<img src="'.$config->baseURL. '/public/images/user/' . $avatar . '?r=' . time() . '"/>
										<a href="javascript:void(0)" class="remove-img"><i class="fas fa-times"></i></a>
									</div>
								</div>
								';
						}
						?>
						<input type="hidden" class="avatar-delete-img" name="avatar_delete_img" value="0">
						<input type="file" class="file form-control" name="avatar">
							<?php if (!empty($form_errors['avatar'])) echo '<small style="display:block" class="alert alert-danger mb-0">' . $form_errors['avatar'] . '</small>'?>
						<small class="small" style="display:block">Maksimal 300Kb, Minimal 100px x 100px, Tipe file: .JPG, .JPEG, .PNG</small>
						<div class="upload-img-thumb mb-2"><span class="img-prop"></span></div>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Username</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<?php 
						$readonly = 'readonly="readonly" class="disabled"';
						if (@$user_permission['update_all']) {
							$readonly = '';
						}
						?>
						<input class="form-control" type="text" name="username" <?=$readonly?> value="<?=set_value('username', @$user_data['username'])?>" placeholder="" required="required"/>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Nama</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<input class="form-control" type="text" name="nama" value="<?=set_value('nama', @$user_data['nama'])?>" placeholder="" required="required"/>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Jenis Kelamin</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<?=options(['name' => 'jenis_kelamin'], ['L' => 'Laki-Laki', 'P' => 'Perempuan'], set_value('jenis_kelamin', @$user_data['jenis_kelamin']))?>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">NIP</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<input class="form-control" type="text" name="nip" value="<?=set_value('nip', @$user_data['nip'])?>" placeholder=""/>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">NIK</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<input class="form-control" type="text" name="nik" value="<?=set_value('nik', @$user_data['nik'])?>"/>
					</div>
				</div>
				<?php
				if (@$user_permission['update_all']) {
				?>
					<div class="row mb-3">
						<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Jabatan</label>
						<div class="col-sm-8 col-md-6 col-lg-5">
							<?=options(['name' => 'id_jabatan[]', 'class' => 'select2', 'multiple' => 'multiple'], $jabatan, @$user_data['id_jabatan'])?>
						</div>
					</div>
				<?php
				}
				?>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">No. HP</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<input class="form-control" type="text" name="no_hp" value="<?=set_value('no_hp', @$user_data['no_hp'])?>" placeholder=""/>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Email</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<input class="form-control" type="text" name="email" value="<?=set_value('email', @$user_data['email'])?>" placeholder="" required="required"/>
						<input type="hidden" name="email_lama" value="<?=set_value('email', @$user_data['email'])?>" />
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Tempat Lahir</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<input class="form-control" type="text" name="tempat_lahir" value="<?=set_value('tempat_lahir', @$user_data['tempat_lahir'])?>" placeholder="" required="required"/>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Tgl. Lahir</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<input class="form-control flatpickr" type="text" name="tgl_lahir" value="<?=set_value('tgl_lahir', @$tgl_lahir)?>" required="required"/>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Alamat</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<textarea class="form-control" name="alamat"><?=set_value('alamat', @$user_data['alamat'])?></textarea>
					</div>
				</div>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Propinsi</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<?=options(['name' => 'id_wilayah_propinsi', 'class' => 'propinsi select2'], $propinsi, set_value('id_wilayah_propinsi', $id_wilayah_propinsi) )?>
					</div>
				</div>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Kabupaten</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<?=options(['name' => 'id_wilayah_kabupaten', 'class' => 'kabupaten select2'], $kabupaten, set_value('id_wilayah_kabupaten', $id_wilayah_kabupaten))?>
					</div>
				</div>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Kecamatan</label>
					<div class="col-sm-8 col-md-6 col-lg-5">
						<?=options(['name' => 'id_wilayah_kecamatan', 'class' => 'kecamatan select2'], $kecamatan, set_value('id_wilayah_kecamatan',$id_wilayah_kecamatan))?>
					</div>
				</div>
				<div class="form-group row mb-3">
					<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Kelurahan</label>
					<div class="col-sm-8 col-md-6 col-lg-5" style="position:relative">
						<?=options(['name' => 'id_wilayah_kelurahan', 'class' => 'kelurahan select2'], $kelurahan, set_value('id_wilayah_kelurahan', $id_wilayah_kelurahan))?>
					</div>
				</div>
				<?php
				if (@$user_permission['update_all']) {
					?>
					<div class="row mb-3">
						<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Verified</label>
						<div class="col-sm-8 col-md-6 col-lg-5">
							<?php
							if (!isset($user_data['verified']) && !key_exists('verified', $_POST) ) {
								$selected = 1;
							} else {
								$selected = set_value('verified', @$user_data['verified']);
							}
							?>
							<?php echo options(['name' => 'verified'], [1=>'Ya', 0 => 'Tidak'], $selected); ?>
						</div>
					</div>
					<div class="row mb-3">
						<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Status</label>
						<div class="col-sm-8 col-md-6 col-lg-5">
							<?php echo options(['name' => 'status'], ['active' => 'Aktif', 'suspended' => 'Suspended', 'deleted' => 'Deleted'], set_value('status', @$user_data['status'])); ?>
						</div>
					</div>
					<div class="row mb-3">
						<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Role</label>
						<div class="col-sm-8 col-md-6 col-lg-5">
							<?php
							foreach ($roles as $key => $val) {
								$options[$val['id_role']] = $val['judul_role'];
							}
							
							if (!empty($user_data['role'])) {
								foreach ($user_data['role'] as $val) {
									$id_role_selected[] = $val['id_role'];
								}
							}
							
							echo options(['name' => 'id_role[]', 'multiple' => 'multiple', 'class' => 'select2'], $options, set_value('id_role', @$id_role_selected));
							?>
						</div>
					</div>
					<div class="row mb-3">
						<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Halaman Default</label>
						<div class="col-sm-8 col-md-6 col-lg-5">
							<?php
							if (empty(@$user_data['default_page_type'])) {
								$user_data['default_page_type'] = 'id_module';
								$user_data['id_module'] = 5;
							}
							$default_page_type = set_value('option_default_page', @$user_data['default_page_type']);
							?>
							<?=options(['name' => 'option_default_page', 'id' => 'option-default-page', 'class' => 'mb-2'], ['url' => 'URL', 'id_module' => 'Module', 'id_role' => 'Role'], $default_page_type )?>
							<?php
							$display_url = $default_page_type == 'url' ? '' : ' style="display:none"';
							$display_module = $default_page_type == 'id_module' ? '' : ' style="display:none"';
							$display_role = $default_page_type == 'id_role' ? '' : ' style="display:none"';
							
							?>
							<div class="default-page-url default-page" <?=$display_url?>>
								<input type="text" class="form-control" name="default_page_url" value="<?=set_value('default_page_url', @$user_data['default_page_url'])?>"/>
								<small>Gunakan {{BASE_URL}} untuk menggunakan base url aplikasi, misal: {{BASE_URL}}builtin/user/edit?id=1</small>
							</div>
							<div class="default-page-id-module default-page" <?=$display_module?>>
								<?php
								foreach ($list_module as $val) {
									$options[$val['id_module']] = $val['nama_module'] . ' - ' . $val['judul_module'];
								}
								
								if (!@$user_data['default_page_id_module']) {
									$user_data['default_page_id_module'] = 5;
								}
								
								echo options(['name' => 'default_page_id_module'], $options, set_value('default_page_id_module', @$user_data['default_page_id_module'])); 
								?>
								<span class="text-muted">Pastikan user memiliki hak akses ke module</span>
							</div>
							<?php
							$default_page_role = [];
							if (!empty($user_data['role'])) {
								foreach ($user_data['role'] as $val) {
									$default_page_role[$val['id_role']] = $val['judul_role'];
								}
							}
							if (!$default_page_role) {
								$default_page_role = ['' => '-- Pilih Role --'];
							}
							?>
							<div class="default-page-id-role default-page" <?=$display_role?>>
								<?=options(['name' => 'default_page_id_role'], $default_page_role, set_value('default_page_id_role', @$user_data['default_page_id_role']));?>
								<small>Halaman default sama dengan halaman default <a title="Halaman Role" href="<?=base_url() . '/builtin/role'?>" target="blank">role</a></small>
							</div>
						</div>
					</div>
					<?php
					if (!empty($_GET['id'])) { ?>
						<div class="row mb-3">
							<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Ubah Password</label>
							<div class="col-sm-8 col-md-6 col-lg-5">
								<?= options(['name' => 'option_ubah_password', 'id' => 'option-ubah-password'], ['N' => 'Tidak', 'Y' => 'Ya'], set_value('option_ubah_password', '')) ?>
							</div>
						</div>
					<?php
					}
					$display = (!empty($_POST['option_ubah_password']) && $_POST['option_ubah_password'] == 'Y') || empty($_GET['id']) ? '' : ' style="display:none"';
					?>
					
					<div id="password-container" <?=$display?>>
						<div class="row mb-3">
							<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Password Baru</label>
							<div class="col-sm-8 col-md-6 col-lg-5">
								<input class="form-control" type="password" name="password" value="<?=set_value('password', '')?>"/>
							</div>
						</div>
						<div class="row mb-3">
							<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Ulangi Password Baru</label>
							<div class="col-sm-8 col-md-6 col-lg-5">
								<input class="form-control" type="password" name="ulangi_password" value="<?=set_value('ulangi_password', '')?>"/>
							</div>
						</div>
					</div>
				<?php
				}
				?>
				<div class="row">
					<div class="col-sm-8">
						<button id="btn-submit-edit-profile" type="submit" name="submit" value="submit" class="btn btn-primary submit-data">Submit</button>
						<button type="button" class="btn btn-danger clear-form">Clear Form</button>
						<input type="hidden" name="id" value="<?=@$user_data['id_user']?>"/>
						<input type="hidden" name="mobile" value="<?=@$_GET['mobile']?>"/>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<?php
if (@$_GET['mobile'] == 'true') {
	echo $this->endSection();
}
?>