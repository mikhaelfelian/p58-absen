<div class="card">
	<div class="card-header">
		<h5 class="card-title mb-0">
			<i class="fas fa-calendar-alt me-2"></i><?= $title ?>
		</h5>
	</div>
	<?php
	$nama_bulan = nama_bulan(true);
	?>
	<div class="card-body">
		
		<!-- Filter Form -->
		<form method="get" action="" id="form-filter">
			<div class="row g-3 mb-4">
				
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
				<?php if (isset($company) && !empty($company)): ?>
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
				<?php endif; ?>
				
				<!-- Period Filter -->
				<div class="col-md-4">
					<label class="form-label">Bulan</label>
					<select class="form-select select2" name="bulan" id="bulan">
						<?php foreach ($nama_bulan as $bulan_num => $bulan_nama): ?>
							<option value="<?= $bulan_num ?>" <?= (@$_GET['bulan'] == $bulan_num || (!isset($_GET['bulan']) && $bulan_num == date('n'))) ? 'selected' : '' ?>>
								<?= $bulan_nama ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<div class="col-md-4">
					<label class="form-label">Tahun</label>
					<?php
					$end_year = date('Y');
					$start_year = date('Y') - 2;
					$option = [];
					for ($i = $end_year; $i >= $start_year; $i--) {
						$option[$i] = $i;
					}
					?>
					<select class="form-select select2" name="tahun" id="tahun">
						<?php foreach ($option as $tahun): ?>
							<option value="<?= $tahun ?>" <?= (@$_GET['tahun'] == $tahun || (!isset($_GET['tahun']) && $tahun == $end_year)) ? 'selected' : '' ?>>
								<?= $tahun ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<!-- Buttons -->
				<div class="col-md-12">
					<button type="submit" class="btn btn-primary">
						<i class="fas fa-search me-2"></i>Tampilkan
					</button>
					<button type="button" class="btn btn-success" id="btn-excel">
						<i class="fas fa-file-excel me-2"></i>Export Excel
					</button>
				</div>
			</div>
		</form>
		
		<?php
		if (!empty($_GET['tahun'])) {
			if (!$presensi) {
				echo '<div class="alert alert-warning mt-4">';
				echo '<i class="fas fa-exclamation-triangle me-2"></i>';
				echo 'Data tidak ditemukan.';
				echo '</div>';
			} else {
				$hari_kerja = json_decode($setting_presensi['hari_kerja'], true);
				$num_day = date('t', strtotime($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . '01'));
				?>
				<div class="row mb-0 mt-4">
					<div class="col-sm-8">
						<p class="fst-italic mb-0 mt-3">
							<strong>Keterangan:</strong> V = Tepat Waktu, TL = Terlambat Masuk, PSW = Pulang Sebelum Waktunya, TAM = Tidak Absen Masuk, TAP = Tidak Absen Pulang
						</p>
					</div>
					<div class="col-sm-4">
						<div class="d-flex mb-2 mt-2" style="justify-content:flex-end">
							<div class="btn-group">
								<button class="btn btn-outline-secondary me-0 btn-export btn-xs" type="button" id="btn-excel">
									<i class="fas fa-file-excel me-2"></i>XLSX
								</button>
							</div>
						</div>
					</div>
				</div>
				<div class="table-responsive">
					<table class="table table-striped table-bordered table-hover table-content-center">
						<thead>
							<tr>
								<th rowspan="2" class="text-center">No</th>
								<th rowspan="2" class="text-center">Nama</th>
								<th colspan="<?= $num_day ?>" class="text-center"><?= $nama_bulan[$_GET['bulan']] . ' ' . $_GET['tahun'] ?></th>
							</tr>
							<tr>
								<?php
								for ($i = 1; $i <= $num_day; $i++) {
									$curr_time = strtotime($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . substr('0' . $i, -2));
									$curr_day = date('w', $curr_time);
									$class = 'class="bg-light fw-normal text-center" style="color:#a7a7a7"';
									if (in_array($curr_day, $hari_kerja)) {
										$class = 'class="text-center"';
									}
									echo '<th ' . $class . '>' . $i . '</th>';
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							$no = 1;
							foreach ($presensi as $id_user => $absen_user) {
								echo '<tr>';
								echo '<td class="text-center">' . $no . '</td>';
								echo '<td>' . ($user[$id_user] ?? '') . '</td>';
								
								for ($i = 1; $i <= $num_day; $i++) {
									$curr_time = strtotime($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . substr('0' . $i, -2));
									$curr_day = date('w', $curr_time);
									
									if (in_array($curr_day, $hari_kerja)) {
										if (key_exists($i, $absen_user)) {
											switch ($absen_user[$i]) {
												case 'tam':
													echo '<td class="bg-danger-subtle text-center">TAM</td>';
													break;
												case 'tam_psw':
													echo '<td class="bg-danger-subtle text-center">TAM,PSW</td>';
													break;
												case 'tap':
													echo '<td class="bg-danger-subtle text-center">TAP</td>';
													break;
												case 'tl_tap':
													echo '<td class="bg-danger-subtle text-center">TL,TAP</td>';
													break;
												case 'tam_tap':
													echo '<td class="bg-danger-subtle text-center">TAM,TAP</td>';
													break;
												case 'tw':
													echo '<td class="bg-success-subtle text-center">V</td>';
													break;
												case 'tl':
													echo '<td class="bg-warning-subtle text-center">TL</td>';
													break;
												case 'psw':
													echo '<td class="bg-warning-subtle text-center">PSW</td>';
													break;
												case 'tl_psw':
													echo '<td class="bg-warning-subtle text-center">TL,PSW</td>';
													break;
												default:
													echo '<td class="text-center">-</td>';
													break;
											}
										} else {
											echo '<td class="bg-danger text-center">TA</td>';
										}
									} else {
										echo '<td class="bg-light text-center"></td>';
									}
								}
								
								echo '</tr>';
								$no++;
							}
							?>
						</tbody>
					</table>
				</div>
				<?php
			}
		} else {
			echo '<div class="alert alert-info">';
			echo '<i class="fas fa-info-circle me-2"></i>';
			echo 'Silakan pilih periode (bulan dan tahun) kemudian klik "Tampilkan" untuk melihat data rekap presensi.';
			echo '</div>';
		}
		?>
	</div>
</div>
<span style="display:none" id="setting-presensi"><?= json_encode($setting_presensi) ?></span>
