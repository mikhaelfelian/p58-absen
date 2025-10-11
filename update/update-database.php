<?php
ini_set('max_execute_time', 0);

function update_database () 
{

	try {
		$conn = mysqli_connect($_POST['host'], $_POST['username'], $_POST['password']);
	} catch (mysqli_sql_exception $e) {
		return ['status' => 'error', 'message' => 'Tidak dapat terhubung dengan server database.<br/>Error: ' . $e->getMessage()];
	}

	try {
		mysqli_select_db($conn, $_POST['nama_database']);
	} catch (mysqli_sql_exception $e) {
		return ['status' => 'error', 'message' => 'Tidak dapat memilih database <strong>' . $_POST['nama_database'] . '</strong>.<br/>Error: ' . $e->getMessage()];
	}
	
	## Cek role admin ##
	$sql = 'SELECT * FROM role WHERE nama_role = "admin"';
	$query = mysqli_query($conn, $sql);
	$col = mysqli_fetch_assoc($query);
	if (!$col) {
		return ['status' => 'error', 'message' => 'Role dengan nama_role admin tidak ditemukan'];
	}
	
	mysqli_begin_transaction($conn);
	
	try {
		
		## Update v1.1 ##
		$sql = 'SHOW COLUMNS FROM pembelian LIKE "%id_user_input%"';
		$query = mysqli_query($conn, $sql);
		$exists = mysqli_fetch_assoc($query);
		if (!$exists) {
			$sql = 'ALTER TABLE `pembelian`
					ADD COLUMN `id_user_input` INT UNSIGNED NULL DEFAULT NULL AFTER `status`,
					ADD COLUMN `tgl_input` DATETIME NULL DEFAULT NULL AFTER `id_user_input`,
					ADD COLUMN `id_user_update` INT NULL DEFAULT NULL AFTER `tgl_input`,
					ADD COLUMN `tgl_update` DATETIME NULL DEFAULT NULL AFTER `id_user_update`';
			$query = mysqli_query($conn, $sql);
		}
		
		$sql = 'SHOW COLUMNS FROM barang_adjusment_stok LIKE "%keterangan%"';
		$query = mysqli_query($conn, $sql);
		$exists = mysqli_fetch_assoc($query);
		if (!$exists) {
			$sql = 'ALTER TABLE `barang_adjusment_stok`
					ADD COLUMN `keterangan` TEXT NULL AFTER `tgl_adjusment_stok`';
			$query = mysqli_query($conn, $sql);
		}
		
		## Update v1.1.2 ##
		$sql = 'SHOW COLUMNS FROM file_picker LIKE "%path%"';
		$query = mysqli_query($conn, $sql);
		$exists = mysqli_fetch_assoc($query);
		if (!$exists) {
			$sql = 'ALTER TABLE `file_picker`
					 COLUMN `path` VARCHAR(255) NULL DEFAULT NULL AFTER `nama_file`';
			$query = mysqli_query($conn, $sql);
			$sql = 'UPDATE file_picker SET path = "public/files/uploads/"';
			$query = mysqli_query($conn, $sql);
		}

	} catch (mysqli_sql_exception $e) {
		mysqli_rollback($conn);
		return ['status' => 'error', 'message' => 'Query error: ' . $e->getMessage()];
	}
	
	return ['status' => 'ok', 'message' => 'Database <strong>' . $_POST['nama_database'] . '</strong> berhasil diupdate.'];
}
