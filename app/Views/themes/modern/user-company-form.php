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
				text: '<?= is_array($message['message']) ? implode(', ', $message['message']) : $message['message'] ?>',
				confirmButtonText: 'OK'
			});
			<?php elseif ($message['status'] == 'ok'): ?>
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
			<?php endif; ?>
			
			// Scroll to top to show the message
			$('html, body').animate({scrollTop: 0}, 500);
		});
		</script>
		<?php endif; ?>
		<?php echo form_open($module_url . '/store', ['class' => 'form-user-company']); ?>
			<?php echo form_hidden('id', @$assignment->id_user_company); ?>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Pilih Pegawai <span class="text-primary fw-bold">*</span></label>
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
		<?php echo form_hidden('id_user', $assignment->id_user); ?>
		<?php endif; ?>
					<?php if (!empty($form_errors['id_user'])) echo '<small class="text-danger fw-medium">' . $form_errors['id_user'] . '</small>'?>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Pilih Perusahaan <span class="text-primary fw-bold">*</span></label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control select2 border-2" name="id_company" id="id_company" required>
						<option value="">-- Pilih Perusahaan --</option>
						<?php foreach ($companies as $company): ?>
						<option value="<?=$company->id_company?>" <?=@$assignment && $assignment->id_company == $company->id_company ? 'selected' : ''?>>
							<?=$company->nama_company?>
						</option>
						<?php endforeach; ?>
					</select>
					<?php if (!empty($form_errors['id_company'])) echo '<small class="text-danger fw-medium">' . $form_errors['id_company'] . '</small>'?>
				</div>
			</div>
			
			<?php 
				$tanggal_mulai_value = '';
				$tanggal_selesai_value = '';
				if (!empty($assignment->tanggal_mulai)) {
					$tanggal_mulai_value = date('Y-m-d', strtotime($assignment->tanggal_mulai));
				}
				if (!empty($assignment->tanggal_selesai)) {
					$tanggal_selesai_value = date('Y-m-d', strtotime($assignment->tanggal_selesai));
				}
			?>
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Tanggal Mulai</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="text" class="form-control border-2 datepicker" name="tanggal_mulai" value="<?=$tanggal_mulai_value?>" placeholder="yyyy-mm-dd">
					<small class="text-muted fw-medium">Kosongkan jika berlaku mulai sekarang</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Tanggal Selesai</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<input type="text" class="form-control border-2 datepicker" name="tanggal_selesai" value="<?=$tanggal_selesai_value?>" placeholder="yyyy-mm-dd">
					<small class="text-muted fw-medium">Kosongkan jika tidak ada batas waktu</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Wajib Patroli ?</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
				<?php 
					// Get current value - handle both string and integer
					$patrolValue = isset($assignment->isPatrolRequired) ? (string) $assignment->isPatrolRequired : '0';
				?>
				<select class="form-control border-2" name="isPatrolRequired">
					<option value="1" <?=$patrolValue === '1' || $patrolValue === 1 ? 'selected' : ''?>>Ya</option>
					<option value="0" <?=$patrolValue === '0' || $patrolValue === 0 || $patrolValue === '' ? 'selected' : ''?>>Tidak</option>
				</select>
				<small class="text-muted fw-medium">Jika Ya, maka user akan diminta untuk patrol saat input activity</small>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Keterangan</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<textarea class="form-control border-2" name="keterangan" rows="3" placeholder="Masukkan keterangan tambahan (opsional)"><?=@$assignment->keterangan?></textarea>
				</div>
			</div>
			
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 col-form-label fw-semibold text-dark">Status</label>
				<div class="col-sm-8 col-md-6 col-lg-5">
					<select class="form-control border-2" name="status">
						<option value="active" <?=@$assignment->status == 'active' || !@$assignment->status ? 'selected' : ''?>>Active</option>
						<option value="inactive" <?=@$assignment->status == 'inactive' ? 'selected' : ''?>>Inactive</option>
						<option value="completed" <?=@$assignment->status == 'completed' ? 'selected' : ''?>>Completed</option>
					</select>
				</div>
			</div>
			
			<div class="row mb-3">
				<div class="col-sm-8 col-md-6 col-lg-5 offset-sm-3 offset-md-2 offset-lg-3 offset-xl-2">
					<button type="submit" name="submit" value="true" class="btn btn-primary btn-sm">
						<i class="fas fa-save me-2"></i>Simpan
					</button>
					<a href="<?=$module_url?>" class="btn btn-outline-secondary btn-sm">
						<i class="fas fa-times me-2"></i>Batal
					</a>
				</div>
			</div>
		<?php echo form_close(); ?>
	</div>
</div>


