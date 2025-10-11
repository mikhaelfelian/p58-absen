<div class="card">
	<div class="card-header">
		<h5 class="card-title"><?=$current_module['judul_module']?></h5>
	</div>
	
	<div class="card-body">
		<div class="text-center text-sm-start">
			<a href="<?=current_url()?>/add" class="btn btn-success btn-xs btn-add"><i class="fa fa-plus pe-1"></i> Tambah Data</a>
			<button class="btn btn-danger btn-delete-all-jabatan btn-xs" <?=$jml_jabatan ? '' : 'disabled'?>><i class="fas fa-trash me-2"></i>Hapus Semua Jabatan</button>
		</div>
		<hr/>
		<div style="max-width:500px" id="jabatan-container">
			
		</div>
	</div>
</div>