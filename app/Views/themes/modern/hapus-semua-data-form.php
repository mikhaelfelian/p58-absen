<div class="card">
	<div class="card-header">
		<h5 class="card-title"><?=$title?></h5>
	</div>
	
	<div class="card-body">
		<?php 
			helper ('html');
		if (!empty($message)) {
			show_message($message);
		}

		?>
		<form method="post" action="" id="form-setting" enctype="multipart/form-data">
			<div>
				<div class="row mb-3">
					<div class="col-sm-12">
						<p>Hapus semua data pada database "<strong><?=$nama_database?></strong>", tabel:</p>
						<ul class="list-circle">
							<?php
							foreach ($list_table as $val) {
								echo '<li>'.$val.'</li>';
							}
							?>
						</ul>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-5">
						<button type="button" id="btn-delete-all-data" value="submit" class="btn btn-danger">Hapus Semua Data</button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>