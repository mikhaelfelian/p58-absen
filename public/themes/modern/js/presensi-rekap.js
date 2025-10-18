/**
* Written by: Agus Prawoto Hadi
* Year		: 2021
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	
	$('.select2').select2({
		'theme' : 'bootstrap-5'
	});
	
	$('#btn-excel').click(function() {
		val_bulan = $('select[name="bulan"]').val();
		bulan = $('select[name="bulan"]').find('option[value="' + val_bulan + '"]').text();
		val_tahun = $('select[name="tahun"]').val();
		tahun = $('select[name="tahun"]').find('option[value="' + val_tahun + '"]').text();
		
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$spinner.prependTo($this);
		
		filename = 'Rekap Presensi - ' + bulan + ' ' + tahun + '.xlsx';
		url = base_url + 'presensi-rekap/ajaxExportExcel?' + $('.form-laporan').serialize();
		fetch(url)
		  .then(resp => resp.blob())
		  .then(blob => {
				$this.prop('disabled', false);
				$spinner.remove();
				saveAs(blob, filename);
		  })
		.catch((xhr) => {
			$this.prop('disabled', false);
			$spinner.remove();
			console.log(xhr);
			alert('Ajax Error')
			
		});
	})
});