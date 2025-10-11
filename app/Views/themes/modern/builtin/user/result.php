<div class="card">
	<div class="card-body">
		<div class="d-flex justify-content-between">
			<div>
				<a href="<?=current_url()?>/add" class="btn btn-success btn-xs"><i class="fa fa-plus pe-1"></i> Tambah Data</a>
				<a href="<?=base_url()?>builtin/user/uploadexcel" class="btn btn-success btn-xs"><i class="fas fa-arrow-up-from-bracket pe-1"></i> Upload Excel</a>
				<button class="btn btn-danger btn-delete-all-user btn-xs"><i class="fas fa-trash me-2"></i>Hapus Semua Pegawai</button>
			</div>
			<div class="btn-group">
				<button class="btn btn-outline-secondary me-0 btn-export btn-xs" type="button" id="btn-excel" disabled><i class="fas fa-file-excel me-2"></i>XLSX</button>
			</div>
		</div>
		<hr/>
		<?php
		if (!empty($message)) {
			show_message($message);
		}
		
		$column =[
					'ignore_avatar' => 'Avatar'
					, 'nama' => 'Nama'
					, 'nip' => 'NIP'
					, 'no_hp' => 'No. HP'
					, 'email' => 'Email'
					, 'username' => 'Username'
					, 'judul_role' => 'Role'
					, 'ignore_action' => 'Aksi'
				];
		$th = '';
		foreach ($column as $val) {
			$th .= '<th>' . $val . '</th>'; 
		}
		?>
		<div class="table-responsive">
			<table id="table-result" class="table display nowrap table-striped table-bordered" style="width:100%">
			<thead>
				<tr>
					<?=$th?>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<?=$th?>
				</tr>
			</tfoot>
			</table>
		</div>
		<?php
			$settings['order'] = [1,'asc'];
			$index = 0;
			foreach ($column as $key => $val) {
				$column_dt[] = ['data' => $key];
				if (strpos($key, 'ignore') !== false) {
					$settings['columnDefs'][] = ["targets" => $index, "orderable" => false];
				}
				$index++;
			}
		?>
		<span id="dataTables-column" style="display:none"><?=json_encode($column_dt)?></span>
		<span id="dataTables-setting" style="display:none"><?=json_encode($settings)?></span>
		<span id="dataTables-url" style="display:none"><?=current_url() . '/getDataDT'?></span>
	</div>
</div>