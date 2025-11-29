<?php helper('html'); ?>
<div class="card-body">
	<h3>Status Presensi</h3>

	<div class="card mb-3">
		<div class="card-body">
			<p><strong>Masuk:</strong> <?= $statusMasuk ?> (<?= $jamMasuk ?>)</p>
			<p><strong>Pulang:</strong> <?= $statusPulang ?> (<?= $jamPulang ?>)</p>
		</div>
	</div>

	<div class="mb-3">
		<a href="/presensi/masuk" class="btn btn-success">Absen Masuk</a>
		<a href="/presensi/pulang" class="btn btn-danger">Absen Pulang</a>
		<a href="/presensi/history" class="btn btn-info">Riwayat Presensi</a>
	</div>
</div>

