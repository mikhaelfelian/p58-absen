<div class="card">
	<div class="card-header">
		<h5 class="card-title"><?=$title?></h5>
	</div>
	<?php
	$nama_bulan = nama_bulan(true);
	?>
	<div class="card-body">
		<form method="get" action="" class="form-laporan" enctype="multipart/form-data" style="max-width:500px">
			<div>
				<div class="form-group row mb-3">
					<label class="col-sm-4 col-form-label">Pegawai</label>
					<div class="col-sm-8">
						<?=options(['name' => 'id_user', 'class' => 'select2'], $user, set_value('id_user', @$presensi['id_user']))?>
					</div>
				</div>
				<div class="form-group row mb-3">
					<label class="col-sm-4 col-form-label">Periode</label>
					<div class="col-sm-8">
						<div class="input-group" style="width:250px">
							<?php
							echo options(['name' => 'bulan',  'style' => 'width:auto', 'class' => 'select2'], $nama_bulan, set_value('bulan', 1));
							$end_year = date('Y');
							$start_year = date('Y') - 2;
							$option = [];
							for ($i = $end_year; $i >= $start_year; $i--) {
								$option[$i] = $i;
							}
							echo options(['name' => 'tahun', 'style' => 'width:auto'], $option, set_value('tahun', $end_year));
							?>
						</div>
					</div>
				</div>
				<div class="form-group row mb-0">
					<div class="offset-sm-4">
						<button type="submit" id="submit" class="btn btn-primary me-2">Submit</button>
					</div>
				</div>
			</div>
		</form>
		<?php
		if (!empty($_GET['tahun'])) {
			if (!$presensi) {
				echo '<div class="mt-4">';
				show_message(['status' => 'error', 'message' => 'Data tidak ditemukan']);
				echo '</div>';
			} else {
				$hari_kerja = json_decode($setting_presensi['hari_kerja'], true);
				$num_day = date('t', strtotime(date($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . '01')));
				echo '
				<div class="row mb-0 mt-4">
					<div class="col-sm-8">
						<p class="fst-italic mb-0 mt-3">
							Keterangan: V = Tepat Waktu, TL = Terlambat Masuk, PSW = Pulang Sebelum Waktunya, TAM: Tidak Absen Masuk, TAP: Tidak Absen Pulang
						</p>
					</div>
					<div class="col-sm-4">
						<div class="d-flex mb-2 mt-2" style="justify-content:flex-end">
							<div class="btn-group">
								<button class="btn btn-outline-secondary me-0 btn-export btn-xs" type="button" id="btn-excel"><i class="fas fa-file-excel me-2"></i>XLSX</button>
							</div>
						</div>
					</div>
				</div>
				<div class="table-responsive">
				<table class="table table-striped table-bordered table-hover table-content-center">
					<thead>
						<tr>
							<th rowspan="2">No</th>
							<th rowspan="2">Nama</th>
							<th colspan="' . $num_day . '">' . $nama_bulan[$_GET['bulan']] . ' ' . $_GET['tahun'] . '</th>
						</tr>
						<tr>';
						
							for ($i = 1; $i <= $num_day; $i++) {
								$curr_time = strtotime($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . substr('0'.$i, -2));
								$curr_day = date('w', $curr_time);
								$class = 'class="bg-light fw-normal" style="color:#a7a7a7"';
								if (in_array($curr_day, $hari_kerja)) {
									$class = '';
								}
								echo '<th ' . $class . '>' . $i . '</th>';
							}
						
						echo '</tr>
					</thead>
					<tbody>';
					$no = 1;
					foreach ($presensi as $id_user => $absen_user) {
						echo '<tr>
							<td>' . $no . '</td>
							<td>' . ($user[$id_user] ?? '') . '</td>';
								for ($i = 1; $i <= $num_day; $i++) 
								{
									$curr_time = strtotime($_GET['tahun'] . '-' . $_GET['bulan'] . '-' . substr('0'.$i, -2));
									$curr_day = date('w', $curr_time);
									if (in_array($curr_day, $hari_kerja)) {
										if (key_exists($i, $absen_user)) {									
											switch ($absen_user[$i]) {
												case 'tam':
													echo '<td class="bg-danger-subtle">TAM</td>';
													break;
												case 'tam_psw':
													echo '<td class="bg-danger-subtle">TAM,PSW</td>';
													break;
												case 'tap':
													echo '<td class="bg-danger-subtle">TAP</td>';
													break;
												case 'tl_tap':
													echo '<td class="bg-danger-subtle">TL,TAP</td>';
													break;
												case 'tam_tap':
													echo '<td class="bg-danger-subtle">TAM,TAP</td>';
													break;
												case 'tw':
													echo '<td class="bg-success-subtle">v</td>';
													break;
												case 'tl':
													echo '<td class="bg-warning-subtle">TL</td>';
													break;
												case 'psw':
													echo '<td class="bg-warning-subtle">PSW</td>';
													break;
												case 'tl_psw':
													echo '<td class="bg-warning-subtle">TL,PSW</td>';
													break;
											}
										} else {
											echo '<td class="bg-danger"">TA</td>';
										}
									} else {
										echo '<td class="bg-light"></td>';
									}
								}
						
						echo '</tr>';
						$no++;
					}
				echo '</tbody>
				</table>
				</div>';
			}
		}
		?>
	</div>
</div>
<span style="display:none" id="setting-presensi"><?=json_encode($setting_presensi)?></span>