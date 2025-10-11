<?php
if ($list_jabatan) {
	foreach ($list_jabatan as $val) {
		echo '<div class="card item-container shadow-sm mb-2" id="jabatan-' . $val['id_jabatan'] . '">
				<ul class="toolbox">
					<li>
						<div class="grip-handler"><i class="fas fa-grip-horizontal"></i></div>
					</li>
					<li>
						<a class="bg-success btn-edit text-white small" data-id="' . $val['id_jabatan'] . '" target="_blank" href="http://localhost:7777/aplikasi-antrian/antrian"><i class="fas fa-pencil-alt"></i></a>
						
					</li>
					<li>
						<button type="button" class="bg-danger btn-delete text-white small" data-delete-title="Hapus jabatan: <strong>' . $val['nama_jabatan'] . '</strong>?" data-id="' . $val['id_jabatan'] . '"><i class="fas fa-times"></i></button>
					</li>
				</ul>
				<div class="body">
					<div class="row col-sm-12 title">' . $val['nama_jabatan'] . '</div>
				</div>
				<input type="hidden" name="urut[]" value="' . $val['id_jabatan'] . '">
			</div>';
	}
} else {
	echo show_message(['status' => 'error', 'message' => 'Data tidak ditemukan']);
}