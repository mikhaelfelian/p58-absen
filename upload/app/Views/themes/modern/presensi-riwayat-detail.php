<?php
helper('html');
?>
<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th>No</th>
			<th>Nama</th>
			<th>Tanggal</th>
			<th>Presensi</th>
			<th>Waktu</th>
			<th>Status</th>
			<th>Aksi</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$no = 1;
	foreach ($result as $val) {
		echo '<tr>
			<td>' . $no . '</td>
			<td>' . $val['nama'] . '</td>
			<td>' . format_tanggal($val['tanggal'], 'dd-mm-yyyy'). '</td>
			<td>' . $val['jenis_presensi'] . '</td>
			<td>' . $val['waktu'] . '</td>
			<td>' . $val['status'] . '</td>
			<td>
				<div class="input-group">
				<a class="btn btn-success" href="' . base_url() . 'presensi-detail/edit?id=' . $val['id_user_presensi'] . '"><i class="fas fa-edit"></i></a>
				<button class="btn btn-danger btn-delete-presensi-detail" data-id="' . $val['id_user_presensi'] . '"><i class="fas fa-times"></i></button>
				</div>
			</td>
		</tr>';
		$no++;
	}
	?>
	</tbody>
</table>