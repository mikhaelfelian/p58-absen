<?php
$start_date = $start_date ?? date('Y-m-01');
$end_date = $end_date ?? date('Y-m-d');
?>

<div class="card">
	<div class="card-header">
		<h5 class="card-title mb-0">
			<i class="fas fa-chart-bar me-2"></i><?= $title ?>
		</h5>
	</div>
	<div class="card-body">
		
		<!-- Filter Form -->
		<form method="get" action="<?= base_url() ?>activity-rekap" id="form-filter">
			<div class="row g-3 mb-4">
				
				<!-- Date Range -->
				<div class="col-md-4">
					<label class="form-label">Tanggal Mulai</label>
					<input type="date" class="form-control" name="start_date" id="start_date" 
						value="<?= $start_date ?>" required>
				</div>
				
				<div class="col-md-4">
					<label class="form-label">Tanggal Selesai</label>
					<input type="date" class="form-control" name="end_date" id="end_date" 
						value="<?= $end_date ?>" required>
				</div>
				
				<!-- User Filter -->
				<?php if (!has_permission('read_own')): ?>
				<div class="col-md-4">
					<label class="form-label">User</label>
					<select class="form-select select2" name="id_user" id="id_user">
						<?php foreach ($user as $id => $nama): ?>
							<option value="<?= $id ?>" <?= (@$_GET['id_user'] == $id) ? 'selected' : '' ?>>
								<?= $nama ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<?php endif; ?>
				
				<!-- Employee Filter -->
				<div class="col-md-4">
					<label class="form-label">Pegawai</label>
					<select class="form-select select2" name="id_user" id="id_user">
						<?php foreach ($user as $id => $nama): ?>
							<option value="<?= $id ?>" <?= (@$_GET['id_user'] == $id) ? 'selected' : '' ?>>
								<?= $nama ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<!-- Company Filter -->
				<div class="col-md-4">
					<label class="form-label">Company</label>
					<select class="form-select select2" name="id_company" id="id_company">
						<?php foreach ($company as $id => $nama): ?>
							<option value="<?= $id ?>" <?= (@$_GET['id_company'] == $id) ? 'selected' : '' ?>>
								<?= $nama ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<!-- Buttons -->
				<div class="col-md-12">
					<button type="submit" class="btn btn-primary">
						<i class="fas fa-search me-2"></i>Tampilkan
					</button>
					<button type="button" class="btn btn-success" id="btn-export-excel">
						<i class="fas fa-file-excel me-2"></i>Export Excel
					</button>
				</div>
			</div>
		</form>
		
		<!-- Results -->
		<?php if (!empty($activities)): ?>
		
		<!-- Map View -->
		<div class="card mb-4">
			<div class="card-header">
				<h6 class="mb-0">
					<i class="fas fa-map-marked-alt me-2"></i>Peta Lokasi Activity
				</h6>
			</div>
			<div class="card-body">
				<div id="activity-map" style="height: 400px; width: 100%;"></div>
				<div class="mt-2">
					<small class="text-muted">
						<i class="fas fa-info-circle me-1"></i>
						Menampilkan lokasi GPS dari setiap activity
					</small>
				</div>
			</div>
		</div>
		
		<!-- Table View -->
		<div class="table-responsive">
			<table class="table table-bordered table-striped table-hover">
				<thead class="table-light">
					<tr>
						<th width="50">No</th>
						<th width="100">Tanggal</th>
						<th width="100">Waktu</th>
						<th width="150">NIP</th>
						<th width="200">Nama</th>
						<th width="150">Company</th>
						<th width="250">Judul Activity</th>
						<th>Deskripsi</th>
						<th width="100">GPS</th>
						<th width="100">Status</th>
						<th width="100">Aksi</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$no = 1;
					foreach ($activities as $activity): 
						// Status badge
						$status_class = 'warning';
						$status_text = 'Pending';
						if ($activity->status == 'approved') {
							$status_class = 'success';
							$status_text = 'Approved';
						} elseif ($activity->status == 'rejected') {
							$status_class = 'danger';
							$status_text = 'Rejected';
						}
					?>
					<tr>
						<td class="text-center"><?= $no++ ?></td>
						<td><?= date('d/m/Y', strtotime($activity->tanggal)) ?></td>
						<td><?= $activity->waktu ?></td>
						<td><?= $activity->nip ?></td>
						<td><?= $activity->nama ?></td>
						<td><?= $activity->nama_company ?></td>
						<td><?= $activity->judul_activity ?></td>
						<td>
							<div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
								<?= substr($activity->deskripsi_activity, 0, 100) ?>
								<?= strlen($activity->deskripsi_activity) > 100 ? '...' : '' ?>
							</div>
						</td>
						<td class="text-center">
							<?php if ($activity->latitude && $activity->longitude): ?>
								<a href="https://www.google.com/maps?q=<?= $activity->latitude ?>,<?= $activity->longitude ?>" 
									target="_blank" class="btn btn-sm btn-info" title="Lihat di Maps">
									<i class="fas fa-map-marker-alt"></i>
								</a>
							<?php else: ?>
								<span class="text-muted">-</span>
							<?php endif; ?>
						</td>
						<td class="text-center">
							<span class="badge bg-<?= $status_class ?>">
								<?= $status_text ?>
							</span>
						</td>
						<td class="text-center">
							<a href="<?= base_url() ?>activity/detail?id=<?= $activity->id_activity ?>" 
								class="btn btn-sm btn-primary" title="Detail">
								<i class="fas fa-eye"></i>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		
		<div class="alert alert-info mt-3">
			<i class="fas fa-info-circle me-2"></i>
			Total: <strong><?= count($activities) ?></strong> activity dari 
			<strong><?= date('d/m/Y', strtotime($start_date)) ?></strong> sampai 
			<strong><?= date('d/m/Y', strtotime($end_date)) ?></strong>
		</div>
		
		<?php else: ?>
		<div class="alert alert-warning">
			<i class="fas fa-exclamation-triangle me-2"></i>
			Tidak ada data activity. Silakan pilih filter dan klik "Tampilkan".
		</div>
		<?php endif; ?>
		
	</div>
