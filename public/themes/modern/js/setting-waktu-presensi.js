/**
* Written by: Agus Prawoto Hadi
* Year		: 2021
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	
	list_barang_terpilih = {}
	$table = $('#list-produk');
	formatNumber('.harga-satuan');
	
	let dataTables = '';
	if ($('#table-result').length) {
		const column = $.parseJSON($('#dataTables-column').html());
		const url = $('#dataTables-url').text();
		
		settings = {
			"processing": true,
			"serverSide": true,
			"scrollX": true,
			"ajax": {
				"url": url,
				"type": "POST",
				"dataSrc": function ( json ) {					
					if (json.recordsTotal > 0) {
						$('.btn-export').removeAttr('disabled');
					} else {
						$('.btn-export').attr('disabled', 'disabled');
					}
					return json.data;
				}
			},
			"columns": column,
			"initComplete": function(settings, json) {
				generate_tooltip();
			}
		}
		
		let $add_setting = $('#dataTables-setting');
		if ($add_setting.length > 0) {
			add_setting = $.parseJSON($('#dataTables-setting').html());
			for (k in add_setting) {
				settings[k] = add_setting[k];
			}
		}
		dataTables =  $('#table-result').DataTable( settings );
	}
	
	$('.select2').select2({
		'theme' : 'bootstrap-5'
	})
	
	$('body').delegate('.btn-delete', 'click', function() {
		id = $(this).attr('data-id');
		$this = $(this);
		$bootbox = bootbox.confirm({
			message: $(this).attr('data-delete-message'),
			callback: function(confirmed) {
				if (confirmed) {
					$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
					$button = $bootbox.find('button');
					$button_submit = $bootbox.find('button.bootbox-accept');
					$button.prop('disabled', true);
					$button_submit.prepend($spinner);
					$.ajax({
						type: 'POST',
						url: base_url + 'setting-waktu-presensi/ajaxDeleteData',
						data: 'id=' + id,
						dataType: 'json',
						success: function (data) {
							$bootbox.modal('hide');
							if (data.status == 'ok') {
								alert_toast('Data berhasil dihapus');
								dataTables.draw();
							} else {
								$button.prop('disabled', false);
								$spinner.remove();
								alert_icon('Error: ' + data.message);
							}
						},
						error: function (xhr) {
							alert_icon(xhr);
							console.log(xhr);
						}
					})
					return false;
				}
			},
			centerVertical: true
		});
	});
	
	$('body').delegate('.switch-aktif', 'click', function() {
		id = $(this).attr('data-id');
		$.ajax({
			type: 'POST',
			url: current_url + '/ajaxSwitchDefault',
			data: 'id=' + id,
			dataType: 'json',
			success: function (data) {
				if (data.status == 'ok') {
					// dataTables.draw();
				} else {
					alert_icon('Error !!!' + data.message);
				}
				
				dataTables.draw();
			},
			error: function (xhr) {
				alert_icon(xhr)
				console.log(xhr);
			}
		})
	})
});