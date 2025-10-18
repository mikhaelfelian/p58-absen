/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

$(document).ready(function() {
	
	// Detail page handlers (for activity-detail.php)
	if ($('.btn-approve').length || $('.btn-reject').length) {
		$('.btn-approve').on('click', function() {
			var id = $(this).data('id');
			Swal.fire({
				title: 'Konfirmasi',
				text: 'Approve activity ini?',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#28a745',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Ya, Approve!',
				cancelButtonText: 'Batal'
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						url: module_url + '/ajaxApprove',
						type: 'POST',
						data: {id: id},
						dataType: 'json',
						success: function(response) {
							if (response.status == 'ok') {
								Swal.fire('Berhasil!', response.message, 'success').then(() => {
									location.reload();
								});
							} else {
								Swal.fire('Error!', response.message, 'error');
							}
						}
					});
				}
			});
		});
		
		$('.btn-reject').on('click', function() {
			var id = $(this).data('id');
			Swal.fire({
				title: 'Reject Activity',
				input: 'textarea',
				inputLabel: 'Alasan Reject',
				inputPlaceholder: 'Masukkan alasan reject...',
				inputAttributes: {
					'aria-label': 'Masukkan alasan reject'
				},
				showCancelButton: true,
				confirmButtonColor: '#dc3545',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Ya, Reject!',
				cancelButtonText: 'Batal',
				inputValidator: (value) => {
					if (!value) {
						return 'Alasan reject harus diisi!'
					}
				}
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						url: module_url + '/ajaxReject',
						type: 'POST',
						data: {id: id, reason: result.value},
						dataType: 'json',
						success: function(response) {
							if (response.status == 'ok') {
								Swal.fire('Berhasil!', response.message, 'success').then(() => {
									location.reload();
								});
							} else {
								Swal.fire('Error!', response.message, 'error');
							}
						}
					});
				}
			});
		});
	}
	
	// DataTables initialization
	if ($('#table-result').length) {
		var column = JSON.parse($('#dataTables-column').text());
		var settings = JSON.parse($('#dataTables-setting').text());
		var url = $('#dataTables-url').text();
		
		settings.processing = true;
		settings.serverSide = true;
		settings.ajax = {
			url: url,
			type: 'POST'
		};
		settings.columns = column;
		
		var table = $('#table-result').DataTable(settings);
		
		// Approve button handler
		$('#table-result').on('click', '.btn-approve', function() {
			var id = $(this).data('id');
			
			Swal.fire({
				title: 'Konfirmasi',
				text: 'Approve activity ini?',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#28a745',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Ya, Approve!',
				cancelButtonText: 'Batal'
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						url: module_url + '/ajaxApprove',
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
						}
					});
				}
			});
		});
		
		// Reject button handler
		$('#table-result').on('click', '.btn-reject', function() {
			var id = $(this).data('id');
			
			Swal.fire({
				title: 'Reject Activity',
				input: 'textarea',
				inputLabel: 'Alasan Reject',
				inputPlaceholder: 'Masukkan alasan reject...',
				inputAttributes: {
					'aria-label': 'Masukkan alasan reject'
				},
				showCancelButton: true,
				confirmButtonColor: '#dc3545',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Reject',
				cancelButtonText: 'Batal',
				inputValidator: (value) => {
					if (!value) {
						return 'Alasan reject harus diisi!'
					}
				}
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						url: module_url + '/ajaxReject',
						type: 'POST',
						data: {id: id, reason: result.value},
						dataType: 'json',
						success: function(response) {
							if (response.status == 'ok') {
								Swal.fire('Berhasil!', response.message, 'success');
								table.ajax.reload();
							} else {
								Swal.fire('Error!', response.message, 'error');
							}
						}
					});
				}
			});
		});
	}
	
	// Initialize GLightbox for photo gallery
	if (typeof GLightbox !== 'undefined') {
		const lightbox = GLightbox({
			selector: '.glightbox'
		});
	}
});

