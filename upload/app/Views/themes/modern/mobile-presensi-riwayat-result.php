<?= $this->extend('themes/modern/layout-mobile') ?>
<?= $this->section('content') ?>
<?php
$nama_bulan = nama_bulan();
$nama_hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
// echo date('j'); die;
?>
<div class="container mt-3">
	<p class="mt-3 mb-3 text-center text-light">RIWAYAT PRESENSI</p>
	<div class="bg-light p-4 rounded-3 mb-4">
		<div class="input-group">
			<input type="text" class="form-control flatpickr text-start" name="periode_presensi" id="periode-presensi" value="<?=$start_date . ' s.d. ' . $end_date?>"/>
			<span class="input-group-text">
				<i class="bi bi-calendar"></i>
			</span>
		</div>
		<span style="display:none" id="periode-presensi-current"><?=$start_date . ' s.d. ' . $end_date?></span>
	</div>
	<div class="bg-light p-4 rounded-3 riwayat-absen-container">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered mb-0">
				<thead class="table-dark">
					<tr>
						<th class="text-center" style="width: 50px;">No</th>
						<th>Tanggal</th>
						<th class="text-center">Masuk</th>
						<th class="text-center">Pulang</th>
						<th class="text-center">Jam Kerja</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$begin = strtotime($start_date_db);
					$end = strtotime($end_date_db);
					$hari_kerja = json_decode($setting_presensi['hari_kerja'], true);
					$no = 1;
					
					// Collect all attendance records
					$attendance_records = [];
					for ($i = $end; $i >= $begin; $i = strtotime('-1 day', $i)) {
						$curr = date('Y-m-d', $i);
						$date_w = date('w', $i);
						
						if (in_array($date_w, $hari_kerja) && key_exists($curr, $riwayat_presensi)) {
							$presensi_masuk = $riwayat_presensi[$curr]['masuk']['presensi_masuk'] ?? null;
							$presensi_pulang = $riwayat_presensi[$curr]['pulang']['presensi_pulang'] ?? null;
							$durasi = $riwayat_presensi[$curr]['durasi'] ?? null;
							$is_valid = $riwayat_presensi[$curr]['is_valid'] ?? 0;
							$batas_presensi_masuk = $riwayat_presensi[$curr]['masuk']['batas_presensi_masuk'] ?? null;
							
							if ($presensi_masuk || $presensi_pulang) {
								$attendance_records[] = [
									'date' => $curr,
									'masuk' => $presensi_masuk,
									'pulang' => $presensi_pulang,
									'durasi' => $durasi,
									'is_valid' => $is_valid,
									'batas_masuk' => $batas_presensi_masuk
								];
							}
						}
					}
					
					// Display records in table
					if (empty($attendance_records)) {
						echo '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data presensi untuk periode ini</td></tr>';
					} else {
						foreach ($attendance_records as $record) {
							// Format waktu masuk
							$waktu_masuk_display = '-';
							if ($record['masuk']) {
								$waktu_masuk_time = substr($record['masuk'], 0, 5); // Get HH:MM
								$class_masuk = '';
								if ($record['batas_masuk'] && $record['masuk'] > $record['batas_masuk']) {
									$class_masuk = 'text-danger fw-bold';
								}
								$waktu_masuk_display = '<span class="' . $class_masuk . '">' . $waktu_masuk_time . '</span>';
							}
							
							// Format waktu pulang
							$waktu_pulang_display = '-';
							if ($record['pulang']) {
								$waktu_pulang_time = substr($record['pulang'], 0, 5); // Get HH:MM
								$waktu_pulang_display = '<span>' . $waktu_pulang_time . '</span>';
							}
							
							// Determine date display (check if crosses midnight)
							$tanggal_display = date('d/m/Y', strtotime($record['date']));
							if ($record['masuk'] && $record['pulang']) {
								// Check if pulang time is earlier than masuk time (crosses midnight)
								$masuk_timestamp = strtotime($record['date'] . ' ' . $record['masuk']);
								$pulang_timestamp = strtotime($record['date'] . ' ' . $record['pulang']);
								
								// If pulang is before masuk, it means it crossed midnight
								if ($pulang_timestamp < $masuk_timestamp) {
									$pulang_date = date('Y-m-d', strtotime($record['date'] . ' +1 day'));
									$tanggal_display = date('d/m/Y', strtotime($record['date'])) . ' - ' . date('d/m/Y', strtotime($pulang_date));
								}
							}
							
							// Format jam kerja
							$jam_kerja_display = '-';
							if ($record['durasi'] !== null && $record['durasi'] > 0) {
								$durasi_formatted = number_format($record['durasi'], 2);
								// Remove trailing zeros
								$durasi_formatted = rtrim(rtrim($durasi_formatted, '0'), '.');
								$jam_kerja_display = $durasi_formatted . ' jam';
								
								// Add validation badge
								$valid_class = $record['is_valid'] ? 'bg-success' : 'bg-warning';
								$valid_text = $record['is_valid'] ? 'Valid' : 'Tidak Valid';
								$jam_kerja_display .= ' <span class="badge ' . $valid_class . ' ms-1">' . $valid_text . '</span>';
							}
							
							echo '<tr>';
							echo '<td class="text-center fw-semibold">' . $no . '</td>';
							echo '<td class="fw-medium">' . $tanggal_display . '</td>';
							echo '<td class="text-center">' . $waktu_masuk_display . '</td>';
							echo '<td class="text-center">' . $waktu_pulang_display . '</td>';
							echo '<td class="text-center">' . $jam_kerja_display . '</td>';
							echo '</tr>';
							
							$no++;
						}
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<input type="hidden" id="page-type" value="presensi-riwayat"/>
</div>
<?= $this->endSection() ?>