</div>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Initialize map if there are activities with GPS
<?php if (!empty($activities)): ?>
	
	// Collect all activities with GPS coordinates
	var activityMarkers = [
		<?php 
		$hasGPS = false;
		foreach ($activities as $activity): 
			if ($activity->latitude && $activity->longitude): 
				$hasGPS = true;
		?>
		{
			lat: <?= $activity->latitude ?>,
			lng: <?= $activity->longitude ?>,
			title: "<?= addslashes($activity->judul_activity) ?>",
			user: "<?= addslashes($activity->nama) ?>",
			date: "<?= date('d/m/Y H:i', strtotime($activity->tanggal . ' ' . $activity->waktu)) ?>",
			company: "<?= addslashes($activity->nama_company) ?>",
			status: "<?= $activity->status ?>"
		},
		<?php 
			endif;
		endforeach; 
		?>
	];
	
	<?php if ($hasGPS): ?>
	// Initialize map
	var activityMap = L.map('activity-map');
	
	// Add OpenStreetMap tiles
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors',
		maxZoom: 19
	}).addTo(activityMap);
	
	// Create marker cluster group
	var bounds = [];
	
	// Add markers
	activityMarkers.forEach(function(marker) {
		if (marker.lat && marker.lng) {
			// Choose marker color based on status
			var markerColor = 'blue';
			if (marker.status === 'approved') markerColor = 'green';
			if (marker.status === 'rejected') markerColor = 'red';
			if (marker.status === 'pending') markerColor = 'orange';
			
			// Create custom icon
			var markerIcon = L.divIcon({
				className: 'custom-marker',
				html: '<div style="background-color: ' + markerColor + '; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"></div>',
				iconSize: [30, 30],
				iconAnchor: [15, 15]
			});
			
			// Add marker
			var leafletMarker = L.marker([marker.lat, marker.lng], {
				icon: markerIcon
			}).addTo(activityMap);
			
			// Add popup
			var popupContent = '<div style="min-width: 200px;">' +
				'<strong>' + marker.title + '</strong><br>' +
				'<small class="text-muted">' + marker.user + '</small><br>' +
				'<small class="text-muted">' + marker.company + '</small><br>' +
				'<small class="text-muted">' + marker.date + '</small><br>' +
				'<span class="badge bg-' + 
					(marker.status === 'approved' ? 'success' : marker.status === 'rejected' ? 'danger' : 'warning') + 
					' mt-1">' + marker.status.toUpperCase() + '</span>' +
				'</div>';
			
			leafletMarker.bindPopup(popupContent);
			
			// Store for bounds
			bounds.push([marker.lat, marker.lng]);
		}
	});
	
	// Fit map to show all markers
	if (bounds.length > 0) {
		activityMap.fitBounds(bounds, { padding: [50, 50] });
	} else {
		// Default center if no markers
		activityMap.setView([-7.250445, 112.768845], 13); // Surabaya
	}
	<?php else: ?>
	// No GPS data available
	document.getElementById('activity-map').innerHTML = 
		'<div class="alert alert-warning mb-0">' +
		'<i class="fas fa-exclamation-triangle me-2"></i>' +
		'Tidak ada data GPS yang tersedia untuk ditampilkan di peta.' +
		'</div>';
	<?php endif; ?>
	
<?php endif; ?>
</script>

<style>
.custom-marker {
	background: transparent;
	border: none;
}
.leaflet-popup-content {
	margin: 10px;
}
</style>

