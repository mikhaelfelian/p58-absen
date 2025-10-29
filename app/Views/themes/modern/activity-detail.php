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
	<?php
		// Parse foto_activity to check if it's JSON or single filename
		$fotos = json_decode($activity->foto_activity, true);
		$is_json = (json_last_error() === JSON_ERROR_NONE && is_array($fotos));
		
		if (!$is_json) {
			// Single photo (legacy format)
			$fotos = [['file_name' => $activity->foto_activity]];
		}
		
		// Collect photos with GPS location
		$photos_with_gps = [];
		foreach ($fotos as $index => $foto) {
			if (isset($foto['lat']) && isset($foto['lon'])) {
				$photos_with_gps[] = [
					'index' => $index + 1,
					'lat' => $foto['lat'],
					'lon' => $foto['lon'],
					'file_name' => $foto['file_name'],
					'url' => $config->baseURL . 'public/images/activity/' . $foto['file_name']
				];
			}
		}
	?>
	<div class="row mb-3">
		<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Foto Activity</label>
		<div class="col-sm-8 col-md-6 col-lg-5">
			<div class="row g-2">
				<?php foreach ($fotos as $index => $foto): ?>
					<?php
						$photo_url = $config->baseURL . 'public/images/activity/' . $foto['file_name'];
						$has_location = isset($foto['lat']) && isset($foto['lon']);
					?>
					<div class="col-auto mb-2">
						<div class="position-relative">
							<a href="<?=$photo_url?>" class="glightbox" data-glightbox='title: Foto Activity <?=$index + 1?>'>
								<img src="<?=$photo_url?>" 
									 class="rounded" 
									 style="width:256px; height:256px; object-fit:cover; border:2px solid #dee2e6;"
									 onerror="this.src='<?=$config->baseURL?>public/images/no-image.jpg'; this.style.width='256px'; this.style.height='256px'; this.style.objectFit='cover';">
							</a>
							<?php if ($has_location): ?>
								<small class="position-absolute bottom-0 start-0 bg-dark text-white px-2 py-1 rounded-top-end" style="font-size:0.7rem; opacity:0.9;">
									<i class="fas fa-map-marker-alt me-1"></i>GPS
								</small>
							<?php endif; ?>
						</div>
						<?php if ($has_location): ?>
							<div class="text-center mt-1">
								<small class="text-muted d-block">Lat: <?=number_format($foto['lat'], 6)?></small>
								<small class="text-muted d-block">Lon: <?=number_format($foto['lon'], 6)?></small>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	
	<?php if (!empty($photos_with_gps)): ?>
	<!-- Leaflet CSS -->
	<link rel="stylesheet" href="<?=$config->baseURL?>public/vendors/leafletjs/leaflet.css" />
	
	<div class="row mb-3">
		<label class="col-sm-3 col-md-2 col-lg-3 col-xl-2 fw-bold">Peta Lokasi</label>
		<div class="col-sm-8 col-md-6 col-lg-5">
			<div id="activity-map" style="height: 400px; width: 100%; border-radius: 8px; border: 1px solid #dee2e6;"></div>
		</div>
	</div>
	
	<!-- Leaflet JS -->
	<script src="<?=$config->baseURL?>public/vendors/leafletjs/leaflet.js"></script>
	<script>
		// Initialize map
		<?php if (count($photos_with_gps) > 0): ?>
			// Get center of all markers
			let center_lat = <?=$photos_with_gps[0]['lat']?>;
			let center_lon = <?=$photos_with_gps[0]['lon']?>;
			
			// Create map
			const map = L.map('activity-map').setView([center_lat, center_lon], 13);
			
			// Add OpenStreetMap tiles
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '© OpenStreetMap contributors',
				maxZoom: 19
			}).addTo(map);
			
			// Add markers for each photo
			<?php foreach ($photos_with_gps as $photo): ?>
				L.marker([<?=$photo['lat']?>, <?=$photo['lon']?>])
					.addTo(map)
					.bindPopup(`
						<div style="text-align:center;">
							<img src="<?=$photo['url']?>" style="max-width:200px; max-height:150px; border-radius:4px; margin-bottom:5px;">
							<br><strong>Foto <?=$photo['index']?></strong>
							<br><small>Lat: <?=number_format($photo['lat'], 6)?></small>
							<br><small>Lon: <?=number_format($photo['lon'], 6)?></small>
						</div>
					`);
			<?php endforeach; ?>
			
			// Fit map to show all markers
			<?php if (count($photos_with_gps) > 1): ?>
				let bounds = [
					[<?=min(array_column($photos_with_gps, 'lat'))?>, <?=min(array_column($photos_with_gps, 'lon'))?>],
					[<?=max(array_column($photos_with_gps, 'lat'))?>, <?=max(array_column($photos_with_gps, 'lon'))?>]
				];
				map.fitBounds(bounds, {padding: [20, 20]});
			<?php endif; ?>
		<?php endif; ?>
	</script>
	<?php endif; ?>
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


