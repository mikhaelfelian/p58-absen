/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

$(document).ready(function() {
	
	// DataTables initialization
	if ($('#table-result').length) {
		var column = JSON.parse($('#dataTables-column').text());
		var settings = JSON.parse($('#dataTables-setting').text());
		var url = $('#dataTables-url').text();
		
		settings.processing = true;
		settings.serverSide = false; // Use client-side processing for simplicity
		settings.ajax = {
			url: url,
			type: 'POST'
		};
		settings.columns = column;
		
		var table = $('#table-result').DataTable(settings);
		
		// Delete button handler
		$('#table-result').on('click', '.btn-delete', function() {
			var id = $(this).data('id');
			
			Swal.fire({
				title: 'Konfirmasi',
				text: 'Apakah Anda yakin ingin menghapus assignment ini?',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Ya, Hapus!',
				cancelButtonText: 'Batal'
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						url: base_url + module_url + '/ajaxDelete',
						type: 'POST',
						data: {id: id},
						dataType: 'json',
						success: function(response) {
							if (response.status == 'ok') {
								Swal.fire('Berhasil!', response.message, 'success');
								table.ajax.reload();
							} else {
								Swal.fire('Error!', response.message, 'error');
							}
						},
						error: function() {
							Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data', 'error');
						}
					});
				}
			});
		});
	}
});

