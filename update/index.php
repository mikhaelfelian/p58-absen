<?php
$base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<html>
<head>
	<title>Update Database Aplikasi Kasir Professional</title>
	<meta name="descrition" content="Update database Aplikasi Kasir Professional"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="shortcut icon" href="<?=$base_url?>public/images/favicon.png?r=1722131398" />
	<link rel="stylesheet" type="text/css" href="<?=$base_url?>public/vendors/bootstrap/css/bootstrap.min.css?r=1722131398"/>
	<link rel="stylesheet" type="text/css" href="<?=$base_url?>public/vendors/bootswatch/cosmo/bootstrap.css?r=1722131398"/>
	<style>
	body {
		font-size: 17px;
	}
	.container {
		max-width: 550px;
		margin-top: 50px;
		margin-bottom: 50px;
	}
	</style>
</head>
<body>
<div class="container">
	<div class="box-body">
		<h3 class="text-center">Update Database</h3>
		<h3 class="text-center">Aplikasi Kasir Professional</h3>
		<hr/>
		<?php
		// print_r($_POST); die;
		if (!empty($_POST['submit'])) {
			// echo 'ff'; die;
			if (empty(trim($_POST['host'])) || empty(trim($_POST['username'])) || empty(trim($_POST['nama_database']))) {
				echo '<div class="alert alert-danger">
						Host, Username, dan Nama Database harus diisi
					</div>';
			} else {
				include 'update-database.php';
				$message = update_database();
				$type = $message['status'] == 'ok' ? 'success' : 'danger';
				echo '<div class="alert alert-' . $type . '">
					' . $message['message'] .'
				</div>';
				
				if ($message['status'] == 'ok') {
					echo '
						<div class="alert alert-info">
							Silakan jalankan aplikasi, cek apakah struktur tabel pada database ' . $_POST['nama_database'] . ' sama dengan struktur tabel pada database yang didownload bersama dengan apliaksi ini.
						</div>
					</div>
					</body>
					</html>';
					exit;
				}
			}
		}
		?>
		
		<p>Aplikasi ini akan meng-<em>update</em> database aplikasi kasir professional ke versi yang terbaru.</p>
		<p>Untuk memulai proses update silakan isikan parameter database berikut:</p>
		<form method="post" action="">
			<div class="row mb-2">
				<label class="col-4">Host</label>
				<div class="col-8">
					<input type="text" name="host" class="form-control" value="localhost" required/>
				</div>
			</div>
			<div class="row mb-2">
				<label class="col-4">Username</label>
				<div class="col-8">
					<input type="text" name="username" class="form-control" value="root" required/>
				</div>
			</div>
			<div class="row mb-2">
				<label class="col-4">Password</label>
				<div class="col-8">
					<input type="password" name="password" class="form-control" value=""/>
				</div>
			</div>
			<div class="row mb-2">
				<label class="col-4">Nama Database</label>
				<div class="col-8">
					<input type="text" name="nama_database" class="form-control" value="" required/>
				</div>
			</div>
			<button class="btn btn-primary" name="submit" value="submit">Submit</button>
		</form>
	</div>
</div>
<script type="text/javascript">
$el = document.getElementById('#btn-submit');
$el.addEventListener('click', function() {
	$el.
})

</script>
</body>
</html>
