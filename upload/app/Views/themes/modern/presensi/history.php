<?php helper('html'); ?>
<div class="card-body">
	<h3>Riwayat Presensi</h3>

	<table class="table table-bordered">
		<thead>
			<tr>
				<th>Tanggal</th>
				<th>Masuk</th>
				<th>Pulang</th>
				<th>Durasi</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($list)): ?>
			<tr>
				<td colspan="5" class="text-center">Tidak ada data presensi</td>
			</tr>
			<?php else: ?>
			<?php foreach ($list as $row): ?>
			<tr>
				<td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
				<td><?= $row['waktu'] ? substr($row['waktu'], 0, 5) : '-' ?></td>
				<td><?= $row['waktu_pulang'] ? substr($row['waktu_pulang'], 0, 5) : '-' ?></td>
				<td><?= $row['durasi'] ? number_format($row['durasi'], 2) . ' jam' : '-' ?></td>
				<td><?= $row['is_valid'] ? 'Valid' : 'Tidak' ?></td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>

