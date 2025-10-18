<?= $this->extend('themes/modern/layout-mobile') ?>
<?= $this->section('content') ?>
<?php
$nama_bulan = nama_bulan();
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
?>
<div class="container mt-4">
	<div class="text-center text-light">
		<h5 class="m-0">Riwayat Activity</h5>
		<p class="p-0"><?=$user['nama']?></p>
	</div>
	
	<div class="bg-light p-4 mt-4 mb-5 rounded-3">
		<?php if (empty($activities)): ?>
		<div class="text-center py-4">
			<i class="fas fa-inbox fa-3x text-muted mb-3"></i>
			<p class="text-muted">Belum ada riwayat activity</p>
			<a href="<?=base_url()?>mobile-activity" class="btn btn-primary btn-sm">
				<i class="fas fa-plus me-2"></i>Tambah Activity
			</a>
		</div>
		<?php else: ?>
		
		<?php foreach ($activities as $activity): ?>
		<div class="card mb-3">
			<div class="card-body">
				<div class="d-flex justify-content-between align-items-start mb-2">
					<h6 class="mb-0"><?=$activity->judul_activity?></h6>
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
				
				<small class="text-muted">
					<i class="fas fa-building me-1"></i><?=$activity->nama_company?><br>
					<i class="fas fa-calendar me-1"></i><?=date('d-m-Y', strtotime($activity->tanggal))?> 
					<i class="fas fa-clock ms-2 me-1"></i><?=$activity->waktu?>
				</small>
				
				<p class="mt-2 mb-2"><?=nl2br($activity->deskripsi_activity)?></p>
				
				<?php if ($activity->foto_activity): ?>
				<img src="<?=$config->baseURL?>public/images/activity/<?=$activity->foto_activity?>" class="img-fluid rounded" style="max-height:200px">
				<?php endif; ?>
				
				<?php if ($activity->status == 'rejected' && $activity->rejection_reason): ?>
				<div class="alert alert-danger mt-2 mb-0">
					<small><strong>Alasan Reject:</strong><br><?=nl2br($activity->rejection_reason)?></small>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>
		
		<?php endif; ?>
	</div>
</div>

<?= $this->endSection() ?>

