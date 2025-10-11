/**
* Written by: Agus Prawoto Hadi
* Year		: 2021
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	
	$('.select2').select2({
		'theme' : 'bootstrap-5'
	});
	const column = $.parseJSON($('#dataTables-column').html());
	let url = $('#dataTables-url').text();
	
	const settings = {
        "processing": true,
        "serverSide": true,
		"scrollX": true,
		"ajax": {
            "url": url,
            "type": "POST"
		},
		
        "columns": column,
		"initComplete": function(settings, json) {
			if (json.data.length == 0) {
				$('.btn-export').attr('disabled', 'disabled');
			} else {
				$('.btn-export').removeAttr('disabled');
			}
		}
    }
	
	let $add_setting = $('#dataTables-setting');
	if ($add_setting.length > 0) {
		add_setting = $.parseJSON($('#dataTables-setting').html());
		for (k in add_setting) {
			settings[k] = add_setting[k];
		}
	}
	
	let dataTables =  $('#table-result').DataTable( settings );
	
	$('#btn-excel').click(function() {
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$spinner.prependTo($this);
		
		start_date = $('#start-date').val();
		end_date = $('#end-date').val();
		filename = 'Riwayat Presensi - ' + start_date + '_' + end_date + '.xlsx';
		url = base_url + 'presensi-riwayat/ajaxExportExcel?start_date=' + start_date + '&end_date=' + end_date + '&' + $('.form-laporan').serialize();
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
	
	$('#btn-pdf').click(function() {
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$spinner.prependTo($this);
		
		start_date = $('#start-date').val();
		end_date = $('#end-date').val();
		filename = 'Riwayat Presensi - ' + start_date + '_' + end_date + '.pdf';
		url = base_url + 'presensi-riwayat/ajaxExportPdf?start_date=' + start_date + '&end_date=' + end_date + '&ajax=true' + $('.form-laporan').serialize();
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
	
	$('#daterange').daterangepicker({
		opens: 'right',
		ranges: {
             'Hari ini': [moment(), moment()],
			 'Bulan ini': [moment().startOf('month'), moment()],
             'Tahun ini': [moment().startOf('year'), moment()],
             '7 Hari Terakhir': [moment().subtract('days', 6), moment()],
             '30 Hari Terakhir': [moment().subtract('days', 29), moment()],
             
          },
		showDropdowns: true,
		   "linkedCalendars": false,
		locale: {
			customRangeLabel: 'Pilih Tanggal',
            format: 'DD-MM-YYYY',
			applyLabel: 'Pilih',
			separator: " s.d. ",
				 "monthNames": [
				"Januari",
				"Februari",
				"Maret",
				"April",
				"Mei",
				"Juni",
				"Juli",
				"Agustus",
				"September",
				"Oktober",
				"November",
				"Desember"
			],
        }
	},	function(start, end, label) 
	{
		start_date = start.format('YYYY-MM-DD');
		end_date = end.format('YYYY-MM-DD');
		$('#start-date').val(start_date);
		$('#end-date').val(end_date);
		load_data(start_date, end_date);
	
	})
	
	$('.form-laporan').find('select').change(function() 
	{
		start_date = $('#start-date').val();
		end_date = $('#end-date').val();
		load_data(start_date, end_date);
	});
	
	function load_data(start_date, end_date) 
	{
		settings.ajax.url = base_url + 'presensi-riwayat/getDataDTPresensi?start_date=' + start_date + '&end_date=' + end_date + '&' + $('.form-laporan').serialize();
		dataTables.destroy();
		len = $('#table-result').find('thead').find('th').length;
		$('#table-result').find('tbody').html('<tr>' +
								'<td colspan="' + len + '" class="text-center">Loading data...</td>' +
							'</tr>');
		dataTables =  $('#table-result').DataTable( settings );
		
	}
	
	$('body').delegate('.btn-edit', 'click', function(e) {
		e.preventDefault();
		
		tanggal = $(this).attr('data-tanggal');
		id_user = $(this).attr('data-id-user');
		$bootbox_presensi = bootbox.dialog({
			title: 'Data Presensi',
			message: '<div class="text-center"><span class="spinner-border"></span></div>',
			buttons: {
				cancel: {
					label: 'Cancel'
				}
			}
		})
		
		$bootbox_presensi.find('.modal-dialog').css('max-width', '750px');
		$.get(base_url + 'presensi-riwayat/ajaxGetDetailPresensi?tanggal=' + tanggal + '&id_user=' + id_user, function (data) {
			$bootbox_presensi.find('.bootbox-body').html(data);
		})
	});
	
	$('body').delegate('.btn-delete', 'click', function(e) 
	{
		e.preventDefault();
		tanggal = $(this).attr('data-tanggal');
		id_user = $(this).attr('data-id-user');
		$bootbox_presensi = bootbox.dialog({
			title: 'Hapus Data Presensi',
			message: '<div class="text-center"><span class="spinner-border"></span></div>',
			buttons: {
				cancel: {
					label: 'Cancel',
					className: 'btn-secondary'
				},
				success: {
					label: 'Hapus',
					className: 'btn-danger',
					callback: function() 
					{
						$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
						$button_delete.prepend($spinner);
						$button.prop('disabled', true);
						$bootbox_presensi.find('a, button').addClass('disabled').prop('disabled', true);
						$.ajax({
							type: 'POST',
							url: base_url + 'presensi-riwayat/ajaxDeletePresensi',
							data: 'tanggal=' + tanggal + '&id_user=' + id_user,
							dataType: 'text',
							success: function (data) {
								data = JSON.parse(data);
								$button.prop('disabled', false);
								$spinner.remove();
								
								$bootbox_presensi.modal('hide');
								if (data.status == 'ok') {
									alert_toast('Data berhasil dihapus');
									dataTables.draw();
								} else {
									alert_icon('Error: ' + data.message);
								}
								return false;
							},
							error: function (xhr) {
								$bootbox_presensi.modal('hide');
								$button.prop('disabled', false);
								$spinner.remove();
								alert_icon(xhr);
								console.log(xhr);
							}
						})
						return false;
					}
				}
			}
		})
		
		$button_delete = $bootbox_presensi.find('.btn-danger');
		$button = $bootbox_presensi.find('button');
		
		$bootbox_presensi.find('.modal-dialog').css('max-width', '750px');
		$.get(base_url + 'presensi-riwayat/ajaxGetDetailPresensi?tanggal=' + tanggal + '&id_user=' + id_user, function (data) {
			$bootbox_presensi.find('.bootbox-body').html('<p>Data presensi berikut akan dihapus:</p>' + data);
		})
		
		
		return false;
		tanggal = $(this).attr('data-tanggal');
		id_user = $(this).attr('data-id-user');
		$bootbox_presensi = bootbox.dialog({
			title: 'Data Presensi',
			message: '<div class="text-center"><span class="spinner-border"></span></div>',
			buttons: {
				cancel: {
					label: 'Cancel'
					
				}
			}
		})
		
		$bootbox_presensi.find('.modal-dialog').css('max-width', '750px');
		$.get(base_url + 'presensi-riwayat/ajaxGetDetailPresensi?tanggal=' + tanggal + '&id_user=' + id_user, function (data) {
			$bootbox_presensi.find('.bootbox-body').html(data);
		})
	});
	
	$('body').delegate('.btn-delete-presensi-detail', 'click', function() 
	{
		$button = $(this);
		$button.prop('disabled', true);
		id = $button.attr('data-id');
		$spinner = $('<span class="spinner-border spinner-border-sm me-2"></span>');
		$spinner.prependTo($button);
		$.ajax({
			type: 'POST',
			url: base_url + 'presensi-detail/ajaxDeleteData',
			data: 'id=' + id,
			dataType: 'json',
			success: function (data) {
				$button.prop('disabled', false);
				$spinner.remove();
				
				$bootbox_presensi.modal('hide');
				if (data.status == 'ok') {
					alert_toast('Data berhasil dihapus');
					dataTables.draw();
				} else {
					alert_icon('Error: ' + data.message);
				}
				return false;
			},
			error: function (xhr) {
				$bootbox_presensi.modal('hide');
				$button.prop('disabled', false);
				$spinner.remove();
				alert_icon(xhr);
				console.log(xhr);
			}
		})
	});
});