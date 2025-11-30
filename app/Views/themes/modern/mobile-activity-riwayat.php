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
					<h6 class="mb-0"><?=htmlspecialchars(isset($activity->judul_activity) ? $activity->judul_activity : '')?></h6>
					<?php
					$status = isset($activity->status) ? $activity->status : 'pending';
					if ($status == 'approved') {
						echo '<span class="badge bg-success">Approved</span>';
					} elseif ($status == 'rejected') {
						echo '<span class="badge bg-danger">Rejected</span>';
					} else {
						echo '<span class="badge bg-warning">Pending</span>';
					}
					?>
				</div>
				
				<small class="text-muted">
					<i class="fas fa-building me-1"></i><?=htmlspecialchars(isset($activity->nama_company) ? $activity->nama_company : '')?><br>
					<i class="fas fa-calendar me-1"></i><?=date('d-m-Y', strtotime(isset($activity->tanggal) ? $activity->tanggal : 'now'))?> 
					<i class="fas fa-clock ms-2 me-1"></i><?=htmlspecialchars(isset($activity->waktu) ? $activity->waktu : '')?>
				</small>
				
				<p class="mt-2 mb-2"><?=nl2br(htmlspecialchars(isset($activity->deskripsi_activity) ? $activity->deskripsi_activity : ''))?></p>
				
				<?php 
				// Debug: Show what we have
				$foto_images = (isset($activity->foto_activity_images) && is_array($activity->foto_activity_images)) ? $activity->foto_activity_images : [];
				$foto_raw = isset($activity->foto_activity) ? $activity->foto_activity : null;
				?>
				
				<?php if (!empty($foto_images)): ?>
				<div class="mt-2">
					<?php foreach ($foto_images as $image_file): ?>
						<?php if (!empty($image_file)): ?>
						<?php 
						$image_url = $config->baseURL . 'public/images/activity/' . htmlspecialchars($image_file);
						$image_path = ROOTPATH . 'public/images/activity/' . $image_file;
						$file_exists = file_exists($image_path);
						?>
						<div class="mb-2">
							<img src="<?=$image_url?>" 
								 class="img-fluid rounded" 
								 style="max-height:200px; width:auto; display:block;"
								 alt="Activity Photo"
								 onerror="console.error('Image failed to load: <?=htmlspecialchars($image_file)?>'); this.style.display='none';">
							<?php if (!$file_exists): ?>
							<small class="text-danger d-block">File not found: <?=htmlspecialchars($image_file)?></small>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<?php elseif (!empty($foto_raw)): ?>
				<!-- Debug: Show raw foto_activity if images array is empty -->
				<div class="alert alert-warning mt-2 mb-0">
					<small>Debug: foto_activity exists but no images extracted. Raw value: <?=htmlspecialchars(substr($foto_raw, 0, 100))?></small>
				</div>
				<?php endif; ?>
				
				<?php if ((isset($activity->status) ? $activity->status : '') == 'rejected' && !empty($activity->rejection_reason)): ?>
				<div class="alert alert-danger mt-2 mb-0">
					<small><strong>Alasan Reject:</strong><br><?=nl2br(htmlspecialchars(isset($activity->rejection_reason) ? $activity->rejection_reason : ''))?></small>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>
		
		<?php endif; ?>
	</div>
</div>

<?= $this->endSection() ?>

