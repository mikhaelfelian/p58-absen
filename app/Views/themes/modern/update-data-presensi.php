<?php
helper('html');
?>
<div class="card">
	<div class="card-header">
		<h5 class="card-title">Update Data Presensi</h5>
	</div>
	<div class="card-body">
		<?php
		if (!empty($message)) {
			show_message($message);
		}
		?>
		<form method="post" action="" style="max-width: 750px" class="form-data">
			<div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Informasi</label>
					<div class="col-sm-9">
						<div class="alert alert-info">
						Menu ini digunakan untuk mengecek apakah ada pegawai yang tidak melakukan presensi, jika ada maka aplikasi akan menambahkan data pegawai yang tidak melakukan presensi tersebut ke tabel user_presensi.
						</div>
					</div>
				</div>
				<div class="row mb-3">
					<label class="col-sm-3 col-form-label">Periode Update</label>
					<div class="col-sm-9">
						<input class="form-control" id="daterange" value="<?=$tanggal_mulai . ' s.d. ' . date('d-m-Y')?>" name="periode_update"/>
					</div>
				</div>
				<input type="hidden" id="start-date" name="start_date" value="<?=$tanggal_mulai_db?>"/>
				<input type="hidden" id="end-date" name="end_date" value="<?=date('Y-m-d')?>"/>
				<button type="submit" class="btn btn-primary offset-sm-3" name="submit" value="submit">Submit</button>
				<input type="hidden" name="id" value="<?=@$_GET['id']?>"/>
			</div>
		</form>
	</div>
</div>