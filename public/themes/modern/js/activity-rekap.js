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
});

