<div class="card">
	<div class="card-header">
		<h5 class="card-title">Detail Activity</h5>
	</div>
	<div class="card-body">
		<?php 
			helper('html');
			
			echo btn_link(['attr' => ['class' => 'btn btn-light btn-xs'],
				'url' => $module_url,
				'icon' => 'fa fa-arrow-circle-left',
				'label' => 'Kembali'
			]);
			
			echo '<hr/>';
		?>
		
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Nama Pegawai</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<?=$activity->nama?> (<?=$activity->nip?>)
			</div>
		</div>
		
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Company</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<?=$activity->nama_company?>
			</div>
		</div>
		
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Tanggal & Waktu</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<?=date('d-m-Y', strtotime($activity->tanggal))?> <?=$activity->waktu?>
			</div>
		</div>
		
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Judul Activity</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<?=$activity->judul_activity?>
			</div>
		</div>
		
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Deskripsi</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<?=nl2br($activity->deskripsi_activity)?>
			</div>
		</div>
		
		<?php if ($activity->foto_activity): ?>
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Foto Activity</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<a href="<?=$config->baseURL?>public/images/activity/<?=$activity->foto_activity?>" class="glightbox">
					<img src="<?=$config->baseURL?>public/images/activity/<?=$activity->foto_activity?>" class="img-fluid rounded" style="max-width:400px">
				</a>
			</div>
		</div>
		<?php endif; ?>
		
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Status</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<?php
				if ($activity->status == 'approved') {
					echo '<span class="badge bg-success">Approved</span>';
				} elseif ($activity->status == 'rejected') {
					echo '<span class="badge bg-danger">Rejected</span>';
				} else {
					echo '<span class="badge bg-warning">Pending</span>';
				}
				?>
			</div>
		</div>
		
		<?php if ($activity->approved_by): ?>
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Approved By</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<?=$activity->approved_by_name?> <br>
				<small class="text-muted"><?=date('d-m-Y H:i:s', strtotime($activity->approved_at))?></small>
			</div>
		</div>
		<?php endif; ?>
		
		<?php if ($activity->rejection_reason): ?>
		<div class="row mb-3">
			<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Alasan Reject</label>
			<div class="col-sm-8 col-md-6 col-lg-5">
				<div class="alert alert-danger">
					<?=nl2br($activity->rejection_reason)?>
				</div>
			</div>
		</div>
		<?php endif; ?>
		
		<?php if (has_permission('approve') && $activity->status == 'pending'): ?>
		<div class="row mb-3">
			<div class="col-sm-8 col-md-6 col-lg-5 offset-sm-3 offset-md-2 offset-lg-3 offset-xl-2">
				<button type="button" class="btn btn-success btn-approve" data-id="<?=$activity->id_activity?>">
					<i class="fas fa-check me-2"></i>Approve
				</button>
				<button type="button" class="btn btn-danger btn-reject" data-id="<?=$activity->id_activity?>">
					<i class="fas fa-times me-2"></i>Reject
				</button>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<script>
$(document).ready(function() {
	$('.btn-approve').on('click', function() {
		var id = $(this).data('id');
		Swal.fire({
			title: 'Konfirmasi',
			text: 'Approve activity ini?',
			icon: 'question',
			showCancelButton: true,
			confirmButtonColor: '#28a745',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Ya, Approve!',
			cancelButtonText: 'Batal'
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: base_url + module_url + '/ajaxApprove',
					type: 'POST',
					data: {id: id},
					dataType: 'json',
					success: function(response) {
						if (response.status == 'ok') {
							Swal.fire('Berhasil!', response.message, 'success').then(() => {
								location.reload();
							});
						} else {
							Swal.fire('Error!', response.message, 'error');
						}
					}
				});
			}
		});
	});
	
	$('.btn-reject').on('click', function() {
		var id = $(this).data('id');
		Swal.fire({
			title: 'Reject Activity',
			input: 'textarea',
			inputLabel: 'Alasan Reject',
			inputPlaceholder: 'Masukkan alasan reject...',
			inputAttributes: {
				'aria-label': 'Masukkan alasan reject'
			},
			showCancelButton: true,
			confirmButtonColor: '#dc3545',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Reject',
			cancelButtonText: 'Batal',
			inputValidator: (value) => {
				if (!value) {
					return 'Alasan reject harus diisi!'
				}
			}
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: base_url + module_url + '/ajaxReject',
					type: 'POST',
					data: {id: id, reason: result.value},
					dataType: 'json',
					success: function(response) {
						if (response.status == 'ok') {
							Swal.fire('Berhasil!', response.message, 'success').then(() => {
								location.reload();
							});
						} else {
							Swal.fire('Error!', response.message, 'error');
						}
					}
				});
			}
		});
	});
});
</script>

