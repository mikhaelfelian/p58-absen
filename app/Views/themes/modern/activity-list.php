<div class="card">
	<div class="card-body">
		<?php
		if (!empty($message)) {
			show_message($message);
		}
		
		$column = [
			'ignore_urut' => '#'
			, 'nama' => 'Nama Pegawai'
			, 'nama_company' => 'Company'
			, 'tanggal' => 'Tanggal'
			, 'waktu' => 'Waktu'
			, 'judul_activity' => 'Judul Activity'
			, 'foto_activity' => 'Foto'
			, 'status' => 'Status'
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
			$settings['order'] = [3,'desc'];
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

