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
	<div class="riwayat-absen-content rounded-3" style="overflow:scroll">
	
		<?php
		// echo '<pre>'; print_r($riwayat_presensi); die;
		$begin = strtotime($start_date_db);
		$end = strtotime($end_date_db);
		$hari_kerja = json_decode($setting_presensi['hari_kerja'], true);
		for ($i = $end; $i >= $begin; $i = strtotime('-1 day', $i)) 
		{
			$waktu_masuk = $waktu_pulang = '-';
			$curr = date('Y-m-d', $i);
			
			$date_w = date('w', $i);
			if (in_array($date_w, $hari_kerja)) {
				if (key_exists($curr, $riwayat_presensi)) 
				{
					if (key_exists('masuk', $riwayat_presensi[$curr])) {
						$class = $riwayat_presensi[$curr]['masuk']['presensi_masuk'] > $riwayat_presensi[$curr]['masuk']['batas_presensi_masuk'] ? 'text-danger' : '';
						$waktu_masuk = '<span class="' . $class . '">' . $riwayat_presensi[$curr]['masuk']['presensi_masuk'] . '</span>';
					}
					
					if (key_exists('pulang', $riwayat_presensi[$curr])) {
						$class = $riwayat_presensi[$curr]['masuk']['presensi_pulang'] < $riwayat_presensi[$curr]['masuk']['batas_presensi_pulang'] ? 'text-danger' : '';
						$waktu_pulang = '<span class="' . $class . '">' . $riwayat_presensi[$curr]['pulang']['presensi_pulang'] . '</span>';
					}
					
				}
			}
			
			$style = '';
			if (!in_array($date_w, $hari_kerja)) {
				$style = ';color:#CCCCCC !important';
			}
						
			echo '<div class="mb-2" style="' . $style . '">
				<div class="fs-bold">' . $nama_hari[date('w', $i)] . ', ' . date('d', $i) . ' ' . $nama_bulan[date('n', $i)] . ' ' . date('Y', $i) . '</div>
				<div class="d-flex justify-content-between">
					<div class="d-flex align-items-center">	
						<i class="bi bi-box-arrow-in-right me-2 text-success" style="font-size:20px' . $style . '"></i>
						<span>Masuk</span>
					</div>
					<div class="d-flex align-items-center">	
						<span>' . $waktu_masuk . '</span>
					</div>
				</div>
				<div class="d-flex justify-content-between">
					<div class="d-flex align-items-center">	
						<i class="bi bi-box-arrow-right me-3 text-warning" style="font-size:17px' . $style . '"></i>
						<span>Pulang</span>
					</div>
					<div class="d-flex align-items-center">	
						<span>' . $waktu_pulang . '</span>
					</div>
				</div>
			</div>';
			if ($i > $begin) {
				echo '<hr/>';
			}
		}
		?>
	</div>
	</div>
	<input type="hidden" id="page-type" value="presensi-riwayat"/>
</div>
<?= $this->endSection() ?>