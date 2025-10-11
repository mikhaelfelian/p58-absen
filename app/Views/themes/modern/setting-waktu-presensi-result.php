<div class="card">
	<div class="card-header">
		<h5 class="card-title"><?=$current_module['judul_module']?></h5>
	</div>
	<?php
	helper('html');
	?>
	<div class="card-body">
		<a href="<?=current_url()?>/add" class="btn btn-success btn-xs"><i class="fa fa-plus pe-1"></i> Tambah Data</a>
		<hr/>
			<?php 
		if (!empty($message)) {
			show_alert($message);
		}
			
		$column =[
					'ignore_urut' => 'No'
					, 'nama_setting' => 'Nama Setting'
					, 'batas_waktu_masuk' => 'Batas Waktu Masuk'
					, 'batas_waktu_pulang' => 'Batas Waktu Pulang'
					, 'ignore_batas_presensi_masuk' => 'Periode Presensi Masuk'
					, 'ignore_batas_presensi_pulang' => 'Periode Presensi Pulang'
					, 'ignore_switch' => 'Gunakan'
					, 'ignore_action' => 'Action'
				];
		
		$settings['order'] = [1,'asc'];
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
		<table id="table-result" class="table display table-striped table-bordered table-hover" style="width:100%">
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
		<?php
			foreach ($column as $key => $val) {
				$column_dt[] = ['data' => $key];
			}
		?>
		<span id="dataTables-column" style="display:none"><?=json_encode($column_dt)?></span>
		<span id="dataTables-setting" style="display:none"><?=json_encode($settings)?></span>
		<span id="dataTables-url" style="display:none"><?=current_url() . '/getDataDT'?></span>
	</div>
</div>