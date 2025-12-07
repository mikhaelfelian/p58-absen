/**
* Written by: Agus Prawoto Hadi
* Year		: 2021
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	
	$('.select2').select2({
		'theme' : 'bootstrap-5'
	});
	
	$('.flatpickr').flatpickr(optionFlatpickr);
	let settings = '';
	if ($('#table-result').length) {
		const column = $.parseJSON($('#dataTables-column').html());
		let url = $('#dataTables-url').text();
		
		settings = {
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
				
				let lightbox = GLightbox();
			},
			drawCallback: function (settings) {
				let lightbox = GLightbox();
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
	
	$('select[name="jenis_foto"]').change(function() {
		if (this.value == 'upload') {
			if (Webcam.loaded) {
				Webcam.reset();
			}
			$('#upload-image-container').show();
			$('#webcam-container').hide();
		} else {
			Webcam.set({
				width: 320,
				height: 240,
				image_format: 'jpeg',
				jpeg_quality: 100,
				flip_horiz: true
			});
			Webcam.attach( '#webcam' );
			
			Webcam.on( 'live', function() {
				$('#btn-ambil-photo').prop('disabled', false);
			});
			
			Webcam.on( 'error', function(err) {
				alert_icon('Webcam error: ' + err);
			} );
			
			$('#upload-image-container').hide();
			$('#webcam-container').show();
		}
	})
	
	$('#btn-ambil-photo').click(function() {
		$this = $(this);
		Webcam.snap( function(data_uri) {
			// display results in page
			$('#photo-result').html('<img src="'+data_uri+'">').show();
			$('#webcam').hide();
			$this.hide();
			$('#btn-ambil-ulang-photo').show();
			$('#btn-submit-presensi').prop('disabled', false);
			$('.photo-raw').html(data_uri);
			data.photo = data_uri;
		} );
		// stream.getTracks().forEach(track => track.stop())
	})
	
	$('#btn-ambil-ulang-photo').click(function() {
		$('#btn-ambil-photo').show();
		$('#webcam').show();
		$('#photo-result').hide();
		$('#btn-submit-presensi').prop('disabled', true);
		$(this).hide();
		
	})
	
	$('select[name="jenis_presensi"]').change(function() {
		$option_status = $('select[name="status"]');
		$option_status.find('option').show();
		$option_status.val('');
		if (this.value == 'masuk') {
			$option_status.find('option[value="pulang_sebelum_waktunya"]').hide();
			$option_status.find('option[value="terlambat_masuk_dan_pulang_sebelum_waktunya"]').hide();
		} else if (this.value == 'pulang') {
			$option_status.find('option[value="terlambat_masuk"]').hide();
			$option_status.find('option[value="terlambat_masuk_dan_pulang_sebelum_waktunya"]').hide();
		}
	})
	
	// Button Delete
	$('body').delegate('.btn-del-presensi', 'click', function(e) {
		e.preventDefault();
		$this = $(this);
		id = $this.attr('data-id');
		$bootbox = bootbox.confirm({
			message: $this.attr('data-delete-message'),
			callback: function(confirmed) {
				let $button = $bootbox.find('button').prop('disabled', true);
				let $button_submit = $bootbox.find('button.bootbox-accept');
				if (confirmed) {
					$spinner = $('<span class="spinner-border spinner-border-sm me-2"></span>');
					$spinner.prependTo($button_submit);
					$.ajax({
						type: 'POST',
						url: current_url + '/ajaxDeleteData',
						data: 'id=' + id,
						dataType: 'json',
						success: function (data) {
							$button.prop('disabled', false);
							$spinner.remove();
							
							$bootbox.modal('hide');
							if (data.status == 'ok') {
								alert_toast('Data berhasil dihapus');
								dataTables.draw();
							} else {
								alert_icon('Error: ' + data.message);
							}
							return false;
						},
						error: function (xhr) {
							$bootbox.modal('hide');
							$button.prop('disabled', false);
							$spinner.remove();
							alert_icon(xhr);
							console.log(xhr);
						}
					})
					
					return false;
				}
			},
			centerVertical: true
		});
	})
	
	$('#btn-excel').click(function() {
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$spinner.prependTo($this);
		
		start_date = $('#start-date').val();
		end_date = $('#end-date').val();
		filename = 'Detail Presensi - ' + start_date + '_' + end_date + '.xlsx';
		url = base_url + 'presensi-detail/ajaxExportExcel?start_date=' + start_date + '&end_date=' + end_date + '&' + $('.form-laporan').serialize();
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
	
	$('#btn-pdf').click(function() 
	{
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$spinner.prependTo($this);
		
		start_date = $('#start-date').val();
		end_date = $('#end-date').val();
		filename = 'Detail Presensi - ' + start_date + '_' + end_date + '.pdf';
		url = base_url + 'presensi-detail/ajaxExportPdf?start_date=' + start_date + '&end_date=' + end_date + '&ajax=true' + '&' + $('.form-laporan').serialize();
		// console.log(url); return;
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
		settings.ajax.url = base_url + 'presensi-detail/getDataDTPresensi?start_date=' + start_date + '&end_date=' + end_date + '&' + $('.form-laporan').serialize();
		dataTables.destroy();
		len = $('#table-result').find('thead').find('th').length;
		$('#table-result').find('tbody').html('<tr>' +
								'<td colspan="' + len + '" class="text-center">Loading data...</td>' +
							'</tr>');
		dataTables =  $('#table-result').DataTable( settings );
	}
	
	// Image Upload
	function bytesToSize(bytes) {
		var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
		if (bytes == 0) return 'n/a';
		var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
		return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
	};
	
	$('body').delegate('.remove-img', 'click', function(e) {
		$container = $(this).parent().parent().parent();
		input_file_name = $container.find('.file').attr('name');
		
		$(this).parent().parent().remove();
		$('.' + input_file_name + '-delete-img').val(1);
	});
	
	$('body').delegate('.file', 'change', function(e) 
	{
		file = this.files[0];
		$this = $(this);
		
		$this.parent().find('.alert-danger').remove();
		$upload_file = $this.parent().children('.upload-file-thumb');
		
		$upload_file.find('img').remove();
		$upload_file.find('.file-prop').empty();
		$upload_file.hide();
		if ($this.val() == '')
			return false;
		
		name = $this.attr('name');
		max_size = 1024 * 1024 * 2;
		$max_size_elm = $('.' + name + '-max-size');
		if ($max_size_elm.length > 0) {
			max_size = parseInt($max_size_elm.val());
		}

		var reader = new FileReader();

		// Closure to capture the file information.
		reader.onload = (function(e) {
			
			// Render thumbnail.
			// $upload_file.find('.file-prop').before(thumb);
			if (file.type == 'image/png' || file.type == 'image/jpg' || file.type == 'image/jpeg') {
				var img = new Image;
				img.src = reader.result;
				img.onload = function() {
					var thumb = '<img class="thumb" src="' + e.target.result +
								'" title="' + escape(file.name) + '"/>';
					$upload_file.find('.file-prop').before(thumb);
					var file_prop = '<ul class="m-0 p-0"><li><small>Name: ' + file.name + '</small></li><li><small>Size: ' + file_size + '</small></li><li><small>Dimension (W x H): ' + img.width + 'px X ' + img.height + 'px</small></li><li><small>Type: ' + file.type + '</small></li></ul>';
					$upload_file.show().find('.file-prop').html(file_prop);
				};
			}
		});
		
		reader.readAsDataURL(file); 
		size = file.size;
		
		file_size = size + ' Bytes';
		if (size > 1024 * 1024) {
			file_size = parseFloat(size / (1024 * 1024)).toFixed(2) + ' Mb';
		} else if (size > 1024) {
			file_size = parseFloat(size / 1024).toFixed(2) + ' Kb';
		}
		
		if (size > max_size) {
			$('<small class="alert alert-danger mt-1" style="display:block">Ukuran file maksimal: ' + bytesToSize(max_size) + ', file Anda ' + file_size + '</small>').insertBefore($upload_file);
			return;
		}
		
		/* if (file.type != 'application/vnd.ms-excel' 
				&& file.type != 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
				&& file.type != 'application/pdf'
				&& file.type != 'application/msword'
				&& file.type != 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
			) {
			$('<small class="alert alert-danger">Tipe file yang diperbolehkan: .doc, .docx, .xls, .xlsx, dan .pdf</small>').insertAfter($this);
			return;
		} */
		// console.log($upload_file.attr('class'));
		/* var file_prop = '<ul><li><small>Name: ' + file.name + '</small></li><li><small>Size: ' + file_size + '</small></li><li><small>Type: ' + file.type + '</small></li></ul>';
		$upload_file.show().find('span').html(file_prop); */
	});
	//-- Upload Image
	
	// Map
	if ($('#setting-presensi').length) {
		setting = JSON.parse($('#setting-presensi').text());

		latitude = $('#latitude').val();
		if (!latitude) {
			latitude = setting.latitude;
			$('#latitude').val(setting.latitude);
		}
		
		longitude = $('#longitude').val();
		if (!longitude) {
			longitude = setting.longitude;
			$('#longitude').val(setting.longitude);
		}
		
		let map = L.map('map', {
			center: [latitude, longitude],
			zoom: 13
		});
		// map.attributionControl.addAttribution('Jagowebdev.com');
		
		map.attributionControl.setPrefix('Jagowebdev.com');

		L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
		}).addTo(map);

		let marker = L.marker(map.getCenter()).addTo(map);
		let decimalPlaces = 6;
		function centerMarkerOnMap(event) {
			marker.setLatLng(event.target.getCenter());
			latlng = marker.getLatLng();
			$('#latitude').val(latlng.lat.toFixed(decimalPlaces));
			$('#longitude').val(latlng.lng.toFixed(decimalPlaces));
		}
		
		$('#latitude, #longitude').keyup(function() {
			this.value = this.value.replace(/[^0-9.-]/g, '');
			map.setView([$('#latitude').val(), $('#longitude').val()]);
		})

		map.on('move', centerMarkerOnMap);
	}
});