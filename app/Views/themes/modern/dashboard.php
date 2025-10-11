<?php helper('html');
$tahun = $list_tahun ? max($list_tahun) : date('Y');
$tahun_prev = $tahun - 1;
?>
<div class="card-body dashboard">
	<div class="row">
		<div class="col-lg-3 col-sm-6 col-xs-12 mb-4">
			<div class="card text-bg-primary shadow">
				<div class="card-body card-stats">
					<div class="description">
						<h5 class="card-title h4"><?=!empty($presensi_pertahun[$tahun]['tepat_waktu']) ? format_number($presensi_pertahun[$tahun]['tepat_waktu']) . '/' . format_number($total_jumlah_presensi[$tahun]) : 0?></h5>
						<?php
						$persen = $total_jumlah_presensi ? round($presensi_pertahun[$tahun]['tepat_waktu'] / $total_jumlah_presensi[$tahun], 2) * 100: 0;
						?>
						<p class="card-text">Presensi Tepat Waktu <?=format_number($persen)?>%</p>
						
					</div>
					<div class="icon bg-warning-light">
						<!-- <i class="fas fa-clipboard-list"></i> -->
						<i class="material-icons">local_shipping</i>
					</div>
				</div>
				<div class="card-footer">
					<div class="card-footer-left">
						<div class="icon me-2">
							<?php
								$exists = false;
								if (!empty($presensi_pertahun[$tahun]['tepat_waktu'])) {
									$exists = true;
									$growth = $presensi_pertahun[$tahun_prev]['tepat_waktu'] ? ($presensi_pertahun[$tahun]['tepat_waktu'] - $presensi_pertahun[$tahun_prev]['tepat_waktu']) / $presensi_pertahun[$tahun_prev]['tepat_waktu'] * 100 : 0; 
									if ($growth == 0) {
										$class = "fa-grip-lines";
									} else {
										$class = $growth > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
									}
									echo '<i class="fas ' . $class . '"></i>';
								} else {
									$growth = 0;
								}
							?>
						</div>
						<p><?=$exists ? round($growth, 2) . '%' : '-'?></p>
					</div>
					<div class="card-footer-right">
						<p><?=!empty($list_tahun) ? max($list_tahun) : ''?></p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-sm-6 col-xs-12 mb-4">
			<div class="card text-white bg-success shadow">
				<div class="card-body card-stats">
					<div class="description">
						<h5 class="card-title"><?=!empty($presensi_pertahun[$tahun]['terlambat_masuk']) ? format_number($presensi_pertahun[$tahun]['terlambat_masuk']) . '/' . format_number($total_presensi_masuk[$tahun]) : 0?></h5>
						<?php
						$persen = $total_presensi_masuk ? round($presensi_pertahun[$tahun]['terlambat_masuk'] / $total_presensi_masuk[$tahun], 2) * 100 : 0;
						?>
						<p class="card-text">Terlambat Masuk <?=format_number($persen)?>%</p>
					</div>
					<div class="icon">
						<!-- <i class="fas fa-shopping-cart"></i>-->
						<i class="material-icons">local_mall</i>
					</div>
				</div>
				<div class="card-footer">
					<div class="card-footer-left">
						<div class="icon me-2">
							<?php
								$exists = false;
								if (!empty($presensi_pertahun[$tahun]['terlambat_masuk'])) {
									$exists = true;
									$growth = ($presensi_pertahun[$tahun]['terlambat_masuk'] - $presensi_pertahun[$tahun_prev]['terlambat_masuk']) / $presensi_pertahun[$tahun_prev]['terlambat_masuk'] * 100; 
									if ($growth == 0) {
										$class = "fa-grip-lines";
									} else {
										$class = $growth > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
									}
									echo '<i class="fas ' . $class . '"></i>';
								} else {
									$growth = 0;
								}
							?>
						</div>
						<p><?=$exists ? round($growth, 2) . '%' : '-'?></p>
					</div>
					<div class="card-footer-right">
						<p><?=!empty($list_tahun) ? max($list_tahun) : ''?></p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-sm-6 col-xs-12 mb-4">
			<div class="card text-white bg-warning shadow">
				<div class="card-body card-stats">
					<div class="description">
						<h5 class="card-title"><?=!empty($presensi_pertahun[$tahun]['pulang_sebelum_waktunya']) ? format_number($presensi_pertahun[$tahun]['pulang_sebelum_waktunya'])  . '/' . format_number($total_presensi_pulang[$tahun]) : 0?></h5>
						<?php
						$persen = $total_presensi_pulang[$tahun] ? round($presensi_pertahun[$tahun]['pulang_sebelum_waktunya'] / $total_presensi_pulang[$tahun], 2) * 100 : 0;
						?>
						<p class="card-text">Pulang Awal <?=format_number($persen)?>%</p>
					</div>
					<div class="icon">
						<!-- <i class="fas fa-money-bill-wave"></i> -->
						<i class="material-icons">payments</i>
					</div>
				</div>
				<div class="card-footer">
					<div class="card-footer-left">
						<div class="icon me-2">
							<?php
							/* echo '<pre>';
							print_r($presensi_pertahun);
							die; */
								$exists = false;
								if (!empty($presensi_pertahun[$tahun]['pulang_sebelum_waktunya'])) {
									$exists = true;
									$growth = ($presensi_pertahun[$tahun]['pulang_sebelum_waktunya'] - $presensi_pertahun[$tahun_prev]['pulang_sebelum_waktunya']) / $presensi_pertahun[$tahun_prev]['pulang_sebelum_waktunya'] * 100; 
									if ($growth == 0) {
										$class = "fa-grip-lines";
									} else {
										$class = $growth > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
									}
									echo '<i class="fas ' . $class . '"></i>';
								} else {
									$growth = 0;
								}
							?>
						</div>
						<p><?=$exists ? round($growth, 2) . '%' : '-'?></p>
					</div>
					<div class="card-footer-right">
						<p><?=!empty($list_tahun) ? max($list_tahun) : ''?></p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-3 col-sm-6 col-xs-12 mb-4">
			<div class="card text-white bg-danger shadow">
				<div class="card-body card-stats">
					<div class="description">
						<h5 class="card-title"><?=!empty($presensi_pertahun[$tahun]['tidak_absen']) ? format_number($presensi_pertahun[$tahun]['tidak_absen']) .  '/' . format_number($total_jumlah_presensi[$tahun]/2) : 0?></h5>
						<?php
						$persen = $total_jumlah_presensi ? round($presensi_pertahun[$tahun]['tidak_absen'] / ($total_jumlah_presensi[$tahun]/2), 2) * 100 : 0;
						?>
						<p class="card-text">Tidak Absen <?=format_number($persen)?>%</p>
					</div>
					<div class="icon">
						<!-- <i class="fas fa-money-bill-wave"></i> -->
						<i class="material-icons">person</i>
					</div>
				</div>
				<div class="card-footer">
					<div class="card-footer-left">
						<div class="icon me-2">
							<?php
								$exists = false;
								if (!empty($presensi_pertahun[$tahun]['tidak_absen'])) {
									$exists = true;
									$growth = !empty($presensi_pertahun[$tahun_prev]['tidak_absen']) ? ($presensi_pertahun[$tahun]['tidak_absen'] - $presensi_pertahun[$tahun_prev]['tidak_absen']) / $presensi_pertahun[$tahun_prev]['tidak_absen'] * 100 : 0; 
									if ($growth == 0) {
										$class = "fa-grip-lines";
									} else {
										$class = $growth > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down';
									}
									echo '<i class="fas ' . $class . '"></i>';
								} else {
									$growth = 0;
								}
							?>
						</div>
						<p><?=$exists ? round($growth) . '%' : '-'?></p>
					</div>
					<div class="card-footer-right">
						<p><?=!empty($list_tahun) ? max($list_tahun) : ''?></p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-12 col-md-12 col-lg-12 col-xl-8 mb-4">
			<div class="card">
				<div class="card-header">
					<div class="card-header-start">
						<h6 class="card-title">Presensi Perbulan</h6>
					</div>
				</div>
				<div class="card-body">
					<div style="overflow: auto">
						<canvas id="bar-container" style="min-width:500px;margin:auto;width:100%"></canvas>
					</div>
				</div>
			</div>
		</div>
		<div class="col-12 col-md-12 col-lg-12 col-xl-4 mb-4">
			<div class="card" style="height:100%">
				<div class="card-header">
					<div class="card-header-start">
						<h5 class="card-title">Presensi Pertahun</h5>
					</div>
				</div>
				<div class="card-body d-flex">
					<canvas id="chart-total-presensi" style="margin:auto;max-width:350px;width:100%"></canvas>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12 col-lg-4 mb-4">
			<div class="card" style="height:100%">
				<div class="card-header">
					<div class="card-header-start">
						<h5 class="card-title">Presensi Tepat Waktu</h5>
					</div>
				</div>
				<div class="card-body d-flex justify-content-center">
					<div style="overflow: auto; width:100%">
						<?php
						if ($presensi_urut_tepat_waktu) {
							echo '<table class="table table-border table-striped">
								<thead>
									<tr>
										<th>No</th>
										<th>Nama</th>
										<th>Tepat Waktu</th>
									</tr>
								</thead>
								<tbody>';
							$no = 1;
							foreach ($presensi_urut_tepat_waktu as $val) {
								echo '
									<tr>
										<td>' . $no . '</td>
										<td>' . $val['nama'] . '</td>
										<td class="text-end">' . $val['jml_tepat_waktu']. '</td>
									</tr>';
								$no++;
							}
							echo '</tbody>
								</table>';
						} else {
							echo '<div class="alert alert-danger">Data tidak ditemukan</div>';
						}
						?>
						<small><em>*) Tepat waktu adalah jumlah presensi masuk dan pulang tepat waktu tahun <?=$tahun?></em></small>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-12 col-lg-8 mb-4">
			<div class="card" style="height:100%">
				<div class="card-header">
					<div class="card-header-start">
						<h5 class="card-title">Presensi Terbaru</h5>
					</div>
					<div class="card-header-end">
						<?php
						if (!empty($list_tahun)) {
							echo '<form method="get" action="" class="d-flex">
									' . options(['name' => 'tahun', 'id' => 'tahun-presensi-terbaru'], $list_tahun, $tahun ) . '
							</form>';
						}
						?>
					</div>
				</div>
				<div class="card-body" style="min-height:348px">
					<?php
					if (!$jml_data_presensi) {
						echo '<div class="alert alert-danger">Data tidak ditemukan</div>';
					} else {
						
						?>
						<div class="table-responsive">
							<?php
							$column =[
										'ignore_urut' => 'No'
										, 'nama' => 'Nama Pegawai'
										, 'tanggal' => 'Tanggal'
										, 'waktu' => 'Waktu'
										, 'jenis_presensi' => 'Jenis'
										, 'status' => 'Status'
									];
							$settings = [];
							$settings['order'] = [2,'desc'];
							$index = 0;
							$th = '';
							foreach ($column as $key => $val) {
								$th .= '<th>' . $val . '</th>'; 
								if (strpos($key, 'ignore') !== false) {
									$settings['columnDefs'][] = ["targets" => $index, "orderable" => false];
								}
								$index++;
							}
							
							?>
							
							<table id="tabel-presensi-terbaru" class="table display table-striped table-hover" style="width:100%">
							<thead>
								<tr>
									<?=$th?>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td colspan="<?=count($column)?>" class="text-center">Loading data...</td>
								</tr>
							</tbody>
							</table>
							<?php
								$column_dt =[];
								foreach ($column as $key => $val) {
									$column_dt[] = ['data' => $key];
								}
							?>
							<span id="presensi-terbaru-column" style="display:none"><?=json_encode($column_dt)?></span>
							<span id="presensi-terbaru-setting" style="display:none"><?=json_encode($settings)?></span>
							<span id="presensi-terbaru-url" style="display:none"><?=current_url() . '/getDataDTPresensiTerbaru?tahun=' . ( !empty($list_tahun) ? max($list_tahun) : 0 )?></span>
						</div>
					<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">

function dynamicColors() {
	var r = Math.floor(Math.random() * 255);
	var g = Math.floor(Math.random() * 255);
	var b = Math.floor(Math.random() * 255);
	return "rgba(" + r + "," + g + "," + b + ", 0.8)";
}

let presensi_perbulan = '<?=$presensi_perbulan ? json_encode($presensi_perbulan[$tahun]) : '[]'?>';
let presensi_pertahun = '<?=$presensi_pertahun ? json_encode($presensi_pertahun[$tahun]) : '[]'?>';
</script>