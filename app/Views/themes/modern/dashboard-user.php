<?php helper('html'); ?>
<div class="card-body dashboard">
	<div class="row"> 
		<!-- Welcome Card -->
		<div class="col-12 mb-4">
			<div class="card bg-primary text-white">
				<div class="card-body text-center">
					<h3 class="card-title">Selamat Datang, <?=$user['nama']?>!</h3>
					<p class="mb-0">Dashboard Presensi Anda</p>
				</div>
			</div>
		</div>
		
		<!-- Today's Status -->
		<div class="col-12 mb-4">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">Status Hari Ini</h5>
				</div>
				<div class="card-body">
					<?php 
					$today_stats = $stats_tahun['current_attendance'] ?? [];
					$has_entry = !empty($today_stats);
					?>
					
					<?php if ($has_entry): ?>
						<div class="row">
							<?php 
							$masuk_done = false;
							$pulang_done = false;
							
							foreach ($today_stats as $stat) {
								if ($stat['jenis_presensi'] == 'masuk') {
									$masuk_done = true;
									$masuk_waktu = $stat['waktu'];
									$masuk_batas = $stat['batas_waktu_presensi'];
								}
								if ($stat['jenis_presensi'] == 'pulang') {
									$pulang_done = true;
									$pulang_waktu = $stat['waktu'];
									$pulang_batas = $stat['batas_waktu_presensi'];
								}
							}
							?>
							
							<div class="col-md-6 mb-3">
								<div class="alert <?=$masuk_done ? 'alert-success' : 'alert-warning'?>">
									<strong>Presensi Masuk</strong><br>
									<?php if ($masuk_done): ?>
										Waktu: <?=$masuk_waktu?>
									<?php else: ?>
										Belum absen masuk
									<?php endif; ?>
								</div>
							</div>
							
							<div class="col-md-6 mb-3">
								<div class="alert <?=$pulang_done ? 'alert-success' : 'alert-warning'?>">
									<strong>Presensi Pulang</strong><br>
									<?php if ($pulang_done): ?>
										Waktu: <?=$pulang_waktu?>
									<?php else: ?>
										Belum absen pulang
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php else: ?>
						<div class="alert alert-info">
							<i class="fas fa-info-circle me-2"></i>
							Belum ada data presensi hari ini
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
		<!-- Statistics Cards -->
		<?php 
		$stats = $stats_tahun['stats'] ?? [];
		$total_presensi = $stats['total_presensi'] ?? 0;
		$masuk_count = $stats['masuk'] ?? 0;
		$pulang_count = $stats['pulang'] ?? 0;
		?>
		
		<div class="col-lg-4 col-sm-6 mb-4">
			<div class="card text-white bg-primary shadow">
				<div class="card-body">
					<h5 class="card-title h3"><?=$total_presensi?></h5>
					<p class="card-text">Total Presensi Tahun <?=date('Y')?></p>
				</div>
			</div>
		</div>
		
		<div class="col-lg-4 col-sm-6 mb-4">
			<div class="card text-white bg-success shadow">
				<div class="card-body">
					<h5 class="card-title h3"><?=$masuk_count?></h5>
					<p class="card-text">Total Masuk</p>
				</div>
			</div>
		</div>
		
		<div class="col-lg-4 col-sm-6 mb-4">
			<div class="card text-white bg-info shadow">
				<div class="card-body">
					<h5 class="card-title h3"><?=$pulang_count?></h5>
					<p class="card-text">Total Pulang</p>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Recent Presensi Table -->
	<div class="row">
		<div class="col-12 mb-4">
			<div class="card">
				<div class="card-header">
					<h5 class="card-title">Riwayat Presensi Terbaru</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-striped table-hover">
							<thead>
								<tr>
									<th>No</th>
									<th>Tanggal</th>
									<th>Jenis</th>
									<th>Waktu</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$recents = $recent_presensi ?? [];
								if (!empty($recents)):
									$no = 1;
									foreach ($recents as $recent):
								?>
									<tr>
										<td><?=$no++?></td>
										<td><?=$recent['tanggal']?></td>
										<td><?=ucfirst($recent['jenis_presensi'])?></td>
										<td><?=$recent['waktu']?></td>
										<td>
											<?php
											// Simplified status: only show "Masuk" or "Pulang Belum Waktu"
											$status = '';
											if ($recent['jenis_presensi'] == 'masuk') {
												$status = '<span class="badge bg-success">Masuk</span>';
											} elseif ($recent['jenis_presensi'] == 'pulang') {
												// Show "Pulang Belum Waktu" if is_valid is 0, otherwise just "Pulang"
												if (isset($recent['is_valid']) && $recent['is_valid'] == 0) {
													$status = '<span class="badge bg-warning">Pulang Belum Waktu</span>';
												} else {
													$status = '<span class="badge bg-info">Pulang</span>';
												}
											} else {
												$status = '<span class="badge bg-secondary">-</span>';
											}
											echo $status;
											?>
										</td>
									</tr>
								<?php
									endforeach;
								else:
								?>
									<tr>
										<td colspan="5" class="text-center">Belum ada data presensi</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
