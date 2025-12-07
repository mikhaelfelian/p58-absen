$(document).ready(function() {

	

	// Initialize Select2

	if ($.fn.select2) {

		$('.select2').select2({

			theme: 'bootstrap-5',

			width: '100%'

		});

	}

	

	// Export Excel

	$('#btn-export-excel').click(function() {

		const start_date = $('#start_date').val();

		const end_date = $('#end_date').val();

		const id_user = $('#id_user').val() || '';

		const id_company = $('#id_company').val() || '';

		

		if (!start_date || !end_date) {

			Swal.fire({

				icon: 'warning',

				title: 'Perhatian',

				text: 'Tanggal mulai dan selesai harus diisi'

			});

			return;

		}

		

		// Build URL

		let url = base_url + 'activity-rekap/ajaxExportExcel?ajax=true';

		url += '&start_date=' + start_date;

		url += '&end_date=' + end_date;

		if (id_user) url += '&id_user=' + id_user;

		if (id_company) url += '&id_company=' + id_company;

		

		// Show loading

		Swal.fire({

			title: 'Mengunduh...',

			html: 'Mohon tunggu, sedang membuat file Excel',

			allowOutsideClick: false,

			didOpen: () => {

				Swal.showLoading();

			}

		});

		

		// Download file

		fetch(url)

			.then(response => {

				if (!response.ok) {

					throw new Error('Network response was not ok');

				}

				return response.blob();

			})

			.then(blob => {

				// Create download link

				const url = window.URL.createObjectURL(blob);

				const a = document.createElement('a');

				a.href = url;

				a.download = 'Rekap_Activity_' + start_date + '_to_' + end_date + '.xlsx';

				document.body.appendChild(a);

				a.click();

				window.URL.revokeObjectURL(url);

				document.body.removeChild(a);

				

				Swal.close();

				

				Swal.fire({

					icon: 'success',

					title: 'Berhasil!',

					text: 'File Excel berhasil diunduh',

					timer: 2000,

					showConfirmButton: false

				});

			})

			.catch(error => {

				console.error('Error:', error);

				Swal.fire({

					icon: 'error',

					title: 'Error',

					text: 'Gagal mengunduh file Excel'

				});

			});

	});

	

	// Date validation

	$('#end_date').change(function() {

		const start_date = $('#start_date').val();

		const end_date = $(this).val();

		

		if (start_date && end_date && end_date < start_date) {

			Swal.fire({

				icon: 'warning',

				title: 'Perhatian',

				text: 'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai'

			});

			$(this).val(start_date);

		}

	});

	

	// Function to update selected count and button state
	function updateSelectedCount() {
		const selectedCheckboxes = $('.activity-checkbox:checked');
		const selectedCount = selectedCheckboxes.length;
		$('#selected-count').text(selectedCount);
		
		// Enable/disable approve button
		if (selectedCount > 0) {
			$('#btn-approve-selected').prop('disabled', false);
		} else {
			$('#btn-approve-selected').prop('disabled', true);
		}
		
		// Update select all checkbox state
		const totalPending = $('.activity-checkbox').length;
		if (totalPending > 0) {
			$('#select-all-checkbox').prop('checked', selectedCount === totalPending);
			$('#select-all-checkbox').prop('indeterminate', selectedCount > 0 && selectedCount < totalPending);
		}
	}
	
	// Select All checkbox handler
	$('#select-all-checkbox').change(function() {
		const isChecked = $(this).prop('checked');
		$('.activity-checkbox').prop('checked', isChecked);
		updateSelectedCount();
	});
	
	// Individual checkbox handler
	$(document).on('change', '.activity-checkbox', function() {
		updateSelectedCount();
	});
	
	// Initialize selected count on page load
	updateSelectedCount();
	
	// Approve Selected button handler
	$('#btn-approve-selected').click(function() {
		// Get all selected activity IDs
		const selectedIds = [];
		$('.activity-checkbox:checked').each(function() {
			selectedIds.push($(this).val());
		});
		
		if (selectedIds.length === 0) {
			Swal.fire({
				icon: 'warning',
				title: 'Perhatian',
				text: 'Pilih activity yang akan diapprove'
			});
			return;
		}
		
		Swal.fire({
			title: 'Konfirmasi',
			text: 'Approve ' + selectedIds.length + ' activity yang dipilih?',
			icon: 'question',
			showCancelButton: true,
			confirmButtonColor: '#28a745',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Ya, Approve!',
			cancelButtonText: 'Batal'
		}).then((result) => {
			if (result.isConfirmed) {
				// Show loading
				Swal.fire({
					title: 'Memproses...',
					html: 'Mohon tunggu, sedang approve activity',
					allowOutsideClick: false,
					didOpen: () => {
						Swal.showLoading();
					}
				});
				
				// Send AJAX request with selected IDs
				$.ajax({
					url: base_url + 'activity-rekap/ajaxApproveAll',
					type: 'POST',
					data: {
						activity_ids: selectedIds
					},
					dataType: 'json',
					success: function(response) {
						if (response.status == 'ok') {
							Swal.fire({
								icon: 'success',
								title: 'Berhasil!',
								text: response.message,
								confirmButtonText: 'OK'
							}).then(() => {
								// Reload page to show updated statuses
								location.reload();
							});
						} else {
							Swal.fire({
								icon: 'error',
								title: 'Error!',
								text: response.message
							});
						}
					},
					error: function(xhr, status, error) {
						console.error('Error:', error);
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: 'Terjadi kesalahan saat approve activity'
						});
					}
				});
			}
		});
	});

});



