<?php
echo '<div class="card">
	<div class="card-header">
		<h5 class="card-title">' . $title . '</h5>
	</div>
	<div class="card-body">';
		if (!empty($message)) {
			show_message($message);
		}
		helper ('html');
		
	$disabled = !empty($aktivasi['activation_key']) ? 'disabled="disabled"' : '';
	
	if (!empty(@$aktivasi['domain_url'])) {
		$domain_url = $aktivasi['domain_url'];
	} else {
		$domain_url = str_replace(':' . $_SERVER['SERVER_PORT'], '', $_SERVER['HTTP_HOST']);
	}
		
	$activation_key = !empty($aktivasi['activation_key']) ? $aktivasi['activation_key'] : '[ digenerate otomatis oleh sistem ]';
	$btn_delete_aktivasi = !empty($aktivasi['activation_key']) ? '<button type="button" id="btn-delete-data-aktivasi" class="btn btn-danger">Hapus Data Aktivasi</button><hr/>' : '';
	echo $btn_delete_aktivasi . '
		<form method="post" style="max-width:700px" action="" class="form-horizontal">
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-3 col-form-label">Email</label>
				<div class="col-sm-9">
					<input type="email" name="email" class="form-control" value="' . set_value('email', @$aktivasi['email']) . '" ' . $disabled . ' required/>
					<p class="fst-italic mt-2" style="line-height: 18px;font-size: 92%;">Email yang terdaftar di Jagowebdev.com</p>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-3 col-form-label">Serial Number</label>
				<div class="col-sm-9">
					<input type="text" name="serial_number" id="serial-number" class="form-control" ' . $disabled . ' value="' . set_value('serial_number', @$aktivasi['serial_number']) . '" required/>
					<p class="fst-italic mt-2" style="line-height: 18px;font-size: 92%;">Serial number diperoleh melalui menu user pada halaman Jagowebdev.com bagian Aktivasi Produk</p>
				</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-3 col-form-label">Domain</label>
				<div class="col-sm-9">';
		
					if ($is_domain_lokal) 
					{
						echo '<div>Lokal</div>';
					} else {
						echo '<div>' . base_url() . '</div>';
					}
					
					if (empty($aktivasi['activation_key'])) 
					{
						if ($is_domain_lokal) 
						{
							echo '
							<div class="alert alert-warning d-flex align-items-center mt-2">
								<i class="fa-solid fa-triangle-exclamation me-3 fs-3"></i>
								<span>Perangkat dimana aplikasi diinstall akan didaftarkan ke Jagowebdev.com untuk aktivasi produk. Penggantian perangkat akan menyebabkan <strong>aktivasi menjadi tidak valid </strong>sehingga mengakibatkan produk tidak bisa digunakan.</span>
							</div>';

						} else {
						
							echo '
							<div class="alert alert-warning d-flex align-items-center mt-2">
								<i class="fa-solid fa-triangle-exclamation me-3 fs-3"></i>
								<span>URL <strong>' . base_url() . '</strong> akan didaftarkan ke Jagowebdev.com untuk aktivasi produk, pengaturan URL ini ada di <strong>app\Config\App.php</strong>. Jika terjadi perubahan URL maka aktivasi menjadi tidak valid dan <strong>mengakibatkan produk tidak bisa digunakan.</strong></span>
							</div>';
						}
					}
					
					
				echo '</div>
			</div>
			<div class="row mb-3">
				<label class="col-sm-3 col-md-2 col-lg-3 col-xl-3 col-form-label">Activation Key</label>
				<div class="col-sm-9">
					' . $activation_key . '
				</div>
			</div>';
			if (empty($aktivasi['activation_key'])) {
				echo '
					<div class="row mb-3">
						<label class="col-sm-3 col-md-2 col-lg-3 col-xl-3 col-form-label">Perhatian</label>
						<div class="col-sm-9">
							<div class="alert alert-info d-flex align-items-center"><i class="fa-solid fa-triangle-exclamation me-3 fs-3"></i><span>Isi data sesuai dengan keadaan yang sebenarnya, sekali produk diaktivasi, data tidak dapat diubah.</span></div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-9">
							<button type="submit" name="submit" value="submit" class="btn btn-primary"><i class="fas fa-key me-2"></i>Aktivasi</button>
						</div>
					</div>';
			}
		echo '</form>
	</div>
</div>';