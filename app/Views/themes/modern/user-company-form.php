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
					'label' => 'Assign Company'
				]);
			}
			
			echo btn_link(['attr' => ['class' => 'btn btn-light btn-xs'],
				'url' => $module_url,
				'icon' => 'fa fa-arrow-circle-left',
				'label' => 'Daftar Assignment'
			]);
			
			echo '<hr/>';

			if (!empty($message)) {
				show_message($message);
			}
		?>
		<form method="post" action="" class="form-user-company">
			<input type="hidden" name="id" value="<?=@$assignment->id_user_company?>">
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Pilih Pegawai <span class="text-danger">*</span></label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control select2" name="id_user" id="id_user" required <?=@$assignment ? 'disabled' : ''?>>
						<option value="">-- Pilih Pegawai --</option>
						<?php foreach ($users as $user): ?>
						<option value="<?=$user->id_user?>" <?=@$assignment && $assignment->id_user == $user->id_user ? 'selected' : ''?>>
							<?=$user->nama?> (<?=$user->nip?>)
						</option>
						<?php endforeach; ?>
					</select>
					<?php if (@$assignment): ?>
					<input type="hidden" name="id_user" value="<?=$assignment->id_user?>">
					<?php endif; ?>
					<?php if (!empty($form_errors['id_user'])) echo '<small class="text-danger">' . $form_errors['id_user'] . '</small>'?>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Pilih Company <span class="text-danger">*</span></label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control select2" name="id_company" id="id_company" required>
						<option value="">-- Pilih Company --</option>
						<?php foreach ($companies as $company): ?>
						<option value="<?=$company->id_company?>" <?=@$assignment && $assignment->id_company == $company->id_company ? 'selected' : ''?>>
							<?=$company->nama_company?>
						</option>
						<?php endforeach; ?>
					</select>
					<?php if (!empty($form_errors['id_company'])) echo '<small class="text-danger">' . $form_errors['id_company'] . '</small>'?>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Tanggal Mulai</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="text" class="form-control datepicker" name="tanggal_mulai" value="<?=@$assignment->tanggal_mulai ? date('d-m-Y', strtotime($assignment->tanggal_mulai)) : ''?>" placeholder="dd-mm-yyyy">
					<small class="text-muted">Kosongkan jika berlaku mulai sekarang</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Tanggal Selesai</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="text" class="form-control datepicker" name="tanggal_selesai" value="<?=@$assignment->tanggal_selesai ? date('d-m-Y', strtotime($assignment->tanggal_selesai)) : ''?>" placeholder="dd-mm-yyyy">
					<small class="text-muted">Kosongkan jika tidak ada batas waktu</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Status</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control" name="status">
						<option value="active" <?=@$assignment->status == 'active' || !@$assignment->status ? 'selected' : ''?>>Active</option>
						<option value="inactive" <?=@$assignment->status == 'inactive' ? 'selected' : ''?>>Inactive</option>
						<option value="completed" <?=@$assignment->status == 'completed' ? 'selected' : ''?>>Completed</option>
					</select>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label">Keterangan</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<textarea class="form-control" name="keterangan" rows="3"><?=@$assignment->keterangan?></textarea>
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
$(document).ready(function() {
	// Initialize Select2
	$('.select2').select2({
		theme: 'bootstrap-5',
		width: '100%'
	});
	
	// Initialize Flatpickr for date picker
	$('.datepicker').flatpickr({
		dateFormat: 'd-m-Y',
		locale: 'id'
	});
});
</script>

