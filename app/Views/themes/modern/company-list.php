<div class="card">
	<div class="card-body">
		<div class="d-flex justify-content-between">
			<div>
				<?php if (has_permission('create')): ?>
				<a href="<?=current_url()?>/add" class="btn btn-success btn-xs"><i class="fa fa-plus pe-1"></i> Tambah Company</a>
				<?php endif; ?>
			</div>
		</div>
		<hr/>
		<?php
		if (!empty($message)) {
			show_message($message);
		}
		
		$column = [
			'ignore_urut' => '#'
			, 'nama_company' => 'Nama Company'
			, 'alamat' => 'Alamat'
			, 'contact_person' => 'Contact Person'
			, 'no_telp' => 'No. Telp'
			, 'latitude' => 'Latitude'
			, 'longitude' => 'Longitude'
			, 'radius_nilai' => 'Radius'
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

