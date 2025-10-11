<?php
helper('html');
?>
<form method="post" action="" class="form-horizontal p-3">
	<div>
		<div class="row mb-3">
			<label class="col-sm-3 col-form-label">Jabatan</label>
			<div class="col-sm-9">
				<input type="text" name="nama_jabatan" class="form-control" value="<?=set_value('nama_jabatan', @$jabatan['nama_jabatan'])?>"/>
				<input type="hidden" name="id_jabatan" value="<?=set_value('id_jabatan', @$jabatan['id_jabatan'])?>"/>
			</div>
		</div>
	</div>
	<input type="hidden" name="id" value="<?=@$_GET['id']?>"/>
</form>