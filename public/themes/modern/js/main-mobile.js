let flatpickr_instance = '';
let osRightPanel = '';
let toastTimer = '';
let $toast = '';
let processing_page = false;
let optionFlatpickr = {
	enableTime: false,
	dateFormat: "d-m-Y",
	time_24hr: true,
	locale: "id"
}
bootbox.setDefaults({
    animate: false
});

function addBtnConfig() 
{
	$filter = $('#tabel-data_filter');
	if ( $('#setting-barang').length == 0) {
		$filter.append('<button class="btn btn-outline-secondary px-4" style="border-right:0" id="btn-kategori-barang"><i class="far fa-folder"></i></button><button class="btn-config btn btn-outline-secondary px-4" id="btn-setting-barang"><i class="fas fa-cog"></i></button>');
	}
}

function addBtnConfigInvoice() 
{
	$filter = $('#tabel-data_filter');
	if ( $('#setting-tampilan-invoice').length == 0) {
		$filter.append('<button class="btn btn-outline-secondary btn-setting-searchbar" id="setting-tampilan-invoice"><i class="fas fa-cog"></i></button>');
	}
}

function rightPanelOverlayScrollbar() {
	if (osRightPanel) {
		osRightPanel.destroy();
	}
	osRightPanel =  OverlayScrollbars( $('.right-panel-body'), {scrollbars : {autoHide: 'leave', autoHideDelay: 100}} );
}

function destroyFlatpickr() {
	if (flatpickr_instance) {
		if (flatpickr_instance.length == undefined) {
			flatpickr_instance.destroy();
		} else {
			flatpickr_instance.map(function (instance) {
				instance.destroy();
			})
		}
	}
}


const dataTables_settings = 
{
	"processing": true,
	"serverSide": true,
	"scrollX": true,
	"ajax": {
		"url": '',
		"type": "POST",
		
	},
	"columns": '',
	'initComplete': function() {
		$('#tabel-data_wrapper').find('.tabel-data').css('opacity', 1);
		$('.dataTables_scrollBody').overlayScrollbars({ scrollbars : {autoHide: 'leave', autoHideDelay: 100}  });
		$('input[type="search"]').focus();
	},
	 "bLengthChange": false,
	"bFilter": true,
	"bInfo": false,
	"fixedHeader": false,
	"language": { search: '', searchPlaceholder: "Cari..." },
	"sDom": "<'row'<'col-sm-12'<'form-group'<f>>>>tr<'row'<'col-sm-12'<'pull-left'i><'pull-right'p><'clearfix'>>>"
	// "dom": '<"row"<"col-sm-4"l><"col-sm-4 text-center"p><"col-sm-4"f>>tip'
}

function loadDataTables(url) 
{
	const column = $.parseJSON($('#dataTables-column').html());
	dataTables_settings.ajax.url = url
	dataTables_settings.columns = column
	dataTables_settings.searching = true
	
	let $add_setting = $('#dataTables-setting');
	dataTables_settings.columnDefs = [];
	if ($add_setting.length > 0) {
		add_setting = $.parseJSON($('#dataTables-setting').html());
		for (k in add_setting) {
			dataTables_settings[k] = add_setting[k];
		}
	}
	
	dataTables_settings.drawCallback =  function( settings ) 
	{
		let $search = $('input[type="search"]');
		
		setting = {};
		// setting.jumlah_digit_barcode = 13;
		if ($('#setting-kasir').length) {
			setting = JSON.parse($('#setting-kasir').text());
		}
		
		if ($search.length) {
			let search = $search.val();
			if (search.length == parseInt(setting.jumlah_digit_barcode)) {
				
				$detail = $('.detail-barang');
				if ($detail.length == 1) {
					$detail.trigger('click');
					$search.val('').focus().trigger('keyup');
				} else {
					bootbox.alert('Barang tidak ditemukan');
				}
			}
		}
    }
	
	dataTables_settings.searchDelay = 250;
	// console.log(dataTables_settings);
	dataTables =  $('#tabel-data').DataTable( dataTables_settings );
	$filter = $('#tabel-data_filter');
	$input = $filter.find('input').eq(0);
	$filter.find('input').find('label').remove();
	$filter.find('label').hide();
	
	$filter.addClass('input-group flex-nowrap shadow-sm');
	$filter.append($input);
	
	$parent = $filter.parent();
	$parent.css('display', 'flex');
	
	
	if ($('#page-type').val() == 'kasir') {
		addBtnConfig();
	}
	
	if ($('#page-type').val() == 'invoice') {
		addBtnConfigInvoice();
	}
		
	if ($parent.find('.btn-close-panel').length == 0) {
		$filter.append('<button class="btn btn-danger btn-close-panel rounded-1 ms-2" style="width:45px; height:40px; display:none; box-shadow: none;"><i class="fas fa-times"></i></button>');
	}
	
	$('.dataTables_paginate').parent().parent().parent().addClass('px-4');
	$('.dataTables_paginate').parent().parent().addClass('px-0');
	
	$("div.dataTables_filter input").unbind();
	
	cariBarang = '';
	notifikasi = '';
	
	after_searching_barcode = false;
	$("div.dataTables_filter input").keyup( function (e) {
		search_value = this.value;
		let $this = $(this);
		clearTimeout(cariBarang);
		if (setting.jumlah_digit_barcode == search_value.length) 
		{
			/* $.get(base_url + 'pos-kasir/getBarangByBarcode?barcode=' + search_value, function(data) {
				alert();
			}) */
			addItem(barang_with_barcode[search_value]);
			$this.val('');
			after_searching_barcode = true;
			return;
		}
		// return;
		if (search_value == '' && after_searching_barcode && $('#data-barang-tidak-ditemukan').length == 0) {
			return;
		}
		
		cariBarang = setTimeout(function() {
			after_searching_barcode = false;
			dataTables.search( search_value ).draw();
		}, dataTables_settings.searchDelay);
		
	});
}

let show_login_page = false;
$(document).ajaxStart(function() { Pace.restart(); });
$(document).ajaxSuccess(function(event, request, settings) {
	if (request.getResponseHeader('required-auth') == '1') {
		// document.write('');
		if ( !show_login_page ) {
			let url = base_url + 'login';
			window.location = base_url;
			history.pushState( url,'',url);
			show_login_page = true;
		}
	}
});

function nama_hari() {
	return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
}

function nama_bulan() {
	return ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
}

// Placeholder
function placeholder(param = {}) 
{
	classes = param.classes != undefined ? param.classes : '';
	style = '';
	if (param.style != undefined) {
		for (k in param.style) {
			style += k + ':' + param.style[k] + ';';
		}
	}
	
	return '<div class="ssc-square ' + classes + '" style="' + style + ';background:#d6e0ed;border-radius:10px"></div>';
}
// Untuk tombol spa dan HISTORY browser
function loadContent(param, callback = false) 
{
	current_url = window.location.protocol + '//' + window.location.host + window.location.pathname;
	if (param.url == current_url + window.location.search) {
		processing_page = false;
		return false;
	}
	
	history.pushState( param.url,'', param.url);
// console.log(current_url);
	$('.navbar-footer').find('.active').removeClass('active');
	$('.navbar-footer').find('a[href="' + param.url + '"]').addClass('active');
	
	if (param.placeholder != undefined) 
	{
		if (param.placeholder == 'presensi-riwayat') {
			$('#page-content').empty();
			height = $('body').height();
			height = height - 23 - 78 - 72;
			num = Math.floor(height / 118);
			html = '<div class="container mt-3">' +
					'<div class="flex mbs">' 
						+ placeholder({classes:'mt-3 mb-3', style: {'height' : '22px', 'width' : '200px', 'margin' : 'auto'}})
						+ placeholder({classes:'mb-4', style: {'height' : '77px'}})
						for (i = 1; i < num; i++) {
							html += placeholder({classes:'mb-2', style: {'height' : '110px'}});
						}					
						
			html += '</div>' +
				'</div>';
			
		} else if (param.placeholder == 'presensi-home') {
			html = '<div class="container mt-4">'
				+ placeholder({classes:'mt-2 mb-2', style: {'height' : '20px', 'width' : '200px', 'margin' : 'auto'}})
				+ placeholder({classes:'mt-0 mb-0', style: {'height' : '17px', 'width' : '150px', 'margin' : 'auto'}})
				+ placeholder({classes:'mt-4 mb-4', style: {'height' : '66px'}})
				+ '<div class="row mb-4">'
					+ '<div class="col-6 pe-2">'
						+ placeholder({style: {'height' : '122.5px'}})
					+ '</div>'
					+ '<div class="d-flex col-6 ps-2">'
						+ placeholder({style: {'height' : '122.5px'}})
					+ '</div>'
				+ '</div>';
				
				if (setting.gunakan_radius_lokasi == 'Y') {
					html += placeholder({classes:'mb-4', style: {'height' : '66px'}})
				}
				
				html += placeholder({classes:'mb-4', style: {'height' : '22px', 'width' : '200px'}})
				
				for (i = 1; i < 4; i++) {
					html += placeholder({classes:'mb-2', style: {'height' : '100px'}});
				}
			html += '</div>';
		} else if (param.placeholder == 'user-profil' || param.placeholder == 'ubah-password') {
			$('#page-content').empty();
			
			height = $('body').height();
			height = parseInt(height) - 53 - 60 - 152;
			html = '<div class="container mt-4">'
					+ placeholder({classes: 'mt-4 mb-2', style: {'height' : '22px', 'width': '120px'}})
					+ placeholder({style: {'height' : height + 'px'}})
					+ '</div>'
		}
		
		$('#page-content').html(html);
	}

	url = param.url;
	$.get(url, function(data) 
	{
		$html = $('<div>');
		$html.append(data);
		
		$new_content = $html.find('.container');

		$('script[data-type="dynamic-resource-head"], link[data-type="dynamic-resource-head"]').remove();
		$resources = $html.find('[data-type="dynamic-resource-head"]');
		$resources.appendTo($('head'));
		
		$('#page-content').html($new_content);
		processing_page = false;

		if (callback) {
			callback();
		}
		
		rightPanelOverlayScrollbar();
	});
}

window.addEventListener('popstate', function(e) {
	if (e.state) {
		loadContent(e.state);
	}
});

history.pushState( window.location.href,'',window.location.href);

function toast_mobile(message) {
	$toast = $('<div class="toast align-items-center text-bg-secondary border-0 start-50 translate-middle-x ps-3 pe-3" role="alert" aria-live="assertive" aria-atomic="true" style="display: block;position: fixed;bottom: 0;width:auto">' +
			'<div class="d-flex">' +
				'<div class="toast-body text-nowrap">' +
					message +
				'</div>' +
			'</div>' +
		'</div>');
	$toast.animate({bottom:100}, 400, function() {
		setTimeout(function () {
			$toast.animate({bottom:0}, 400, function() {
				$toast.remove();
			})
		}, 2000)
	});
	$('body').append($toast);
}

$(document).ready(function() {
	
	$(document).undelegate('.link-spa', 'click').delegate('.link-spa', 'click', function(e) {
		e.preventDefault();

		if (processing_page) {
			return;
		}
		
		if ($(this).hasClass('active')) {
			return;
		}
		
		processing_page = true;
		if (flatpickr_instance) {
			if (flatpickr_instance.length == undefined) {
				flatpickr_instance.destroy();
			} else {
				flatpickr_instance.map(function (instance) {
					instance.destroy();
				})
			}
		}
		
		offcanvas.hide();

		url = $(this).attr('href');
				
		data_placeholder = $(this).attr('data-placeholder');
		loadContent({url:url, placeholder: data_placeholder});
	});

	
	// Setting
	setting = {};
	if ($('#setting-presensi').length) {
		setting = JSON.parse($('#setting-presensi').text());
	}
	
	// Geolocation
	options = {enableHighAccuracy: true, timeout: 5000, maximumAge:0};
	let geolocation = {};
	
	success = function(pos) {
		geolocation = pos;		
		if (setting.gunakan_radius_lokasi == 'Y') 
		{
			dist = getDistance(setting.latitude, setting.longitude, geolocation.coords.latitude, geolocation.coords.longitude);
			radius = parseInt(setting.radius_nilai);
			if (setting.radius_satuan == 'km') {
				radius = radius * 1000;
			}
			dist = dist * 1000;
			if (radius < dist) {
				$alert = $('<div class="alert alert-danger d-flex align-items-center mt-4"><i class="bi bi-x-circle fs-1 me-3"></i>Lokasi Anda diluar radius lokasi absen yang diperbolehkan. Radius lokasi absen adalah ' + setting.radius_nilai + setting.radius_satuan + ' dari kantor (' + setting.latitude + ', ' + setting.longitude + ')</div>'); 
			} else {
				$alert = $('<div class="alert alert-success d-flex align-items-center mt-4"><i class="bi bi-check-circle fs-1 me-3"></i>Lokasi Anda berada di dalam radius lokasi absen</div>'); 
			}
			
			if ($('#alert-lokasi').length) {
				$alert.appendTo($('#alert-lokasi'));
				$('#alert-lokasi').show();
			}
		}
	}
	error = function(err) {
		if (options.enableHighAccuracy) {
    		options.enableHighAccuracy = false;
    		navigator.geolocation.getCurrentPosition( success, error, options);
		} else {
		    alert_icon('Error: ' + err.message);
    		console.log(err);
		}
	}
	navigator.geolocation.getCurrentPosition( success, error, options);
	
	function getDistance(lat1, long1, lat2, long2) {
		let theta = long1 - long2;
		let distance = 60 * 1.1515 * (180/Math.PI) * Math.acos(
			Math.sin(lat1 * (Math.PI/180)) * Math.sin(lat2 * (Math.PI/180)) + 
			Math.cos(lat1 * (Math.PI/180)) * Math.cos(lat2 * (Math.PI/180)) * Math.cos(theta * (Math.PI/180))
		);
		// kilometer
		return distance * 1.609344;
	}
		
	$('#btn-presensi').click(function(e){
		e.preventDefault();
		
		// Check if today is a working day
		var today = new Date().getDay(); // 0 = Sunday, 1 = Monday, etc.
		var hariKerja = typeof companySetting !== 'undefined' && companySetting && companySetting.hari_kerja ? companySetting.hari_kerja : [1,2,3,4,5];
		
		// Ensure integer comparison
		var isWorkingDay = hariKerja.some(function(day) {
			return parseInt(day) === parseInt(today);
		});
		
		if (!isWorkingDay) {
			Swal.fire({
				icon: 'info',
				title: 'Hari Libur',
				text: 'Anda tidak bisa absen di hari libur. Presensi hanya dapat dilakukan pada hari kerja.',
				confirmButtonText: 'OK'
			});
			return false;
		}
		
		date = new Date();
		jam_sekarang = ("0" + date.getHours()).substr(-2);
		menit_sekarang = ("0" + date.getMinutes()).substr(-2);
		detik_sekarang = ("0" + date.getSeconds()).substr(-2);
		waktu_sekarang = jam_sekarang + ':' + menit_sekarang + ':' + detik_sekarang;
		if (waktu_sekarang < setting.waktu_masuk_akhir) {
			presensi('masuk');
		} else {
			presensi('pulang');
		}
	})
	
	$('body').undelegate('#presensi-masuk', 'click');
	$('body').undelegate('#presensi-pulang', 'click');
	
	btn_clicked = '';
	$('body').delegate('#presensi-pulang, #presensi-masuk', 'click', function(e) {
		$this = $(this);
		e.preventDefault();
		btn_clicked =  $this.attr('id');
		const jenis_presensi = btn_clicked == 'presensi-masuk' ? 'masuk' : 'pulang';
		presensi(jenis_presensi);
	})
		
	$bootbox_presensi = '';
	
	// Presensi Flash Control Variables (Global scope for access from attachWebcam)
	var presensiVideoTrack = null;
	var presensiFlashMode = 'auto';
	var presensiTorchSupported = false;
	
	// Presensi Flash Control Functions (Global scope for access from attachWebcam)
	function hidePresensiFlashControl(message) {
		if (!$bootbox_presensi || !$bootbox_presensi.length) {
			return;
		}
		const wrapper = $bootbox_presensi.find('#presensi-flash-control-wrapper');
		if (!wrapper.length) {
			return;
		}
		wrapper.hide();
		if (message) {
			$bootbox_presensi.find('#presensi-flash-support-text').text(message);
		}
	}
	
	function setupPresensiFlashControl() {
		if (!$bootbox_presensi || !$bootbox_presensi.length) {
			console.warn('setupPresensiFlashControl: bootbox_presensi not available');
			return;
		}
		const wrapper = $bootbox_presensi.find('#presensi-flash-control-wrapper');
		if (!wrapper.length) {
			console.warn('setupPresensiFlashControl: flash control wrapper not found');
			return;
		}
		
		// Always show the wrapper initially
		wrapper.show();
		
		// Check if video track is available
		if (!presensiVideoTrack) {
			console.warn('setupPresensiFlashControl: presensiVideoTrack not available yet');
			$bootbox_presensi.find('#presensi-flash-support-text').text('Menunggu kamera siap...');
			// Disable buttons until video track is ready
			wrapper.find('.flash-toggle-presensi').prop('disabled', true);
			return;
		}
		
		// Enable buttons
		wrapper.find('.flash-toggle-presensi').prop('disabled', false);
		
		// Check for torch capability with better error handling
		let capabilities = {};
		try {
			if (presensiVideoTrack.getCapabilities) {
				capabilities = presensiVideoTrack.getCapabilities();
				console.log('setupPresensiFlashControl: torch capabilities:', capabilities);
			} else if (presensiVideoTrack.getSettings) {
				// Fallback to getSettings if getCapabilities is not available
				const settings = presensiVideoTrack.getSettings();
				console.log('setupPresensiFlashControl: torch settings:', settings);
				// Some browsers expose torch in settings
				if (settings.torch !== undefined) {
					capabilities.torch = settings.torch;
				}
			}
		} catch (error) {
			console.warn('setupPresensiFlashControl: Error checking capabilities:', error);
		}
		
		presensiTorchSupported = !!capabilities.torch;
		console.log('setupPresensiFlashControl: torch supported:', presensiTorchSupported);
		
		if (!presensiTorchSupported) {
			// Show wrapper with message instead of hiding
			$bootbox_presensi.find('#presensi-flash-support-text').text('Lampu tidak tersedia di perangkat ini.');
			// Disable buttons if torch not supported
			wrapper.find('.flash-toggle-presensi').prop('disabled', true);
			return;
		}
		
		// Torch is supported, show helpful message and enable controls
		$bootbox_presensi.find('#presensi-flash-support-text').text('Sesuaikan lampu saat mengambil foto.');
		updatePresensiFlashButtons();
		applyPresensiFlashMode(presensiFlashMode);
	}
	
	function updatePresensiFlashButtons() {
		if (!$bootbox_presensi || !$bootbox_presensi.length) {
			return;
		}
		const wrapper = $bootbox_presensi.find('#presensi-flash-control-wrapper');
		if (!wrapper.length) {
			return;
		}
		wrapper.find('.flash-toggle-presensi').removeClass('active');
		wrapper.find(`.flash-toggle-presensi[data-flash-mode="${presensiFlashMode}"]`).addClass('active');
	}
	
	function applyPresensiFlashMode(mode) {
		if (!presensiVideoTrack || !presensiTorchSupported) {
			return;
		}
		if (mode === 'auto') {
			presensiVideoTrack.applyConstraints({ advanced: [{ torch: false }] }).catch(() => {});
			return;
		}
		const torchOn = mode === 'on';
		presensiVideoTrack.applyConstraints({ advanced: [{ torch: torchOn }] }).catch(err => {
			console.warn('Failed to set presensi torch:', err);
			if (typeof Swal !== 'undefined') {
				Swal.fire('Info', 'Tidak dapat mengubah lampu di perangkat ini.', 'info');
			}
		});
	}
	
	function presensi(jenis_presensi) {
		
		// Check if today is a working day
		var today = new Date().getDay(); // 0 = Sunday, 1 = Monday, etc.
		var hariKerja = typeof companySetting !== 'undefined' && companySetting && companySetting.hari_kerja ? companySetting.hari_kerja : [1,2,3,4,5];
		
		// Ensure integer comparison
		var isWorkingDay = hariKerja.some(function(day) {
			return parseInt(day) === parseInt(today);
		});
		
		if (!isWorkingDay) {
			Swal.fire({
				icon: 'info',
				title: 'Hari Libur',
				text: 'Anda tidak bisa absen di hari libur. Presensi hanya dapat dilakukan pada hari kerja.',
				confirmButtonText: 'OK'
			});
			return false;
		}
		
		// Check if a company was manually selected or if company is set (from active shift)
		var selectedCompanyId = $('#selected-company-id').val() || $('#id_company').val();
		var isManualSelection = $('#selected-company-id').val() ? true : false;
		var hasCompanySelected = $('#id_company').val() ? true : false;
		var usingCompanyLocationFallback = false;
		
		// Helper function to get company location from assignedCompanies array
		function getCompanyLocationFromArray(companyId) {
			if (typeof assignedCompanies === 'undefined' || !assignedCompanies || !Array.isArray(assignedCompanies)) {
				return null;
			}
			
			for (var i = 0; i < assignedCompanies.length; i++) {
				var company = assignedCompanies[i];
				var companyIdToCompare = company.id_company;
				if (companyIdToCompare == companyId) {
					return {
						latitude: company.latitude || null,
						longitude: company.longitude || null
					};
				}
			}
			return null;
		}
		
		// Check if geolocation is available, if not try to get from detected fields or get current location
		if (geolocation.coords == undefined) {
			// Try to get location from detected-latitude/detected-longitude fields (set by GPS or manual selection)
			var detectedLat = $('#detected-latitude').val();
			var detectedLng = $('#detected-longitude').val();
			
			if (detectedLat && detectedLng) {
				// Construct geolocation object from detected coordinates
				geolocation = {
					coords: {
						latitude: parseFloat(detectedLat),
						longitude: parseFloat(detectedLng)
					}
				};
			} else if (isManualSelection || hasCompanySelected) {
				// Manual selection or active shift without GPS - use company location as fallback
				var companyLat = $('#selected-company-lat').val();
				var companyLng = $('#selected-company-lng').val();
				
				// If selected-company-lat/lng are not set, try to get from assignedCompanies array
				if ((!companyLat || !companyLng) && selectedCompanyId) {
					var companyLocation = getCompanyLocationFromArray(selectedCompanyId);
					if (companyLocation && companyLocation.latitude && companyLocation.longitude) {
						companyLat = companyLocation.latitude;
						companyLng = companyLocation.longitude;
						// Also set the hidden fields for future use
						var latField = $('#selected-company-lat');
						var lngField = $('#selected-company-lng');
						if (latField.length) latField.val(companyLat);
						if (lngField.length) lngField.val(companyLng);
					}
				}
				
				if (companyLat && companyLng) {
					// Use company location as fallback for manual selection or active shift
					geolocation = {
						coords: {
							latitude: parseFloat(companyLat),
							longitude: parseFloat(companyLng)
						}
					};
					usingCompanyLocationFallback = true;
					console.log('Using company location as fallback:', companyLat, companyLng);
				} else {
					// Try to get current GPS location as last resort
					if (navigator.geolocation) {
						alert_icon('Mendapatkan lokasi GPS...');
						navigator.geolocation.getCurrentPosition(
							function(position) {
								geolocation = position;
								// Retry presensi after getting location
								presensi(jenis_presensi);
							},
							function(error) {
								// GPS failed but company is selected - try to get company location from array
								var companyLat = $('#selected-company-lat').val();
								var companyLng = $('#selected-company-lng').val();
								
								// If not in hidden fields, try to get from assignedCompanies array
								if ((!companyLat || !companyLng) && selectedCompanyId) {
									var companyLocation = getCompanyLocationFromArray(selectedCompanyId);
									if (companyLocation && companyLocation.latitude && companyLocation.longitude) {
										companyLat = companyLocation.latitude;
										companyLng = companyLocation.longitude;
									}
								}
								
								if (companyLat && companyLng) {
									geolocation = {
										coords: {
											latitude: parseFloat(companyLat),
											longitude: parseFloat(companyLng)
										}
									};
									usingCompanyLocationFallback = true;
									// Retry presensi with company location
									presensi(jenis_presensi);
								} else {
									alert_icon('Lokasi GPS tidak tersedia. Silakan aktifkan GPS atau pilih perusahaan secara manual.');
								}
							},
							{enableHighAccuracy: true, timeout: 5000, maximumAge: 0}
						);
						return;
					} else {
						// No geolocation support - use company location if available
						var companyLat = $('#selected-company-lat').val();
						var companyLng = $('#selected-company-lng').val();
						
						// If not in hidden fields, try to get from assignedCompanies array
						if ((!companyLat || !companyLng) && selectedCompanyId) {
							var companyLocation = getCompanyLocationFromArray(selectedCompanyId);
							if (companyLocation && companyLocation.latitude && companyLocation.longitude) {
								companyLat = companyLocation.latitude;
								companyLng = companyLocation.longitude;
							}
						}
						
						if (companyLat && companyLng) {
							geolocation = {
								coords: {
									latitude: parseFloat(companyLat),
									longitude: parseFloat(companyLng)
								}
							};
							usingCompanyLocationFallback = true;
						} else {
							alert_icon('Lokasi GPS tidak tersedia. Silakan aktifkan GPS atau pilih perusahaan secara manual.');
							return;
						}
					}
				}
			} else {
				// If no detected location and no company selected, try to get current GPS location
				// But first check if company is set (from active shift) and get its location
				if (hasCompanySelected && selectedCompanyId) {
					var companyLocation = getCompanyLocationFromArray(selectedCompanyId);
					if (companyLocation && companyLocation.latitude && companyLocation.longitude) {
						geolocation = {
							coords: {
								latitude: parseFloat(companyLocation.latitude),
								longitude: parseFloat(companyLocation.longitude)
							}
						};
						usingCompanyLocationFallback = true;
						console.log('Using company location from active shift:', companyLocation.latitude, companyLocation.longitude);
					} else if (navigator.geolocation) {
						// Try GPS as fallback
						alert_icon('Mendapatkan lokasi GPS...');
						navigator.geolocation.getCurrentPosition(
							function(position) {
								geolocation = position;
								// Retry presensi after getting location
								presensi(jenis_presensi);
							},
							function(error) {
								// GPS failed - if company is selected, use company location
								if (hasCompanySelected && selectedCompanyId) {
									var companyLocation = getCompanyLocationFromArray(selectedCompanyId);
									if (companyLocation && companyLocation.latitude && companyLocation.longitude) {
										geolocation = {
											coords: {
												latitude: parseFloat(companyLocation.latitude),
												longitude: parseFloat(companyLocation.longitude)
											}
										};
										usingCompanyLocationFallback = true;
										presensi(jenis_presensi);
									} else {
										alert_icon('Lokasi harus diaktifkan. Pastikan GPS/Lokasi diaktifkan di browser Anda.');
									}
								} else {
									alert_icon('Lokasi harus diaktifkan. Pastikan GPS/Lokasi diaktifkan di browser Anda.');
								}
							},
							{enableHighAccuracy: true, timeout: 5000, maximumAge: 0}
						);
						return;
					} else {
						// No geolocation support - use company location if available
						var companyLocation = getCompanyLocationFromArray(selectedCompanyId);
						if (companyLocation && companyLocation.latitude && companyLocation.longitude) {
							geolocation = {
								coords: {
									latitude: parseFloat(companyLocation.latitude),
									longitude: parseFloat(companyLocation.longitude)
								}
							};
							usingCompanyLocationFallback = true;
						} else {
							alert_icon('Lokasi harus diaktifkan');
							return;
						}
					}
				} else if (navigator.geolocation) {
					// No company selected - try GPS
					alert_icon('Mendapatkan lokasi GPS...');
					navigator.geolocation.getCurrentPosition(
						function(position) {
							geolocation = position;
							// Retry presensi after getting location
							presensi(jenis_presensi);
						},
						function(error) {
							alert_icon('Lokasi harus diaktifkan. Pastikan GPS/Lokasi diaktifkan di browser Anda.');
						},
						{enableHighAccuracy: true, timeout: 5000, maximumAge: 0}
					);
					return;
				} else {
					alert_icon('Lokasi harus diaktifkan');
					return;
				}
			}
		}
		
		// No time range validation - users can clock in/out at ANY time
		// Validation is based solely on duration >= jam_kerja_target
		
		// Only validate radius if GPS location is actually available (not using company location fallback)
		if (setting.gunakan_radius_lokasi == 'Y' && !usingCompanyLocationFallback && geolocation.coords) {
			dist = getDistance(setting.latitude, setting.longitude, geolocation.coords.latitude, geolocation.coords.longitude);
			radius = parseInt(setting.radius_nilai);
			if (setting.radius_satuan == 'km') {
				radius = radius * 1000;
			}
			dist = dist * 1000;
			if (radius < dist) {
				alert_icon('Lokasi Anda diluar radius lokasi absen yang diperbolehkan. Radius lokasi absen adalah ' + setting.radius_nilai + setting.radius_satuan + ' dari kantor (' + setting.latitude + ', ' + setting.longitude + ')');
				return;
			} else {
				// alert_icon('Lokasi Anda berada di dalam radius lokasi absen');
				// return;
			}
		} else if (setting.gunakan_radius_lokasi == 'Y' && usingCompanyLocationFallback) {
			// Manual selection without GPS - skip radius validation since we can't verify actual location
			console.log('Skipping radius validation for manual selection without GPS');
		}
				
		// Get selected company ID (already retrieved above, but ensure it's set)
		if (!selectedCompanyId) {
			selectedCompanyId = $('#selected-company-id').val() || $('#id_company').val();
		}
		
		data = {
			'location' : geolocation, 
			'jenis_presensi' : jenis_presensi, 
			'foto' : '',
			'id_company': selectedCompanyId
		};
		
		/* r = Math.floor(Math.random() * (64 - 16 + 1)) + 32;
		p = Array.from(
				window.crypto.getRandomValues(new Uint8Array(Math.ceil(r / 2))),
				(b) => ("0" + (b & 0xFF).toString(16)).slice(-2)
			).join("");
		
		data = await JsAesPhp.encrypt(data, p) + p + r; */
		hari = nama_hari();
		bulan = nama_bulan();
		hari_tanggal = hari[moment().day()] + ', ' + moment().format('DD') + ' ' + bulan[moment().month()] + ' ' + moment().year();
		
		waktu = new Date();
		jam = "0" + waktu.getHours();
		menit = "0" + waktu.getMinutes();
		detik = "0" + waktu.getSeconds();
		
		$bootbox_presensi = bootbox.dialog({
			message: '<div class="text-center mt-2 mb-3">' + 
						'<div class="mb-2 header-container">' + 
							'<p class="m-0 fw-bold">PRESENSI ' + jenis_presensi.toUpperCase() + '</p><hr/>' + 
							'<p class="m-0">' + hari_tanggal + '</p>' + 
							'<p class="live-jam">' + jam.substr(-2) + ':' + menit.substr(-2) + ':' + detik.substr(-2) + '</p>' + 
						'</div>'+
						
						'<div id="video-container" class="mb-3" style="position:relative; display:none;">'+
							'<video id="video" autoplay></video>' +
							'<div id="webcam-loader">' +
								'<div id="webcam-spinner" class="text-center rounded-4" style="position:absolute;width:60px;height:60px;background:#fff9c0;left:calc(50% - 30px);top:calc(50% - 45px);padding-top:15px;color:#f19f00">' + 
									'<div class="spinner-border"></div>' + 
								'</div>' + 
								'<p style="position:absolute;bottom: 25px;margin: auto;width: 100%;">Memuat kamera...</p>' +
							'</div>' + 
						'</div>'+
						'<div id="presensi-container" style="position:relative; display:none">'+
							'<div class="spinner-border"></div>' + 
							'<p class="mt-2">' + 'Memproses presensi</p>' +
						'</div>'+
						'<div id="canvas-container" class="mb-3" style="position:relative;display:none">' + 
							'<canvas id="canvas"></canvas>' + 
							'<button class="btn btn-warning text-light" id="btn-ambil-ulang-foto" style="position:absolute;right:10px;bottom:12px">' + 
								'<i class="fas fa-rotate-left"></i>' + 
							'</button>' + 
							'<div id="foto-raw" style="display:none"></div>' +
						'</div>' +
						'<div id="presensi-flash-control-wrapper" class="mb-3" style="display:none;">' +
							'<div class="d-flex align-items-center gap-2 justify-content-center">' +
								'<small class="text-muted me-2">Flash:</small>' +
								'<div class="btn-group" role="group">' +
									'<button type="button" class="btn btn-sm btn-outline-secondary flash-toggle-presensi active" data-flash-mode="auto">' +
										'<i class="fas fa-adjust me-1"></i>Auto' +
									'</button>' +
									'<button type="button" class="btn btn-sm btn-outline-secondary flash-toggle-presensi" data-flash-mode="on">' +
										'<i class="fas fa-lightbulb me-1"></i>On' +
									'</button>' +
									'<button type="button" class="btn btn-sm btn-outline-secondary flash-toggle-presensi" data-flash-mode="off">' +
										'<i class="fas fa-lightbulb me-1"></i>Off' +
									'</button>' +
								'</div>' +
							'</div>' +
							'<small id="presensi-flash-support-text" class="text-muted d-block mt-1 text-center"></small>' +
						'</div>' +
						'<button type="button" class="btn btn-success" id="btn-ambil-foto" style="display:none" disabled>Ambil Foto</button>' +
						'<button type="button" class="btn btn-primary" id="btn-submit-presensi" style="display:none">Submit</button>' +
					'</div>',
			closeButton: false
		});
				
		setInterval(function(){ 
			waktu = new Date();
			jam = "0" + waktu.getHours();
			menit = "0" + waktu.getMinutes();
			detik = "0" + waktu.getSeconds();
			$('.live-jam').html(jam.substr(-2) + ':' + menit.substr(-2) + ':' + detik.substr(-2));
			
		}, 1000);
		
		if (setting.gunakan_foto_selfi == 'Y') {
			
			$('#video-container, #btn-ambil-foto, #presensi-flash-control-wrapper').show();
			
			$btn_close = $('<button type="button" class="bootbox-close-button btn-close" aria-hidden="true" style="position: absolute;right: 10px;top: 10px;z-index:99999"></button>')
			$bootbox_presensi.find('.modal-content').prepend($btn_close);

			// Presensi flash button click handlers
			$bootbox_presensi.on('click', '.flash-toggle-presensi', function() {
				const mode = $(this).data('flash-mode');
				if (!mode) {
					return;
				}
				if (!presensiTorchSupported) {
					if (typeof Swal !== 'undefined') {
						Swal.fire('Info', 'Lampu tidak tersedia di perangkat ini.', 'info');
					}
					return;
				}
				presensiFlashMode = mode;
				updatePresensiFlashButtons();
				applyPresensiFlashMode(mode);
			});

			attachWebcam();
			$('#btn-submit-presensi').click(function(){
				$bootbox_presensi.find('button').prop('disabled', true);
				$(this).prepend('<span class="spinner-border spinner-border-sm me-2">');
				data.foto = $('#foto-raw').text();
				saveData(data);
			});
			
		} else {
			
			$('#presensi-container').show();
			saveData(data);
		}
	};
	
	let video = '';
	async function attachWebcam() {
		
		navigator.mediaDevices.getUserMedia({video: true})
		.then((stream) => 
		{
			camera_dimension = stream.getVideoTracks()[0].getSettings();
			webcam_container_width = $('.modal-dialog').width() - 40;
			webcam_container_height = camera_dimension.height * (webcam_container_width / camera_dimension.width);
			
			// Store video track for flash control
			presensiVideoTrack = stream.getVideoTracks()[0];
			console.log('attachWebcam: presensiVideoTrack set, attempting to setup flash control');
			
			// Try to setup flash control immediately after getting the track
			// This ensures it's available as soon as possible
			setupPresensiFlashControl();
			
			video = document.getElementById("video");
			video.srcObject  = stream;
			video.onloadedmetadata = () => {
				$('#video').width(webcam_container_width);
				video.play();
				$('#webcam-loader').remove();
				$('#btn-ambil-foto').prop('disabled', false);
				// Setup flash control after video is ready
				setupPresensiFlashControl();
			};
			
			$('#canvas').attr('width', webcam_container_width);
			$('#canvas').attr('height', webcam_container_height);
			$('#btn-ambil-foto').click(function() {
				
				let canvas = document.getElementById("canvas");
				canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
				$('#canvas-container').show();
				$('#video').hide();
				$('#btn-ambil-foto').hide();
				$('#btn-submit-presensi').show();
				let image_data_url = canvas.toDataURL('image/jpeg');
				$('#foto-raw').text(image_data_url);
			});
			
			$('#btn-ambil-ulang-foto').click(function() {
				$('#btn-ambil-foto').show();
				$('#video').show();
				$('#canvas-container').hide();
				$('#btn-submit-presensi').hide();
			})
			
			$('.bootbox-close-button').click(function() {
				tracks = video.srcObject.getTracks();
				tracks[0].stop();
				// Reset presensi flash control
				presensiVideoTrack = null;
				hidePresensiFlashControl();
			});
			
		})
		.catch((err) => {
			$('#webcam-loader').remove();
			alert_icon('Gagal memuat kamera, cek console browser');
			console.log(err);
		});
	}

	function saveData(data)
	{
		$.ajax({
			url: base_url + 'mobile-presensi-home/ajaxSaveData',
			type: 'post',
			data: 'data=' + btoa(JSON.stringify(data)),
			success: function(data) {
				data = JSON.parse(data);
				if (data.status == 'ok') 
				{
					$bootbox_presensi.modal('hide');
					if (video) {
						tracks = video.srcObject.getTracks();
						tracks[0].stop();
					}
					// Reset presensi flash control
					presensiVideoTrack = null;
					hidePresensiFlashControl();
					// Update waktu presensi if btn_clicked is set
					if (btn_clicked && btn_clicked !== '') {
					$(`#${btn_clicked}`).find('.waktu-presensi').text(data.data.waktu);
					}
					toast_mobile('<i class="bi bi-check-circle me-2"></i>Data berhasil disimpan');

					// Refresh presensi buttons and history sections via AJAX
					// Beri sedikit jeda agar user sempat melihat toast
					setTimeout(function() {
						refreshPresensiSections();
					}, 800);
					/* let $bootbox_timer = bootbox.dialog({
						message: '<div class="text-center mt-4 mb-4"><div class="mb-2 fs-1 text-success"><i class="far fa-circle-check"></i></div><p class="mb-4">Data presensi ' + data.data.jenis_presensi + ' berhasil disimpan</p></div>',
						closeButton: false
					});
					
					$bootbox_timer.find('.modal-content').addClass('ms-3 me-3');
					$bootbox_timer.find('.modal-body').addClass('p-0');
					$bootbox_timer.find('.modal-body').prepend('<div class="timer-bar bg-warning" style="height:4px;width:100%;opacity:0.7">'); */
					
					/* const timerInterval = setInterval(timerBar, 1);
					function timerBar() 
					{
						const date = new Date();
						currWidth = parseInt($('.timer-bar').width());
						$('.timer-bar').width(currWidth - 1);
						if (currWidth < 2) {
							clearInterval(timerInterval);
							$bootbox_timer.modal('hide');
							$('#presensi-' + data.data.jenis_presensi).find('.waktu-presensi').text(data.data.waktu)
						}
					} */
				} else {
					if ($('#btn-submit-presensi').length) {
						$bootbox_presensi.find('button').prop('disabled', false);
						$bootbox_presensi.find('.spinner-border').remove();
					}
					alert_icon(data.message);
				}
			},
			error: function (xhr) {
				if ($('#btn-submit-presensi').length) {
					$bootbox_presensi.find('button').prop('disabled', false);
					$bootbox_presensi.find('.spinner-border').remove();
				}
				alert_icon(xhr);
				console.log(xhr);
			}
		})
	}
	
	/**
	 * Refresh presensi buttons and history sections via AJAX
	 * Replaces full page reload with partial refresh for better UX
	 */
	function refreshPresensiSections() {
		// Show loading indicators
		var $buttonsContainer = $('#presensi-buttons-container');
		var $historyContainer = $('#presensi-history-container');
		
		if ($buttonsContainer.length) {
			$buttonsContainer.html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div></div>');
		}
		
		if ($historyContainer.length) {
			$historyContainer.html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Memuat...</span></div></div>');
		}
		
		// Fetch updated sections
		$.ajax({
			url: base_url + 'mobile-presensi-home/ajaxRefreshSections',
			type: 'get',
			success: function(response) {
				try {
					var data = typeof response === 'string' ? JSON.parse(response) : response;
					
					if (data.status === 'ok') {
						// Update buttons section
						if ($buttonsContainer.length && data.buttons_html) {
							$buttonsContainer.html(data.buttons_html);
						}
						
						// Update history section
						if ($historyContainer.length && data.history_html) {
							$historyContainer.html(data.history_html);
						}
					} else {
						console.error('Failed to refresh sections:', data);
						// Fallback to full reload on error
						window.location.reload();
					}
				} catch (e) {
					console.error('Error parsing refresh response:', e);
					// Fallback to full reload on error
					window.location.reload();
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX error refreshing sections:', error);
				// Fallback to full reload on error
				window.location.reload();
			}
		});
	}
	
	$('#user-menu-nav-header').click(function() {
		img_src = $(this).find('img').attr('src');
		user_detail = JSON.parse($('#user-detail').text());
		// bootbox.alert('oke');
		menu_user = '<div>'+
						'<div class="d-flex align-items-center">' + 
							'<img src="' + img_src + '" style="width:48px;height:48px;border-radius:50%;margin-right:10px"/>' +
							'<div>' + 
								'<h5 class="m-0 p-0">' + $('#profil-user-sidebar').children().eq(0).text() + '</h5>' +
								'<p class="mt-1 mb-0">' + $('#profil-user-sidebar').children().eq(1).text() + '</p>' +
							'</div>' + 
						'</div><hr/>' +
						'<ul class="list-menu-user mb-0">' + 
							'<li>' + 
								'<a class="d-flex align-items-center link-spa link-popup" data-placeholder="user-profil" href="' + base_url + 'builtin/user/edit?mobile=true">' +
									'<i class="bi bi-person-vcard me-2 fs-3"></i>' + 
									'<span>Profil</span>' + 
								'</a>' + 
							'</li>' + 
							'<li>' + 
								'<a class="d-flex align-items-center link-spa link-popup" data-placeholder="ubah-password" href="' + base_url + 'builtin/user/edit-password?mobile=true">' +
									'<i class="bi bi-key me-2 fs-3"></i>' + 
									'<span>Ubah Password</span>' + 
								'</a>' + 
							'</li>' + 
							'<li>' + 
								'<a class="d-flex align-items-center link-spa link-popup" href="' + base_url + 'login/logout?mobile=true">' +
									'<i class="bi bi-box-arrow-right me-2 fs-3"></i>' + 
									'<span>Logout</span>' + 
								'</a>' + 
							'</li>' + 
						'</ul>' +
					'</div>';
					
		$bootbox_popup = bootbox.dialog({
			title: '',
			message: menu_user,
			buttons: {
				cancel: {
					label: 'Close'
				}
			}
		})
		
		$('body').delegate('.link-popup', 'click', function() {
			$bootbox_popup.modal('hide');
		});
	})

	$.extend( $.fn.dataTable.defaults, {
		"language": {
			"processing": '<span><span class="spinner-border text-primary" role="status"></span></span>',
			"previous": "Prev"
		}
	});

	bootbox.setDefaults({
		animate: false,
		centerVertical : true
	});
	
	let offcanvas_el = document.getElementById("offcanvasExample");
	let offcanvas = new bootstrap.Offcanvas(offcanvas_el);
		
	$('#close-sidebar').click(function() {
		offcanvas.hide();
	});
	
	if ($('#dataTables-url').length) {
		
		let query_string = '';
		let add_btn_config = false;
		
		if ($('#page-type').val() == 'kasir') {
			if (setting_kasir.item_layout == 'grid') {
				dataTables_settings.pageLength = setting_kasir.item_layout_grid_length;
			} else {
				dataTables_settings.pageLength = 10;
			}
						
			query_string = '&id_gudang=' + $('#id-gudang').val() + '&id_jenis_harga=' + $('#id-jenis-harga').val();
			add_btn_config = true;
		}
				
		url = $('#dataTables-url').text() + query_string;
		loadDataTables(url, add_btn_config);
	}
		
	$(document).delegate('.number', 'keyup', function () {
		this.value = format_ribuan(this.value);
	})
	
	$('.sidebar-mobile').find('.nav-link').click(function() {
		if (processing_page) {
			return false;
		}
		$('.navbar-footer').find('.active').removeClass('active');
	})
	
	$('.navbar-footer').find('.nav-link').click(function() {
		$this = $(this);
		if ($this.hasClass('nav-menu-mobile')) {
			return;
		}
		if (processing_page) {
			return false;
		}
		/* $('.navbar-footer').find('.active').removeClass('active');
		$this.addClass('active'); */
	});

	$('#btn-logout').click(function(e){
		$btn_logout = $(this);
		$('.offcanvas-header').find('.btn-cole').trigger('click');
		if (logout_tanpa_input_kas_akhir) {
			$btn_logout.next().click();
		} else {
			bootbox.dialog({
				message: 'Logout tanpa input kas akhir?',
				buttons: {
					cancel: { label: 'Cancel' },
					success : {
						label: 'Logout',
						callback: function() {
							$btn_logout.next().click();
						}
					}
				}
			})
		}
	})
	
	// Patrol functionality
	initPatrolFunctionality();
})

// Patrol functionality
function initPatrolFunctionality() {
	// Detect if running on mobile device
	const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
	
	let qrScanner = null;
	let currentCompanyId = null;
	let companyPatrolMap = (typeof assignedPatrols !== 'undefined' && assignedPatrols) ? assignedPatrols : {};
	let patrolOptions = [];
	let patrolStatusMap = {};
	let selectedPatrolId = null;
	let forcedScannerTargetId = null;
	let currentStep = 1;
	let scannedPatrolData = null;
	let activityCameraStream = null;
	let activityCameraFacingMode = 'environment'; // 'environment' or 'user'
	let activityPhotos = []; // Array to store multiple photos
	let nextPatrolInfo = null;
	const stepperPills = $('#activity-stepper .step-pill');
	const qrInlineWrapper = $('#qr-inline-wrapper');
	const qrModalPlaceholder = $('#qr-modal-placeholder');
	const qrInlineHomeSlot = $('<div id="qr-inline-home-slot"></div>');
	const patrolAccordionCollapse = $('#patrol-progress-collapse');
	const patrolAccordionWrapper = $('#patrol-status-table-wrapper');
	const photoEvidenceCard = $('#photo-evidence-card');
	const floatingPhotoBtn = $('#btn-floating-photo');
	const scrollToPhotoBtn = $('#btn-scroll-to-photo');
	const descriptionField = $('#deskripsi_activity');
	const descriptionCollapse = $('#description-collapse');
	const flashControlWrapper = $('#flash-control-wrapper');
	const cameraStatusText = $('#camera-status-text');
	let flashMode = 'auto';
	let torchSupported = false;
	let cameraVideoTrack = null;
	let lastSuccessfulFacingMode = activityCameraFacingMode;
	let accordionCollapsedByCamera = false;
	let wasAccordionOpenBeforeCamera = false;
	let photoHighlightTimeout = null;
	let isQrInModal = false;
	const qrFlashControlWrapper = $('#qr-flash-control-wrapper');
	let qrScannerFlashMode = 'auto';
	let qrScannerTorchSupported = false;
	let qrScannerVideoTrack = null;
	let presensiFlashMode = 'auto';
	let presensiTorchSupported = false;
	let presensiVideoTrack = null;
	
	// QR Scanner timing metrics
	let qrScannerStartTime = null;
	let ocrFallbackTimer = null; // Timer for OCR fallback after 1 second
	let tesseractWorker = null; // Tesseract.js worker instance
	
	// Mobile debugging
	let mobileDebugEnabled = false;
	let mobileDebugLogs = [];
	const maxDebugLogs = 50;
	
	// Verify Tesseract.js is loaded
	function verifyTesseractLoaded() {
		if (typeof Tesseract === 'undefined') {
			console.error('[MOBILE DEBUG] Tesseract.js not loaded!');
			mobileDebugLog('ERROR: Tesseract.js library not found. OCR fallback will not work.');
			return false;
		}
		console.log('[MOBILE DEBUG] Tesseract.js loaded successfully');
		mobileDebugLog('Tesseract.js library loaded');
		return true;
	}
	
	// Mobile debug logging function
	function mobileDebugLog(message, type = 'info') {
		const timestamp = new Date().toLocaleTimeString();
		const logEntry = {
			timestamp: timestamp,
			message: message,
			type: type
		};
		mobileDebugLogs.push(logEntry);
		if (mobileDebugLogs.length > maxDebugLogs) {
			mobileDebugLogs.shift(); // Remove oldest log
		}
		
		// Update debug panel if visible
		if (mobileDebugEnabled) {
			updateMobileDebugPanel();
		}
		
		// Always log to console
		const prefix = type === 'error' ? '[MOBILE DEBUG ERROR]' : type === 'warning' ? '[MOBILE DEBUG WARN]' : '[MOBILE DEBUG]';
		console.log(prefix, message);
	}
	
	// Update mobile debug panel
	function updateMobileDebugPanel() {
		const panel = $('#mobile-debug-panel');
		if (!panel.length) return;
		
		const logsHtml = mobileDebugLogs.slice(-20).reverse().map(log => {
			const icon = log.type === 'error' ? 'fa-exclamation-circle text-danger' : 
			            log.type === 'warning' ? 'fa-exclamation-triangle text-warning' : 
			            'fa-info-circle text-info';
			return `<div class="small mb-1">
				<i class="fas ${icon} me-1"></i>
				<span class="text-muted">[${log.timestamp}]</span> ${log.message}
			</div>`;
		}).join('');
		
		panel.find('.debug-logs').html(logsHtml || '<div class="text-muted small">No logs yet...</div>');
	}
	
	// Toggle mobile debug panel
	function toggleMobileDebug() {
		mobileDebugEnabled = !mobileDebugEnabled;
		const panel = $('#mobile-debug-panel');
		if (mobileDebugEnabled) {
			if (!panel.length) {
				// Create debug panel
				const debugPanel = $(`
					<div id="mobile-debug-panel" class="position-fixed bottom-0 start-0 end-0 bg-dark text-white p-3" style="max-height: 200px; overflow-y: auto; z-index: 9999; display: none; border-top: 2px solid #0d6efd;">
						<div class="d-flex justify-content-between align-items-center mb-2">
							<strong><i class="fas fa-bug me-2"></i>Mobile Debug Panel</strong>
							<div>
								<button class="btn btn-sm btn-outline-light me-2" onclick="location.reload(true)">
									<i class="fas fa-sync me-1"></i>Force Reload
								</button>
								<button class="btn btn-sm btn-outline-light" onclick="$('#mobile-debug-panel').hide(); mobileDebugEnabled = false;">
									<i class="fas fa-times"></i>
								</button>
							</div>
						</div>
						<div class="debug-logs small"></div>
					</div>
				`);
				$('body').append(debugPanel);
			}
			updateMobileDebugPanel();
			$('#mobile-debug-panel').slideDown();
		} else {
			if (panel.length) {
				panel.slideUp();
			}
		}
	}
	
	// Add debug toggle button (only on mobile)
	if (isMobile) {
		// Make toggle function globally accessible for button click
		window.toggleMobileDebug = toggleMobileDebug;
		
		// Add triple-tap gesture to show debug panel (tap on qr-scanning-status area)
		let tapCount = 0;
		let tapTimer = null;
		$(document).on('click', '#qr-scanning-status, #qr-reader', function(e) {
			// Only trigger on quick triple-tap (within 1 second)
			tapCount++;
			if (tapTimer) clearTimeout(tapTimer);
			tapTimer = setTimeout(() => {
				if (tapCount >= 3) {
					toggleMobileDebug();
					mobileDebugLog('Debug panel toggled by triple-tap');
				}
				tapCount = 0;
			}, 1000);
		});
		
		mobileDebugLog('Mobile debug system initialized. Triple-tap QR area to show debug panel.');
	}
	
	// Verify Tesseract on init
	setTimeout(() => {
		verifyTesseractLoaded();
	}, 1000);
	let defaultQrStatusHtml = `
		<div class="alert alert-info mb-0">
			<i class="fas fa-search me-2"></i>
			<strong>Siap untuk memindai QR Patrol</strong><br>
			<small>Arahkan kamera ke QR code. Tekan tombol \"Mulai Scan\" jika kamera belum aktif.</small>
		</div>
	`;
	if (qrInlineWrapper.length) {
		qrInlineWrapper.after(qrInlineHomeSlot);
		$('#qr-scanning-status').html(defaultQrStatusHtml);
	}
	if (!$('#photos-preview-container').children().length) {
		$('#photos-preview-container').hide();
	}
	if (descriptionField.length && descriptionField.val().trim() && descriptionCollapse.length) {
		if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
			const descInstance = bootstrap.Collapse.getOrCreateInstance(descriptionCollapse[0], { toggle: false });
			descInstance.show();
		} else {
			descriptionCollapse.addClass('show');
		}
	}
	
	function focusPhotoCard() {
		if (!photoEvidenceCard.length) {
			return;
		}
		scrollIntoViewIfNeeded(photoEvidenceCard);
		photoEvidenceCard.addClass('photo-highlight');
		if (photoHighlightTimeout) {
			clearTimeout(photoHighlightTimeout);
		}
		photoHighlightTimeout = setTimeout(() => {
			photoEvidenceCard.removeClass('photo-highlight');
		}, 1500);
	}
	
	function collapsePatrolProgressForCamera() {
		if (!patrolAccordionWrapper.length || accordionCollapsedByCamera) {
			return;
		}
		wasAccordionOpenBeforeCamera = patrolAccordionCollapse.hasClass('show');
		if (patrolAccordionCollapse.length && typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
			const instance = bootstrap.Collapse.getOrCreateInstance(patrolAccordionCollapse[0], { toggle: false });
			instance.hide();
		} else {
			patrolAccordionCollapse.removeClass('show');
		}
		patrolAccordionWrapper.addClass('camera-hidden');
		accordionCollapsedByCamera = true;
	}
	
	function restorePatrolProgressAfterCamera() {
		if (!accordionCollapsedByCamera || !patrolAccordionWrapper.length) {
			return;
		}
		patrolAccordionWrapper.removeClass('camera-hidden');
		if (wasAccordionOpenBeforeCamera && patrolAccordionCollapse.length) {
			if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
				const instance = bootstrap.Collapse.getOrCreateInstance(patrolAccordionCollapse[0], { toggle: false });
				instance.show();
			} else {
				patrolAccordionCollapse.addClass('show');
			}
		}
		accordionCollapsedByCamera = false;
	}
	
	function hideFlashControl(message) {
		if (!flashControlWrapper.length) {
			return;
		}
		flashControlWrapper.hide();
		if (message) {
			$('#flash-support-text').text(message);
		}
	}
	
	function setupFlashControl() {
		if (!flashControlWrapper.length || !cameraVideoTrack) {
			return;
		}
		const capabilities = cameraVideoTrack.getCapabilities ? cameraVideoTrack.getCapabilities() : {};
		torchSupported = !!capabilities.torch;
		if (!torchSupported) {
			hideFlashControl('Lampu tidak tersedia di perangkat ini.');
			return;
		}
		flashControlWrapper.show();
		$('#flash-support-text').text('Sesuaikan lampu saat mengambil foto.');
		updateFlashButtons();
		applyFlashMode(flashMode);
	}
	
	function updateFlashButtons() {
		if (!flashControlWrapper.length) {
			return;
		}
		flashControlWrapper.find('.flash-toggle').removeClass('active');
		flashControlWrapper.find(`.flash-toggle[data-flash-mode="${flashMode}"]`).addClass('active');
	}
	
	function applyFlashMode(mode) {
		if (!cameraVideoTrack || !torchSupported) {
			return;
		}
		if (mode === 'auto') {
			cameraVideoTrack.applyConstraints({ advanced: [{ torch: false }] }).catch(() => {});
			return;
		}
		const torchOn = mode === 'on';
		cameraVideoTrack.applyConstraints({ advanced: [{ torch: torchOn }] }).catch(err => {
			console.warn('Failed to set torch:', err);
			Swal.fire('Info', 'Tidak dapat mengubah lampu di perangkat ini.', 'info');
		});
	}
	
	// Presensi Flash Control Functions (moved to global scope above, keeping reference here for compatibility)
	// Functions are now defined globally before initPatrolFunctionality() to be accessible from attachWebcam()
	
	function storePatrolOptionsForCompany(companyId, patrols) {
		if (!companyId) {
			return;
		}
		companyPatrolMap[companyId] = normalizePatrolArray(patrols);
	}
	
	function setPatrolOptionsForCompany(companyId) {
		if (companyId && companyPatrolMap[companyId]) {
			patrolOptions = normalizePatrolArray(companyPatrolMap[companyId]);
		} else {
			patrolOptions = [];
		}
		updatePatrolDropdown();
		initPatrolStatusMap(patrolOptions);
		renderPatrolStatusTable();
		togglePatrolUi(patrolOptions.length > 0);
		setNextPatrolInfo(getNextPendingPatrol());
	}

	function initPatrolStatusMap(patrols) {
		patrolStatusMap = {};
		if (!patrols || !patrols.length) {
			return;
		}
		patrols.forEach(function(patrol, index) {
			const formatted = formatPatrolForDisplay(patrol);
			if (!formatted || !formatted.id_patrol) {
				return;
			}
			const order = formatted.urutan != null ? formatted.urutan : (index + 1);
			patrolStatusMap[formatted.id_patrol] = {
				nama_patrol: formatted.nama_patrol || ('Patrol #' + (index + 1)),
				urutan: order,
				status: 'pending',
				last_scan: null
			};
		});
	}

	function renderPatrolTabs() {
		const nav = $('#patrol-tabs-nav');
		const placeholder = $('#patrol-tabs-placeholder');
		const detailBody = $('#patrol-tabs-detail-body');
		if (!nav.length) {
			return;
		}
		nav.empty();
		if (!patrolOptions.length) {
			if (placeholder.length) {
				placeholder.show();
			}
			if (detailBody.length) {
				detailBody.hide().empty();
			}
			return;
		}

		patrolOptions.forEach(function(patrol, idx) {
			const button = $('<button type="button" class="btn btn-outline-primary patrol-tab-button me-2 mb-2"></button>');
			button.attr('data-patrol-id', patrol.id_patrol);
			button.text(patrol.nama_patrol || ('Patrol #' + (idx + 1)));
			button.on('click', function() {
				selectPatrolForScan(patrol.id_patrol);
			});
			nav.append(button);
		});

		if (selectedPatrolId && findPatrolById(selectedPatrolId)) {
			selectPatrolForScan(selectedPatrolId);
		} else {
			selectPatrolForScan(patrolOptions[0].id_patrol);
		}
	}

	function selectPatrolForScan(patrolId) {
		if (!patrolId) {
			return;
		}
		selectedPatrolId = patrolId;
		// Tab section removed - just set the selected patrol ID
	}

	function updatePatrolDetailCard(patrolId) {
		const detailBody = $('#patrol-tabs-detail-body');
		const placeholder = $('#patrol-tabs-placeholder');
		if (!detailBody.length) {
			return;
		}
		const patrol = findPatrolById(patrolId);
		if (!patrol) {
			detailBody.hide().empty();
			if (placeholder.length) {
				placeholder.show();
			}
			return;
		}
		const statusObj = patrolStatusMap[patrol.id_patrol] || {};
		const isCompleted = statusObj.status === 'completed';
		const badge = isCompleted ? '<span class="badge bg-success">Selesai</span>' : '<span class="badge bg-secondary">Belum di-scan</span>';
		const lastScanText = statusObj.last_scan ? statusObj.last_scan : '-';
		const order = statusObj.urutan != null ? statusObj.urutan : (patrol.urutan != null ? patrol.urutan : '-');
		detailBody.html(
			'<h6 class="mb-2">' + (patrol.nama_patrol || 'Titik Patroli') + '</h6>' +
			'<p class="mb-1 text-muted">Urutan: ' + order + '</p>' +
			'<p class="mb-1 text-muted">Status: ' + badge + '</p>' +
			'<p class="mb-3 text-muted">Scan terakhir: ' + lastScanText + '</p>' +
			'<button type="button" class="btn btn-primary w-100" id="btn-scan-selected-patrol">' +
				'<i class="fas fa-qrcode me-2"></i>Scan ' + (patrol.nama_patrol || '') +
			'</button>'
		);
		detailBody.show();
		if (placeholder.length) {
			placeholder.hide();
		}
	}

	function renderPatrolStatusTable() {
		const tbody = $('#patrol-status-table tbody');
		if (!tbody.length) {
			return;
		}
		if (!patrolOptions.length) {
			tbody.html('<tr class="text-muted"><td colspan="5" class="text-center py-3">Belum ada data patroli untuk company ini.</td></tr>');
			return;
		}
		let rows = '';
		patrolOptions.forEach(function(patrol, idx) {
			const statusObj = patrolStatusMap[patrol.id_patrol] || {};
			const isCompleted = statusObj.status === 'completed';
			const badge = isCompleted
				? '<span class="badge bg-success">Selesai</span>'
				: '<span class="badge bg-secondary">Belum</span>';
			const lastScanText = statusObj.last_scan ? statusObj.last_scan : '-';
			const order = statusObj.urutan != null
				? statusObj.urutan
				: (patrol.urutan != null ? patrol.urutan : (idx + 1));
			
			// Button styling and state based on completion
			const buttonClass = isCompleted 
				? 'btn btn-sm btn-secondary w-100' 
				: 'btn btn-sm btn-outline-primary w-100 btn-scan-patrol-table';
			const buttonDisabled = isCompleted ? ' disabled' : '';
			const buttonIcon = isCompleted ? 'fas fa-check-circle me-1' : 'fas fa-qrcode me-1';
			const buttonText = isCompleted ? 'Selesai' : 'Scan';
			
			rows += '' +
				'<tr>' +
					'<td class="text-center">' + order + '</td>' +
					'<td>' +
						'<div class="fw-semibold">' + (patrol.nama_patrol || '-') + '</div>' +
						'<small class="text-muted">Barcode: ' + (patrol.barcode || '-') + '</small><br/>' +
						'' + badge + '<br/>' +
						'<small class="text-muted">Scan terakhir: ' + lastScanText + '</small>' +
					'</td>' +
					'<td class="text-center">' +
						'<button type="button" class="' + buttonClass + '" data-patrol-id="' + patrol.id_patrol + '"' + buttonDisabled + '>' +
							'<i class="' + buttonIcon + '"></i>' + buttonText +
						'</button>' +
					'</td>' +
				'</tr>';
		});
		tbody.html(rows);
		updatePatrolProgressBar();
	}

	function updatePatrolProgressBar() {
		const progressBar = $('#patrol-progress-bar');
		const progressPercent = $('#patrol-progress-percent');
		
		if (!progressBar.length || !progressPercent.length) {
			return;
		}
		
		if (!patrolOptions || !patrolOptions.length) {
			progressBar.css('width', '0%').attr('aria-valuenow', 0);
			progressPercent.text('0%');
			return;
		}
		
		const total = patrolOptions.length;
		let completed = 0;
		
		patrolOptions.forEach(function(patrol) {
			const statusObj = patrolStatusMap[patrol.id_patrol];
			if (statusObj && statusObj.status === 'completed') {
				completed++;
			}
		});
		
		const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
		
		progressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
		progressPercent.text(percentage + '%');
		
		// Change progress bar color based on completion
		progressBar.removeClass('bg-success bg-warning bg-danger');
		if (percentage === 100) {
			progressBar.addClass('bg-success');
		} else if (percentage >= 50) {
			progressBar.addClass('bg-primary');
		} else {
			progressBar.addClass('bg-warning');
		}
	}

	function markPatrolAsScanned(patrolId, options) {
		console.log('[QR DEBUG] markPatrolAsScanned called for patrol ID:', patrolId);
		if (!patrolId) {
			return;
		}
		options = options || {};
		if (!patrolStatusMap[patrolId]) {
			const patrol = findPatrolById(patrolId);
			if (!patrol) {
				return;
			}
			const fallbackOrder = patrol.urutan != null ? patrol.urutan : null;
			patrolStatusMap[patrolId] = {
				nama_patrol: patrol.nama_patrol || '',
				urutan: fallbackOrder,
				status: 'pending',
				last_scan: null
			};
		}
		let timestamp = options.timestamp;
		if (!timestamp) {
			if (typeof moment !== 'undefined') {
				timestamp = moment().format('HH:mm:ss');
			} else {
				const now = new Date();
				const hours = String(now.getHours()).padStart(2, '0');
				const minutes = String(now.getMinutes()).padStart(2, '0');
				const seconds = String(now.getSeconds()).padStart(2, '0');
				timestamp = hours + ':' + minutes + ':' + seconds;
			}
		}
		patrolStatusMap[patrolId].status = 'completed';
		patrolStatusMap[patrolId].last_scan = timestamp;
		console.log('[QR DEBUG] Patrol marked as completed. Updated status:', JSON.stringify(patrolStatusMap[patrolId]));
		console.log('[QR DEBUG] Note: This does NOT prevent re-scanning. Old QR codes can still be used.');
		renderPatrolStatusTable();
		if (selectedPatrolId == patrolId) {
			updatePatrolDetailCard(patrolId);
		}
		setNextPatrolInfo(getNextPendingPatrol());
	}

	function getNextPendingPatrol() {
		if (!patrolOptions || !patrolOptions.length) {
			return null;
		}
		for (let i = 0; i < patrolOptions.length; i++) {
			const patrol = patrolOptions[i];
			const statusObj = patrolStatusMap[patrol.id_patrol];
			if (!statusObj || statusObj.status !== 'completed') {
				return patrol;
			}
		}
		return null;
	}

	function togglePatrolUi(hasData) {
		// Show patrol status table
		$('#patrol-status-table-wrapper').show();
	}

	function triggerScanForPatrol(patrolId) {
		if (!patrolId) {
			Swal.fire('Info', 'Pilih titik patroli terlebih dahulu.', 'info');
			return;
		}
		try {
			// Store as string for consistent comparison
			// NOTE: No sequence validation - users can scan any patrol in any order
			// IMPORTANT: This sets forcedScannerTargetId which will be preserved throughout the scan process
			// Even if user scans a different QR code, this patrol ID will be used
			forcedScannerTargetId = String(patrolId);
			selectPatrolForScan(patrolId);
			ensureInlineScanner(true);
		} catch (error) {
			console.error('Error starting scanner:', error);
			// Reset state on error
			forcedScannerTargetId = null;
			selectedPatrolId = null;
			// Stop any running scanner
			stopQRScanner().then(() => {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Gagal memulai scanner. Silakan coba lagi.'
				});
			});
		}
	}
	
	function findPatrolByBarcode(barcode) {
		console.log('[QR DEBUG] findPatrolByBarcode called with barcode:', barcode);
		
		if (!barcode || !patrolOptions || !patrolOptions.length) {
			console.log('[QR DEBUG] findPatrolByBarcode: Invalid input - barcode:', barcode, 'patrolOptions length:', patrolOptions ? patrolOptions.length : 0);
			return null;
		}
		const target = String(barcode).trim().toLowerCase();
		console.log('[QR DEBUG] findPatrolByBarcode: Searching for normalized barcode:', target);
		
		const result = patrolOptions.find(function(patrol) {
			const patrolBarcode = String(patrol.barcode || '').trim().toLowerCase();
			const matches = patrolBarcode === target;
			if (matches) {
				console.log('[QR DEBUG] findPatrolByBarcode: Match found!', patrol);
			}
			return matches;
		}) || null;
		
		console.log('[QR DEBUG] findPatrolByBarcode result:', result ? result.nama_patrol : 'null');
		return result;
	}
	
	function findPatrolById(patrolId) {
		if (!patrolId || !patrolOptions || !patrolOptions.length) {
			return null;
		}
		return patrolOptions.find(function(patrol) {
			return String(patrol.id_patrol) === String(patrolId);
		}) || null;
	}
	
	function getSequenceNumber(patrolId) {
		if (!patrolId || !patrolOptions || !patrolOptions.length) {
			return null;
		}
		const index = patrolOptions.findIndex(function(patrol) {
			return String(patrol.id_patrol) === String(patrolId);
		});
		if (index === -1) {
			return null;
		}
		const patrol = patrolOptions[index];
		return patrol.urutan != null ? patrol.urutan : (index + 1);
	}
	
	function formatPatrolForDisplay(patrol) {
		if (!patrol) {
			return null;
		}
		const formatted = {
			id_patrol: patrol.id_patrol ?? patrol.idPatrol ?? null,
			nama_patrol: patrol.nama_patrol ?? patrol.namaPatrol ?? '',
			barcode: patrol.barcode ?? patrol.scanned_barcode ?? ''
		};
		formatted.urutan = patrol.urutan != null ? patrol.urutan : getSequenceNumber(formatted.id_patrol);
		return formatted;
	}
	
	function restartScannerWithDelay() {
		setTimeout(function() {
			if (isQrInModal && $('#qrScannerModal').is(':visible')) {
				startQRScanner();
			} else if (!isQrInModal && qrInlineWrapper.length) {
				startQRScanner();
			}
		}, 600);
	}
	
	function normalizePatrolArray(patrols) {
		if (!patrols) {
			return [];
		}
		if (Array.isArray(patrols)) {
			return patrols;
		}
		return Object.keys(patrols).map(function(key) {
			return patrols[key];
		});
	}
	
	if (companyPatrolMap && typeof companyPatrolMap === 'object') {
		Object.keys(companyPatrolMap).forEach(function(key) {
			companyPatrolMap[key] = normalizePatrolArray(companyPatrolMap[key]);
		});
	} else {
		companyPatrolMap = {};
	}

	function setStepper(step) {
		if (!stepperPills.length) {
			return;
		}
		stepperPills.removeClass('active');
		stepperPills.filter('[data-step="' + step + '"]').addClass('active');
	}

	function scrollIntoViewIfNeeded($target) {
		if (!$target || !$target.offset) {
			return;
		}
		$('html, body').stop().animate({
			scrollTop: $target.offset().top - 80
		}, 400);
	}

	function isScannerRunning() {
		if (!qrScanner) {
			return false;
		}
		if (typeof qrScanner.isScanning === 'function') {
			return qrScanner.isScanning();
		}
		return qrScanner.isScanning === true;
	}

	function stopQRScanner() {
		// Reset timing metrics when scanner stops
		qrScannerStartTime = null;
		
		// Clear OCR fallback timer
		if (ocrFallbackTimer) {
			clearTimeout(ocrFallbackTimer);
			ocrFallbackTimer = null;
		}
		
		return new Promise(resolve => {
			try {
				if (!qrScanner) {
					// Reset QR scanner flash control
					qrScannerVideoTrack = null;
					hideQRFlashControl();
					resolve();
					return;
				}
				if (isScannerRunning()) {
					qrScanner.stop().then(() => {
						try {
							qrScanner.clear();
						} catch (e) {}
						qrScanner = null;
						// Reset QR scanner flash control
						qrScannerVideoTrack = null;
						hideQRFlashControl();
						resolve();
					}).catch(() => {
						qrScanner = null;
						// Reset QR scanner flash control
						qrScannerVideoTrack = null;
						hideQRFlashControl();
						resolve();
					});
				} else {
					try {
						qrScanner.clear();
					} catch (e) {}
					qrScanner = null;
					// Reset QR scanner flash control
					qrScannerVideoTrack = null;
					hideQRFlashControl();
					resolve();
				}
			} catch (e) {
				qrScanner = null;
				// Reset QR scanner flash control
				qrScannerVideoTrack = null;
				hideQRFlashControl();
				resolve();
			}
		});
	}

	function resetQrInlineStatus() {
		$('#qr-result').hide();
		$('#qr-scanning-status').html(defaultQrStatusHtml);
	}

	function ensureInlineScanner(forceRestart = false) {
		showStep(1);
		if (qrInlineWrapper.length) {
			qrInlineWrapper.addClass('active');
			scrollIntoViewIfNeeded(qrInlineWrapper);
			setTimeout(() => {
				qrInlineWrapper.removeClass('active');
			}, 1500);
		}
		if (forceRestart) {
			stopQRScanner().then(() => {
				startQRScanner();
			});
		} else if (!isScannerRunning()) {
			startQRScanner();
		}
	}

	function moveScannerToModal() {
		if (!qrInlineWrapper.length || !qrModalPlaceholder.length) {
			return;
		}
		qrModalPlaceholder.empty().append(qrInlineWrapper.detach());
		isQrInModal = true;
		$('#qrScannerModal').modal('show');
	}

	function returnScannerToInline() {
		if (!qrInlineWrapper.length || !qrInlineHomeSlot.length) {
			return;
		}
		qrInlineHomeSlot.after(qrInlineWrapper.detach());
		isQrInModal = false;
		resetQrInlineStatus();
	}
	
	// Load patrol points when company is detected
	window.addEventListener('companyDetected', function(event) {
		currentCompanyId = event.detail.companyId;
		setPatrolOptionsForCompany(currentCompanyId);
		loadPatrolPoints(currentCompanyId);
	});
	
	// Also check if company is already detected on page load
	$(document).ready(function() {
		// Reset state variables on page load to prevent stuck state
		forcedScannerTargetId = null;
		selectedPatrolId = null;
		scannedPatrolData = null;
		currentStep = 1;
		
		setTimeout(function() {
			const idCompany = $('#id_company').val();
			if (idCompany && !currentCompanyId) {
				currentCompanyId = idCompany;
				setPatrolOptionsForCompany(currentCompanyId);
				loadPatrolPoints(currentCompanyId);
			}
		}, 1000);
	});
	
	// Step navigation
	// Proceed to step 2 handlers (both QR and OCR)
	$('#btn-proceed-to-step2, #btn-proceed-to-step2-ocr').click(function() {
		showStep(2);
	});
	
	$('#btn-back-to-step1').click(function() {
		showStep(1);
	});
	
	$('#btn-proceed-to-step3').click(function() {
		validateStep2();
	});
	
	$('#btn-back-to-step2').click(function() {
		showStep(2);
	});
	
	// Show specific step
	function showStep(step) {
		$('[data-step-container]').hide();
		$('#step-' + step).show();
		currentStep = step;
		setStepper(step);
		scrollIntoViewIfNeeded($('#step-' + step));
		
		if (step === 3) {
			populateReviewContent();
		}
	}
	
	// Validate step 2 before proceeding
	function validateStep2() {
		const judul = $('#judul_activity').val();
		
		if (!judul.trim()) {
			Swal.fire('Error', 'Judul activity harus diisi', 'error');
			return;
		}
		
		showStep(3);
	}
	
	// Populate review content
	function populateReviewContent() {
		const judul = $('#judul_activity').val();
		const deskripsi = $('#deskripsi_activity').val();
		const foto = $('#foto_activity').val();
		
		let reviewHtml = `
			<div class="row">
				<div class="col-6"><strong>Titik Patroli:</strong></div>
				<div class="col-6">${scannedPatrolData ? scannedPatrolData.nama_patrol : 'N/A'}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Company:</strong></div>
				<div class="col-6">${scannedPatrolData ? scannedPatrolData.nama_company : 'N/A'}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Judul:</strong></div>
				<div class="col-6">${judul}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Deskripsi:</strong></div>
				<div class="col-6">${deskripsi}</div>
			</div>
			<div class="row">
				<div class="col-6"><strong>Foto:</strong></div>
				<div class="col-6">${foto ? '✓ Sudah diambil' : '✗ Belum diambil'}</div>
			</div>
		`;
		
		$('#review-content').html(reviewHtml);
	}
	
	// Load patrol points for a company
	function loadPatrolPoints(companyId) {
		if (!companyId) {
			patrolOptions = [];
			updatePatrolDropdown();
			setNextPatrolInfo(null);
			// Reset state when no company
			forcedScannerTargetId = null;
			selectedPatrolId = null;
			return;
		}
		
		// Reset scanner target when loading new patrol points (e.g., after refresh)
		forcedScannerTargetId = null;
		selectedPatrolId = null;
		
		// Always fetch from database - don't use cached data
		// Clear patrolStatusMap before loading to ensure fresh state
		patrolStatusMap = {};
		
		// Build URL with cache-busting timestamp parameter
		const url = base_url + 'mobile-activity/getPatrolPoints/' + companyId + '?_t=' + Date.now();
		
		$.ajax({
			url: url,
			type: 'GET',
			dataType: 'json',
			cache: false, // Prevent browser caching
			success: function(response) {
				if (response.status === 'ok') {
					patrolOptions = response.data || [];
					storePatrolOptionsForCompany(companyId, patrolOptions);
					updatePatrolDropdown();
					
					// Initialize status map with 'pending' for all patrols first
					initPatrolStatusMap(patrolOptions);
					
					// Mark all scanned patrols today as completed BEFORE rendering table
					if (response.scanned_patrols_today && typeof response.scanned_patrols_today === 'object') {
						for (const patrolId in response.scanned_patrols_today) {
							if (response.scanned_patrols_today.hasOwnProperty(patrolId)) {
								const scanTime = response.scanned_patrols_today[patrolId];
								let formattedTime = null;
								
								if (scanTime) {
									if (typeof moment !== 'undefined') {
										formattedTime = moment(scanTime).format('HH:mm:ss');
									} else {
										// Parse datetime string and extract time
										const timeMatch = scanTime.match(/(\d{2}):(\d{2}):(\d{2})/);
										if (timeMatch) {
											formattedTime = timeMatch[0];
										} else {
											formattedTime = scanTime;
										}
									}
								}
								
								// Mark as scanned (this updates patrolStatusMap but doesn't render yet)
								const patrolIdStr = String(patrolId);
								if (!patrolStatusMap[patrolIdStr]) {
									const patrol = findPatrolById(patrolIdStr);
									if (patrol) {
										const fallbackOrder = patrol.urutan != null ? patrol.urutan : null;
										patrolStatusMap[patrolIdStr] = {
											nama_patrol: patrol.nama_patrol || '',
											urutan: fallbackOrder,
											status: 'pending',
											last_scan: null
										};
									}
								}
								if (patrolStatusMap[patrolIdStr]) {
									patrolStatusMap[patrolIdStr].status = 'completed';
									patrolStatusMap[patrolIdStr].last_scan = formattedTime;
								}
							}
						}
					}
					
					// Also mark last_scanned for backward compatibility
					if (response.last_scanned && response.last_scanned.id_patrol) {
						const lastPatrolId = String(response.last_scanned.id_patrol);
						// Only mark if not already marked by scanned_patrols_today
						if (!response.scanned_patrols_today || !response.scanned_patrols_today[lastPatrolId]) {
							let lastScanTime = null;
							if (response.last_scanned.scan_time) {
								if (typeof moment !== 'undefined') {
									lastScanTime = moment(response.last_scanned.scan_time).format('HH:mm:ss');
								} else {
									lastScanTime = response.last_scanned.scan_time;
								}
							}
							if (!patrolStatusMap[lastPatrolId]) {
								const patrol = findPatrolById(lastPatrolId);
								if (patrol) {
									const fallbackOrder = patrol.urutan != null ? patrol.urutan : null;
									patrolStatusMap[lastPatrolId] = {
										nama_patrol: patrol.nama_patrol || '',
										urutan: fallbackOrder,
										status: 'pending',
										last_scan: null
									};
								}
							}
							if (patrolStatusMap[lastPatrolId]) {
								patrolStatusMap[lastPatrolId].status = 'completed';
								patrolStatusMap[lastPatrolId].last_scan = lastScanTime;
							}
						}
					}
					
					// Now render table after all status updates are complete
					renderPatrolStatusTable();
					togglePatrolUi(patrolOptions.length > 0);
					
					const nextFromServer = response.next_patrol || getNextPendingPatrol();
					setNextPatrolInfo(nextFromServer);
				}
			},
			error: function() {
				// Error loading patrol points - silently fail
			}
		});
	}
	
	// Display next patrol info
	function displayNextPatrolInfo(nextPatrol) {
		if (nextPatrol) {
			const urutanText = nextPatrol.urutan ? `Urutan: ${nextPatrol.urutan}` : '';
			$('#next-patrol-name').html(`<strong>${nextPatrol.nama_patrol}</strong>`);
			$('#next-patrol-urutan').text(urutanText);
			$('#next-patrol-info').show();
		} else {
			hideNextPatrolInfo();
		}
	}
	
	// Hide next patrol info
	function hideNextPatrolInfo() {
		$('#next-patrol-info').hide();
		$('#next-patrol-name').empty();
		$('#next-patrol-urutan').empty();
	}
	
	function setNextPatrolInfo(nextPatrol) {
		const formattedPatrol = formatPatrolForDisplay(nextPatrol);
		nextPatrolInfo = formattedPatrol;
		if (formattedPatrol) {
			displayNextPatrolInfo(formattedPatrol);
		} else {
			hideNextPatrolInfo();
		}
	}
	
	function getNextPatrolAfter(patrolId) {
		if (!patrolOptions || patrolOptions.length === 0) {
			return null;
		}
		
		if (!patrolId) {
			return patrolOptions[0];
		}
		
		const index = patrolOptions.findIndex(function(patrol) {
			return String(patrol.id_patrol) === String(patrolId);
		});
		
		if (index === -1) {
			return null;
		}
		
		return patrolOptions[index + 1] || null;
	}
	
	// Update patrol dropdown
	function updatePatrolDropdown() {
		const select = $('#id_patrol');
		select.empty().append('<option value="">Pilih Titik Patroli</option>');
		
		patrolOptions.forEach(function(patrol) {
			select.append(`<option value="${patrol.id_patrol}" data-barcode="${patrol.barcode}">${patrol.nama_patrol}</option>`);
		});
	}
	
	// QR Scanner functionality
	// "Buka Kamera" button click handler - start QR scanner
	$('#btn-start-inline-qr').off('click').on('click', function() {
		console.log('[QR DEBUG] ========== Buka Kamera button clicked ==========');
		
		if (!currentCompanyId) {
			console.log('[QR DEBUG] Company ID not set');
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		
		// DON'T reset forcedScannerTargetId if user already clicked a scan button
		// Only reset if this is a free scan (no specific patrol selected)
		// This preserves user's choice (e.g., patrol 18) when they click "Mulai Scan"
		if (!forcedScannerTargetId || forcedScannerTargetId === null || forcedScannerTargetId === '') {
			// Free scan mode - no specific patrol selected
			forcedScannerTargetId = null;
		}
		// If forcedScannerTargetId is set, keep it - user selected a specific patrol
		
		// Check camera permissions first
		if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
			console.log('[QR DEBUG] Camera API available, checking permissions...');
			
			// Test camera access
			navigator.mediaDevices.getUserMedia({ video: true })
				.then(function(stream) {
					// Permission granted - stop test stream and start scanner
					stream.getTracks().forEach(track => track.stop());
					console.log('[QR DEBUG] Camera permission granted, ensuring inline scanner and starting...');
		ensureInlineScanner(true);
				})
				.catch(function(error) {
					console.error('[QR DEBUG] Camera permission error:', error);
					let errorMsg = 'Tidak dapat mengakses kamera. ';
					if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
						errorMsg = 'Izin kamera ditolak. Silakan aktifkan izin kamera di pengaturan browser.';
					} else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
						errorMsg = 'Kamera tidak ditemukan di perangkat ini.';
					} else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
						errorMsg = 'Kamera sedang digunakan oleh aplikasi lain.';
					} else {
						errorMsg += 'Error: ' + error.message;
					}
					
					Swal.fire({
						icon: 'error',
						title: 'Error Kamera',
						html: errorMsg + '<br><br><small>Pastikan:<br>1. Izin kamera sudah diberikan<br>2. Menggunakan HTTPS atau localhost<br>3. Browser mendukung kamera</small>',
						confirmButtonText: 'OK'
					});
					
					$('#qr-scanning-status').html(`
						<div class="alert alert-danger">
							<i class="fas fa-exclamation-triangle me-2"></i>
							<strong>Kamera tidak dapat diakses</strong>
							<br>
							<small>${errorMsg}</small>
						</div>
					`);
				});
		} else {
			console.error('[QR DEBUG] Camera API not supported');
			Swal.fire({
				icon: 'error',
				title: 'Browser Tidak Mendukung',
				text: 'Browser Anda tidak mendukung akses kamera. Silakan gunakan browser modern seperti Chrome atau Firefox.',
				confirmButtonText: 'OK'
			});
		}
	});

	$('#btn-retry-inline-qr').click(function() {
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		ensureInlineScanner(true);
	});
	
	// OCR ONLY MODE - Continuous OCR text detection
	let ocrVideoStream = null;
	let ocrVideoTrack = null;
	let ocrInterval = null;
	
	// Start OCR camera and continuous text detection
	$('#btn-start-ocr').off('click').on('click', function() {
		console.log('[OCR] Starting OCR camera...');
		
		if (ocrVideoStream) {
			// Already running, stop it
			stopOCRCamera();
			$(this).html('<i class="fas fa-camera me-2"></i>Mulai OCR');
			return;
		}
		
		// Check if Tesseract is loaded
		if (typeof Tesseract === 'undefined') {
			Swal.fire('Error', 'OCR library tidak tersedia. Pastikan koneksi internet aktif.', 'error');
			return;
		}
		
		// Request camera access
		if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
			const videoContainer = $('#ocr-video-container');
			const videoElement = document.getElementById('ocr-video');
			const statusDiv = $('#ocr-status');
			
			statusDiv.html(`
				<div class="alert alert-info">
					<i class="fas fa-spinner fa-spin me-2"></i>
					<strong>Mengakses kamera...</strong>
				</div>
			`);
			
			navigator.mediaDevices.getUserMedia({ 
				video: { 
					facingMode: 'environment',
					width: { ideal: 1280 },
					height: { ideal: 720 }
				} 
			}).then(function(stream) {
				ocrVideoStream = stream;
				ocrVideoTrack = stream.getVideoTracks()[0];
				videoElement.srcObject = stream;
				
				videoElement.onloadedmetadata = function() {
					videoContainer.show();
					statusDiv.html(`
						<div class="alert alert-success">
							<i class="fas fa-camera me-2"></i>
							<strong>Kamera aktif - Membaca teks dengan OCR...</strong>
						</div>
					`);
					
					// Start continuous OCR
					startContinuousOCR();
					$('#btn-start-ocr').html('<i class="fas fa-stop me-2"></i>Stop OCR');
				};
				
				videoElement.play();
			}).catch(function(error) {
				console.error('[OCR] Camera error:', error);
				let errorMsg = 'Tidak dapat mengakses kamera.';
				if (error.name === 'NotAllowedError') {
					errorMsg = 'Izin kamera ditolak.';
				} else if (error.name === 'NotFoundError') {
					errorMsg = 'Kamera tidak ditemukan.';
				}
				
				statusDiv.html(`
					<div class="alert alert-danger">
						<i class="fas fa-exclamation-triangle me-2"></i>
						<strong>${errorMsg}</strong>
					</div>
				`);
			});
		} else {
			Swal.fire('Error', 'Browser tidak mendukung akses kamera.', 'error');
		}
	});
	
	function startContinuousOCR() {
		if (ocrInterval) {
			clearInterval(ocrInterval);
		}
		
		// Run OCR every 2 seconds
		ocrInterval = setInterval(function() {
			runOCRDetection();
		}, 2000);
		
		// Run immediately
		runOCRDetection();
	}
	
	function runOCRDetection() {
		const videoElement = document.getElementById('ocr-video');
		if (!videoElement || videoElement.readyState !== 4) {
			return;
		}
		
		const canvas = document.createElement('canvas');
		canvas.width = videoElement.videoWidth;
		canvas.height = videoElement.videoHeight;
		const ctx = canvas.getContext('2d');
		ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
		
		canvas.toBlob(function(blob) {
			if (!blob) {
				return;
			}
			
			// Update status
			$('#ocr-status').html(`
				<div class="alert alert-info">
					<i class="fas fa-spinner fa-spin me-2"></i>
					<strong>Memproses OCR...</strong>
				</div>
			`);
			
			// Run OCR
			Tesseract.recognize(blob, 'eng', {
				logger: m => {
					if (m.status === 'recognizing text') {
						console.log('[OCR] Progress:', Math.round(m.progress * 100) + '%');
					}
				}
			}).then(function(result) {
				const ocrText = result.data.text || '';
				console.log('[OCR] Detected text:', ocrText);
				
				// Display all detected text
				if (ocrText.trim()) {
					$('#ocr-detected-text').text(ocrText);
					$('#qr-scan-result').show();
					
					// Update status
					$('#ocr-status').html(`
						<div class="alert alert-success">
							<i class="fas fa-check-circle me-2"></i>
							<strong>Teks terdeteksi!</strong>
							<br>
							<small>Memperbarui setiap 2 detik...</small>
						</div>
					`);
				} else {
					$('#ocr-status').html(`
						<div class="alert alert-warning">
							<i class="fas fa-search me-2"></i>
							<strong>Tidak ada teks terdeteksi</strong>
							<br>
							<small>Mencoba lagi...</small>
						</div>
					`);
				}
			}).catch(function(error) {
				console.error('[OCR] Error:', error);
				$('#ocr-status').html(`
					<div class="alert alert-danger">
						<i class="fas fa-exclamation-triangle me-2"></i>
						<strong>Error OCR: ${error.message}</strong>
					</div>
				`);
			});
		}, 'image/jpeg', 0.8);
	}
	
	function stopOCRCamera() {
		if (ocrInterval) {
			clearInterval(ocrInterval);
			ocrInterval = null;
		}
		
		if (ocrVideoTrack) {
			ocrVideoTrack.stop();
			ocrVideoTrack = null;
		}
		
		if (ocrVideoStream) {
			ocrVideoStream.getTracks().forEach(track => track.stop());
			ocrVideoStream = null;
		}
		
		const videoElement = document.getElementById('ocr-video');
		if (videoElement) {
			videoElement.srcObject = null;
		}
		
		$('#ocr-video-container').hide();
		$('#ocr-status').html(`
			<div class="alert alert-info">
				<i class="fas fa-stop me-2"></i>
				<strong>OCR dihentikan</strong>
			</div>
		`);
	}
	
	$('#btn-floating-photo, #btn-scroll-to-photo').click(function() {
		focusPhotoCard();
	});
	
	flashControlWrapper.find('.flash-toggle').on('click', function() {
		const mode = $(this).data('flash-mode');
		if (!mode) {
			return;
		}
		if (!torchSupported) {
			Swal.fire('Info', 'Lampu tidak tersedia di perangkat ini.', 'info');
			return;
		}
		flashMode = mode;
		updateFlashButtons();
		applyFlashMode(mode);
	});
	
	// QR Scanner Flash Control Functions
	function hideQRFlashControl(message) {
		if (!qrFlashControlWrapper.length) {
			return;
		}
		qrFlashControlWrapper.hide();
		if (message) {
			$('#qr-flash-support-text').text(message);
		}
	}
	
	function setupQRScannerFlashControl() {
		if (!qrFlashControlWrapper.length || !qrScannerVideoTrack) {
			return;
		}
		const capabilities = qrScannerVideoTrack.getCapabilities ? qrScannerVideoTrack.getCapabilities() : {};
		qrScannerTorchSupported = !!capabilities.torch;
		if (!qrScannerTorchSupported) {
			hideQRFlashControl('Lampu tidak tersedia di perangkat ini.');
			return;
		}
		qrFlashControlWrapper.show();
		$('#qr-flash-support-text').text('Sesuaikan lampu saat memindai QR code.');
		updateQRScannerFlashButtons();
		applyQRScannerFlashMode(qrScannerFlashMode);
	}
	
	function updateQRScannerFlashButtons() {
		if (!qrFlashControlWrapper.length) {
			return;
		}
		qrFlashControlWrapper.find('.flash-toggle-qr').removeClass('active');
		qrFlashControlWrapper.find(`.flash-toggle-qr[data-flash-mode="${qrScannerFlashMode}"]`).addClass('active');
	}
	
	function applyQRScannerFlashMode(mode) {
		if (!qrScannerVideoTrack || !qrScannerTorchSupported) {
			return;
		}
		if (mode === 'auto') {
			qrScannerVideoTrack.applyConstraints({ advanced: [{ torch: false }] }).catch(() => {});
			return;
		}
		const torchOn = mode === 'on';
		qrScannerVideoTrack.applyConstraints({ advanced: [{ torch: torchOn }] }).catch(err => {
			console.warn('Failed to set QR scanner torch:', err);
			Swal.fire('Info', 'Tidak dapat mengubah lampu di perangkat ini.', 'info');
		});
	}
	
	// QR Scanner flash button click handlers
	qrFlashControlWrapper.find('.flash-toggle-qr').on('click', function() {
		const mode = $(this).data('flash-mode');
		if (!mode) {
			return;
		}
		if (!qrScannerTorchSupported) {
			Swal.fire('Info', 'Lampu tidak tersedia di perangkat ini.', 'info');
			return;
		}
		qrScannerFlashMode = mode;
		updateQRScannerFlashButtons();
		applyQRScannerFlashMode(mode);
	});
	
	function updateCameraStatus(message, variant = 'info') {
		if (!cameraStatusText.length) {
			return;
		}
		cameraStatusText.removeClass('text-success text-warning text-danger text-muted');
		if (!message) {
			cameraStatusText.text('');
			return;
		}
		let cls = 'text-muted';
		if (variant === 'success') {
			cls = 'text-success';
		} else if (variant === 'warning') {
			cls = 'text-warning';
		} else if (variant === 'error') {
			cls = 'text-danger';
		}
		cameraStatusText.addClass(cls).text(message);
	}

	// Manual QR entry button handler
	$(document).on('click', '#btn-manual-qr-input', function() {
		showManualQREntry();
	});

	$('#btn-manual-validation').click(function() {
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		if (!patrolOptions || patrolOptions.length === 0) {
			Swal.fire('Info', 'Data patrol belum tersedia untuk company ini.', 'info');
			return;
		}

		// PRIORITY: If user already clicked a scan button (forcedScannerTargetId is set), use that
		// This respects user's explicit choice (e.g., patrol 18) instead of suggesting next patrol
		if (forcedScannerTargetId && forcedScannerTargetId !== null && forcedScannerTargetId !== '') {
			const selectedPatrol = findPatrolById(forcedScannerTargetId);
			if (selectedPatrol) {
				// User already selected a patrol via scan button - use that one
				handlePatrolDetection(selectedPatrol.barcode || '', { isTest: true, manualTrigger: true });
				return;
			}
		}

		// Validasi Manual: Suggest next patrol but allow any patrol to be scanned
		// Users can also click scan button on any patrol row to scan in any order
		let targetPatrol = null;
		if (nextPatrolInfo && nextPatrolInfo.id_patrol) {
			targetPatrol = findPatrolById(nextPatrolInfo.id_patrol);
		}
		if (!targetPatrol) {
			// If no next patrol, use first pending patrol
			for (let i = 0; i < patrolOptions.length; i++) {
				const patrol = patrolOptions[i];
				const statusObj = patrolStatusMap[patrol.id_patrol];
				if (!statusObj || statusObj.status !== 'completed') {
					targetPatrol = patrol;
					break;
				}
			}
		}
		if (!targetPatrol) {
			// Fallback to first patrol if all are completed
			targetPatrol = patrolOptions[0];
		}

		if (!targetPatrol) {
			Swal.fire('Info', 'Patrol belum tersedia.', 'info');
			return;
		}

		// Allow scanning any patrol - no sequence validation
		forcedScannerTargetId = String(targetPatrol.id_patrol);
		handlePatrolDetection(targetPatrol.barcode || '', { isTest: true, manualTrigger: true });
	});

	$('#btn-open-fullscreen-qr').click(function() {
		if (!currentCompanyId) {
			Swal.fire('Error', 'Company belum terdeteksi. Pastikan GPS aktif dan Anda berada di lokasi company.', 'error');
			return;
		}
		moveScannerToModal();
	});

	$('body').off('click', '.btn-scan-patrol-table');
	$('body').on('click', '.btn-scan-patrol-table', function() {
		const patrolId = $(this).attr('data-patrol-id');
		// Ensure patrolId is properly parsed (handle string/number conversion)
		if (patrolId) {
			triggerScanForPatrol(patrolId);
		}
	});

	$('body').off('click', '#btn-scan-selected-patrol');
	$('body').on('click', '#btn-scan-selected-patrol', function() {
		triggerScanForPatrol(selectedPatrolId);
	});
	
	// Handle modal opened event
	$(document).on('qrScannerModalOpened', function() {
		setTimeout(() => {
			if (isQrInModal) {
				startQRScanner();
			}
		}, 300);
	});
	
	// Handle modal shown event - use both events for better compatibility
	$('#qrScannerModal').on('shown.bs.modal', function() {
		// Delay to ensure modal is fully rendered
		setTimeout(() => {
			if (isQrInModal) {
				startQRScanner();
			}
		}, 300);
	});
	
	// Also handle when modal is about to be shown (for some mobile browsers)
	$('#qrScannerModal').on('show.bs.modal', function() {
		// Set status message based on whether a specific patrol is targeted
		if (forcedScannerTargetId) {
			const targetPatrol = findPatrolById(forcedScannerTargetId);
			if (targetPatrol) {
				$('#qr-scanning-status').html(`
					<div class="alert alert-info">
						<i class="fas fa-search me-2"></i>
						<strong>Mencari QR Code untuk:</strong>
						<br>
						<strong>${targetPatrol.nama_patrol || 'Patrol #' + forcedScannerTargetId}</strong>
						<br>
						<small>Arahkan kamera ke QR code patrol yang sesuai</small>
					</div>
				`);
			} else {
				$('#qr-scanning-status').html(`
					<div class="alert alert-info">
						<i class="fas fa-search me-2"></i>
						<strong>Mencari QR Code...</strong>
						<br>
						<small>Arahkan kamera ke QR code patrol</small>
					</div>
				`);
			}
		} else {
			// Reset status message for free scanning
			$('#qr-scanning-status').html(`
				<div class="alert alert-info">
					<i class="fas fa-search me-2"></i>
					<strong>Mencari QR Code...</strong>
					<br>
					<small>Arahkan kamera ke QR code patrol</small>
					<br>
					<small class="text-muted">Pastikan izin kamera sudah diberikan</small>
				</div>
			`);
		}
	});
	
	
	// Start QR scanner
	function startQRScanner() {
		// Validate that company and patrol options are loaded before starting scanner
		if (!currentCompanyId) {
			console.log('[QR DEBUG] Cannot start scanner: Company ID not set');
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					icon: 'warning',
					title: 'Company Belum Terdeteksi',
					text: 'Pastikan GPS aktif atau pilih company secara manual terlebih dahulu.',
					confirmButtonText: 'OK'
				});
			}
			return Promise.reject('Company ID not set');
		}
		
		if (!patrolOptions || patrolOptions.length === 0) {
			console.log('[QR DEBUG] Cannot start scanner: No patrol options available');
			if (typeof Swal !== 'undefined') {
				Swal.fire({
					icon: 'warning',
					title: 'Data Patrol Tidak Tersedia',
					text: 'Data patrol belum tersedia untuk company ini. Silakan hubungi admin.',
					confirmButtonText: 'OK'
				});
			}
			return Promise.reject('No patrol options available');
		}
		
		console.log('[QR DEBUG] Starting QR scanner - Company ID:', currentCompanyId, 'Patrol options:', patrolOptions.length);
		
		// Check if Html5Qrcode is available
		if (typeof Html5Qrcode === 'undefined') {
			console.error('[QR DIAGNOSTIC] Html5Qrcode library not loaded');
			mobileDebugLog('ERROR: Html5Qrcode library not loaded', 'error');
			Swal.fire('Error', 'QR Scanner library tidak tersedia. Pastikan koneksi internet aktif untuk memuat library.', 'error');
			return;
		}
		
		// DIAGNOSTIC: Verify Html5Qrcode is fully loaded
		const libraryCheck = {
			Html5Qrcode: typeof Html5Qrcode,
			Html5QrcodeScanType: typeof Html5QrcodeScanType,
			hasStart: typeof Html5Qrcode.prototype.start === 'function',
			hasStop: typeof Html5Qrcode.prototype.stop === 'function',
			hasClear: typeof Html5Qrcode.prototype.clear === 'function'
		};
		console.log('[QR DIAGNOSTIC] Html5Qrcode library check:', libraryCheck);
		mobileDebugLog(`Library check: Html5Qrcode=${libraryCheck.Html5Qrcode}, hasStart=${libraryCheck.hasStart}, hasStop=${libraryCheck.hasStop}`, 'info');
		
		// DIAGNOSTIC: Verify Html5Qrcode is fully loaded
		console.log('[QR DIAGNOSTIC] Html5Qrcode library check:', {
			Html5Qrcode: typeof Html5Qrcode,
			Html5QrcodeScanType: typeof Html5QrcodeScanType,
			hasStart: typeof Html5Qrcode.prototype.start === 'function',
			hasStop: typeof Html5Qrcode.prototype.stop === 'function'
		});
		mobileDebugLog('Html5Qrcode library loaded and verified', 'info');
		
		// Check if qr-reader element exists
		let qrReaderElementCheck = document.getElementById('qr-reader');
		if (!qrReaderElementCheck) {
			console.error('[QR DIAGNOSTIC] qr-reader element not found in DOM');
			mobileDebugLog('ERROR: qr-reader element not found in DOM', 'error');
			Swal.fire('Error', 'Elemen scanner tidak ditemukan', 'error');
			return;
		}
		
		// DIAGNOSTIC: Check element visibility and dimensions
		const computedStyle = window.getComputedStyle(qrReaderElementCheck);
		const elementRect = qrReaderElementCheck.getBoundingClientRect();
		const diagnosticInfo = {
			elementExists: !!qrReaderElementCheck,
			display: computedStyle.display,
			visibility: computedStyle.visibility,
			opacity: computedStyle.opacity,
			width: elementRect.width,
			height: elementRect.height,
			top: elementRect.top,
			left: elementRect.left,
			parentDisplay: qrReaderElementCheck.parentElement ? window.getComputedStyle(qrReaderElementCheck.parentElement).display : 'N/A',
			parentVisibility: qrReaderElementCheck.parentElement ? window.getComputedStyle(qrReaderElementCheck.parentElement).visibility : 'N/A',
			isVisible: computedStyle.display !== 'none' && 
			          computedStyle.visibility !== 'hidden' && 
			          parseFloat(computedStyle.opacity) > 0 &&
			          elementRect.width > 0 && 
			          elementRect.height > 0
		};
		
		console.log('[QR DIAGNOSTIC] Element state check:', diagnosticInfo);
		mobileDebugLog(`Element diagnostic: visible=${diagnosticInfo.isVisible}, display=${diagnosticInfo.display}, size=${elementRect.width}x${elementRect.height}`, 
			diagnosticInfo.isVisible ? 'info' : 'warning');
		
		// Warn if element is not visible
		if (!diagnosticInfo.isVisible) {
			console.warn('[QR DIAGNOSTIC] WARNING: qr-reader element is not visible!', {
				display: diagnosticInfo.display,
				visibility: diagnosticInfo.visibility,
				opacity: diagnosticInfo.opacity,
				dimensions: `${elementRect.width}x${elementRect.height}`,
				parentDisplay: diagnosticInfo.parentDisplay
			});
			mobileDebugLog(`WARNING: Element not visible - display:${diagnosticInfo.display}, visibility:${diagnosticInfo.visibility}, size:${elementRect.width}x${elementRect.height}`, 'warning');
			
			// Try to make it visible
			qrReaderElementCheck.style.display = 'block';
			qrReaderElementCheck.style.visibility = 'visible';
			qrReaderElementCheck.style.opacity = '1';
			
			// Check parent containers
			let parent = qrReaderElementCheck.parentElement;
			let parentLevel = 0;
			while (parent && parentLevel < 5) {
				const parentStyle = window.getComputedStyle(parent);
				if (parentStyle.display === 'none') {
					console.warn(`[QR DIAGNOSTIC] Parent at level ${parentLevel} has display:none:`, parent);
					mobileDebugLog(`Parent level ${parentLevel} has display:none`, 'warning');
					parent.style.display = 'block';
				}
				parent = parent.parentElement;
				parentLevel++;
			}
		}
		
		// Clear the element before starting
		qrReaderElementCheck.innerHTML = '';
		
		// Check if scanner is already running
		let isScanning = false;
		if (qrScanner) {
			if (typeof qrScanner.isScanning === 'function') {
				isScanning = qrScanner.isScanning();
			} else if (qrScanner.isScanning === true) {
				isScanning = true;
			}
		}
		if (isScanning) {
			return;
		}
		
		// Clear existing scanner
		if (qrScanner) {
			try {
				qrScanner.clear();
			} catch(e) {
				// Error clearing scanner - silently fail
			}
		}
		
		// Mobile-friendly configuration
		// Use the isMobile from outer scope (initPatrolFunctionality)
		const config = {
			fps: isMobile ? 30 : 30, // Increased to 30 FPS for faster detection (1-2 second target)
			qrbox: function(viewfinderWidth, viewfinderHeight) {
				// Use 90% of viewfinder on mobile for better detection, 50% on desktop
				const minEdgePercentage = isMobile ? 0.9 : 0.5;
				const minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
				const qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
				console.log('[QR DEBUG] QR box size:', qrboxSize, 'from viewfinder:', viewfinderWidth, 'x', viewfinderHeight);
				return {
					width: qrboxSize,
					height: qrboxSize
				};
			},
			aspectRatio: 1.0,
			rememberLastUsedCamera: true,
			supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA], // Real-time camera scanning only
			showTorchButtonIfSupported: true,
			showZoomSliderIfSupported: !isMobile, // Disable zoom slider on mobile
			defaultZoomValueIfSupported: isMobile ? 1 : 2,
			useBarCodeDetectorIfSupported: true,
			verbose: false, // Disable verbose logging for better performance
			videoConstraints: isMobile ? {
				facingMode: "environment",
				width: { ideal: 1280 },
				height: { ideal: 720 }
			} : undefined
		};
		
		// Real-time scanning - no manual capture needed
		// QR codes are detected automatically while in viewfinder
		console.log('[QR DEBUG] Creating Html5Qrcode instance for element: qr-reader');
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			console.error('[QR DEBUG] ERROR: qr-reader element not found!');
			mobileDebugLog('ERROR: qr-reader element not found before creating scanner', 'error');
			Swal.fire('Error', 'Elemen scanner tidak ditemukan', 'error');
			return Promise.reject('qr-reader element not found');
		}
		console.log('[QR DEBUG] qr-reader element found:', qrReaderElement);
		
		// DIAGNOSTIC: Verify element state before creating scanner
		const preInitCheck = {
			elementExists: !!qrReaderElement,
			elementId: qrReaderElement ? qrReaderElement.id : 'N/A',
			innerHTML: qrReaderElement ? qrReaderElement.innerHTML.length : 0,
			clientWidth: qrReaderElement ? qrReaderElement.clientWidth : 0,
			clientHeight: qrReaderElement ? qrReaderElement.clientHeight : 0,
			computedDisplay: qrReaderElement ? window.getComputedStyle(qrReaderElement).display : 'N/A'
		};
		console.log('[QR DIAGNOSTIC] Pre-initialization check:', preInitCheck);
		mobileDebugLog(`Pre-init: element=${preInitCheck.elementExists}, size=${preInitCheck.clientWidth}x${preInitCheck.clientHeight}, display=${preInitCheck.computedDisplay}`, 'info');
		
		try {
		qrScanner = new Html5Qrcode("qr-reader");
			console.log('[QR DIAGNOSTIC] Html5Qrcode instance created successfully');
			mobileDebugLog('Html5Qrcode instance created', 'info');
		} catch (error) {
			console.error('[QR DIAGNOSTIC] Failed to create Html5Qrcode instance:', error);
			mobileDebugLog(`ERROR: Failed to create scanner instance - ${error.message}`, 'error');
			Swal.fire('Error', 'Gagal membuat instance scanner: ' + error.message, 'error');
			return Promise.reject(error);
		}
		console.log('[QR DEBUG] Html5Qrcode instance created:', qrScanner);
		
		// Wrap callbacks to ensure they're called
		const wrappedOnScanSuccess = function(decodedText, decodedResult) {
			console.log('[QR DEBUG] wrappedOnScanSuccess called with:', decodedText);
			onScanSuccess(decodedText, decodedResult);
		};
		
		const wrappedOnScanFailure = function(error) {
			// onScanFailure is called VERY frequently during normal scanning
			// (every frame that doesn't contain a QR code - potentially 30+ times per second)
			// This is EXPECTED behavior - failures mean "no QR code in this frame", not an error
			// Only suppress logging - don't treat as actual errors
			// Log first few failures to verify callback is working
			if (!wrappedOnScanFailure.callCount) {
				wrappedOnScanFailure.callCount = 0;
			}
			wrappedOnScanFailure.callCount++;
			if (wrappedOnScanFailure.callCount <= 3) {
				console.log('[QR DEBUG] wrappedOnScanFailure called (this is normal - means no QR in frame):', wrappedOnScanFailure.callCount);
			}
			onScanFailure(error);
		};
		
		// Try environment camera first, then user camera as fallback
		// On mobile, prioritize environment (back camera)
		// Use the isMobile from outer scope (initPatrolFunctionality)
		const cameraConfigs = isMobile ? [
			{ facingMode: "environment" },
			"environment",
			{ facingMode: "user" },
			"user"
		] : [
			{ facingMode: "environment" },
			{ facingMode: "user" },
			"environment",
			"user"
		];
		
		let currentConfigIndex = 0;
		
		function tryStartScanner() {
			// Use isMobile from outer scope (initPatrolFunctionality)
			if (currentConfigIndex >= cameraConfigs.length) {
				// All camera configs failed - reset scanner state
				qrScanner = null;
				forcedScannerTargetId = null;
				
				let errorMsg = 'Tidak dapat mengakses kamera.';
				if (isMobile) {
					errorMsg += '<br><small>Pastikan:<br>1. Izin kamera sudah diberikan<br>2. Menggunakan HTTPS atau localhost<br>3. Browser mendukung kamera</small>';
				} else {
					errorMsg += ' Pastikan izin kamera diberikan.';
				}
				
				// Reset UI
				$('#qr-scanning-status').html(`
					<div class="alert alert-danger">
						<i class="fas fa-exclamation-triangle me-2"></i>
						<strong>Kamera tidak dapat diakses</strong>
						<br>
						<small>Silakan coba lagi atau gunakan tombol "Validasi Manual"</small>
					</div>
				`);
				
				mobileDebugLog('All camera configs failed - cannot access camera', 'error');
				Swal.fire({
					icon: 'error',
					title: 'Error Kamera',
					html: errorMsg
				});
				return;
			}
			
			const cameraConfig = cameraConfigs[currentConfigIndex];
			
			// Show loading message
			$('#qr-scanning-status').html(`
				<div class="alert alert-warning">
					<i class="fas fa-spinner fa-spin me-2"></i>
					<strong>Mengakses kamera...</strong>
					<br>
					<small>Mencoba konfigurasi kamera ${currentConfigIndex + 1}/${cameraConfigs.length}</small>
				</div>
			`);
			
			// Record scanner start time for timing metrics
			qrScannerStartTime = Date.now();
			console.log('[QR TIMING] Scanner started at:', new Date(qrScannerStartTime).toISOString());
			mobileDebugLog(`Scanner starting with config ${currentConfigIndex + 1}/${cameraConfigs.length}`, 'info');
			
			// Clear any existing OCR fallback timer
			if (ocrFallbackTimer) {
				clearTimeout(ocrFallbackTimer);
				ocrFallbackTimer = null;
			}
			
			// Verify Tesseract before setting OCR fallback
			if (verifyTesseractLoaded()) {
				// Set OCR fallback timer (1 second)
				ocrFallbackTimer = setTimeout(function() {
					console.log('[OCR FALLBACK] QR detection timeout - triggering OCR fallback');
					mobileDebugLog('QR detection timeout - triggering OCR fallback', 'warning');
					processOCRFallback();
				}, 1000);
			} else {
				mobileDebugLog('OCR fallback disabled - Tesseract.js not loaded', 'error');
			}
			
			console.log('[QR DEBUG] Starting scanner with config:', {
				cameraConfig: cameraConfig,
				fps: config.fps,
				qrbox: typeof config.qrbox === 'function' ? 'function' : config.qrbox,
				verbose: config.verbose
			});
			
			// DIAGNOSTIC: Final check before starting
			const finalCheck = {
				qrScannerExists: !!qrScanner,
				elementExists: !!document.getElementById('qr-reader'),
				elementVisible: (() => {
					const el = document.getElementById('qr-reader');
					if (!el) return false;
					const style = window.getComputedStyle(el);
					return style.display !== 'none' && style.visibility !== 'hidden';
				})(),
				cameraConfig: cameraConfig,
				configFps: config.fps,
				Html5QrcodeLoaded: typeof Html5Qrcode !== 'undefined'
			};
			console.log('[QR DIAGNOSTIC] Final check before start():', finalCheck);
			mobileDebugLog(`Final check: scanner=${finalCheck.qrScannerExists}, element=${finalCheck.elementExists}, visible=${finalCheck.elementVisible}, library=${finalCheck.Html5QrcodeLoaded}`, 'info');
			
			qrScanner.start(
				cameraConfig,
				config,
				wrappedOnScanSuccess,
				wrappedOnScanFailure
			).then(() => {
				console.log('[QR DEBUG] Scanner start() promise resolved - scanner should be running');
				mobileDebugLog('Scanner start() promise resolved', 'info');
				
				// DIAGNOSTIC: Verify scanner actually started
				setTimeout(() => {
					const postStartCheck = {
						scannerRunning: (() => {
							if (!qrScanner) return false;
							if (typeof qrScanner.isScanning === 'function') {
								return qrScanner.isScanning();
							}
							return qrScanner.isScanning === true;
						})(),
						videoElement: (() => {
							const el = document.getElementById('qr-reader');
							if (!el) return null;
							return el.querySelector('video');
						})(),
						videoPlaying: (() => {
							const el = document.getElementById('qr-reader');
							if (!el) return false;
							const video = el.querySelector('video');
							return video && !video.paused && video.readyState >= 2;
						})(),
						videoReadyState: (() => {
							const el = document.getElementById('qr-reader');
							if (!el) return 'N/A';
							const video = el.querySelector('video');
							return video ? video.readyState : 'N/A';
						})()
					};
					console.log('[QR DIAGNOSTIC] Post-start verification:', postStartCheck);
					mobileDebugLog(`Post-start: running=${postStartCheck.scannerRunning}, video=${!!postStartCheck.videoElement}, playing=${postStartCheck.videoPlaying}, readyState=${postStartCheck.videoReadyState}`, 
						postStartCheck.scannerRunning && postStartCheck.videoPlaying ? 'info' : 'warning');
				}, 1000);
				
				// Verify video element was created
				setTimeout(() => {
					const qrReaderElement = document.getElementById('qr-reader');
					if (qrReaderElement) {
						const video = qrReaderElement.querySelector('video');
						const videoCheck = {
							videoExists: !!video,
							videoSrcObject: video ? !!video.srcObject : false,
							videoReadyState: video ? video.readyState : 'N/A',
							videoPlaying: video ? !video.paused : false
						};
						console.log('[QR DEBUG] Video element check:', videoCheck);
						
						if (!video) {
							console.error('[QR DEBUG] ERROR: Video element not found in qr-reader!');
							mobileDebugLog('ERROR: Video element not found in qr-reader!', 'error');
							$('#qr-scanning-status').html(`
								<div class="alert alert-danger">
									<i class="fas fa-exclamation-triangle me-2"></i>
									<strong>Video tidak ditemukan</strong>
									<br>
									<small>Scanner mungkin tidak berfungsi dengan benar.</small>
								</div>
							`);
						} else if (!video.srcObject) {
							console.warn('[QR DEBUG] WARNING: Video element exists but has no srcObject');
							mobileDebugLog('WARNING: Video element exists but has no srcObject', 'warning');
						} else {
							console.log('[QR DEBUG] Video element looks good - scanner should be working');
							mobileDebugLog(`Video element ready (readyState: ${video.readyState}, playing: ${!video.paused})`, 'info');
						}
					} else {
						mobileDebugLog('ERROR: qr-reader element not found!', 'error');
					}
				}, 500);
				
				// Verify scanner is actually running
				setTimeout(() => {
					let isRunning = false;
					if (qrScanner) {
						if (typeof qrScanner.isScanning === 'function') {
							isRunning = qrScanner.isScanning();
						} else if (qrScanner.isScanning === true) {
							isRunning = true;
						}
					}
					console.log('[QR DEBUG] Scanner running status:', isRunning);
					
					if (!isRunning) {
						console.warn('[QR DEBUG] Scanner reported as not running after start');
						mobileDebugLog('WARNING: Scanner reported as not running after start', 'warning');
						$('#qr-scanning-status').html(`
							<div class="alert alert-warning">
								<i class="fas fa-exclamation-triangle me-2"></i>
								<strong>Scanner mungkin tidak aktif</strong>
								<br>
								<small>Silakan coba klik "Buka Kamera" lagi atau refresh halaman.</small>
							</div>
						`);
					} else {
						console.log('[QR DEBUG] Scanner is confirmed running - QR detection should work');
						mobileDebugLog('Scanner confirmed running - QR detection active', 'info');
					}
				}, 1000);
				
				// Hide loading, show scanning message
				$('#qr-scanning-status').html(`
					<div class="alert alert-success">
						<i class="fas fa-camera me-2"></i>
						<strong>Kamera aktif - Memindai secara real-time</strong>
						<br>
						<small>Arahkan kamera ke QR code patrol. QR akan terdeteksi otomatis saat berada di dalam kotak pemindaian.</small>
						<br>
						<small class="text-muted">Memindai setiap detik...</small>
					</div>
				`);
				
				// Get video track from QR scanner for flash control
				setTimeout(() => {
					const qrReaderElement = document.getElementById('qr-reader');
					if (qrReaderElement) {
						const video = qrReaderElement.querySelector('video');
						if (video && video.srcObject) {
							const stream = video.srcObject;
							const tracks = stream.getVideoTracks();
							if (tracks.length > 0) {
								qrScannerVideoTrack = tracks[0];
								setupQRScannerFlashControl();
								console.log('[QR DEBUG] Video track obtained for flash control');
							} else {
								console.warn('[QR DEBUG] No video tracks found');
							}
						} else {
							console.warn('[QR DEBUG] Video element or srcObject not found');
						}
					} else {
						console.warn('[QR DEBUG] qr-reader element not found');
					}
				}, 500); // Small delay to ensure video element is ready
				
				// Real-time detection enabled - QR codes detected automatically
				// No manual capture needed - scanner detects QR codes continuously while in viewfinder
			}).catch(err => {
				console.error('[QR DEBUG] Camera start error:', err);
				console.error('[QR DEBUG] Error details:', {
					name: err.name,
					message: err.message,
					stack: err.stack
				});
				
				// Clean up failed scanner attempt
				if (qrScanner) {
					try {
						qrScanner.clear();
					} catch (e) {
						console.warn('[QR DEBUG] Error clearing scanner:', e);
					}
				}
				
				// Show detailed error message
				let errorMsg = 'Gagal mengakses kamera. ';
				if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
					errorMsg = 'Izin kamera ditolak. Silakan aktifkan izin kamera di pengaturan browser.';
					mobileDebugLog('Camera permission denied', 'error');
				} else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
					errorMsg = 'Kamera tidak ditemukan di perangkat ini.';
					mobileDebugLog('Camera not found', 'error');
				} else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
					errorMsg = 'Kamera sedang digunakan oleh aplikasi lain.';
					mobileDebugLog('Camera in use by another app', 'error');
				} else if (err.name === 'OverconstrainedError' || err.name === 'ConstraintNotSatisfiedError') {
					errorMsg = 'Konfigurasi kamera tidak didukung. Mencoba konfigurasi lain...';
					mobileDebugLog('Camera config not supported, trying next...', 'warning');
				} else {
					errorMsg += 'Error: ' + (err.message || err.toString());
					mobileDebugLog(`Camera error: ${err.name} - ${err.message}`, 'error');
				}
				
				$('#qr-scanning-status').html(`
					<div class="alert alert-warning">
						<i class="fas fa-spinner fa-spin me-2"></i>
						<strong>Mencoba konfigurasi kamera lain...</strong>
						<br>
						<small>${errorMsg}</small>
						<br>
						<small>Mencoba konfigurasi ${currentConfigIndex + 1}/${cameraConfigs.length}</small>
					</div>
				`);
				
				currentConfigIndex++;
				// Try next config after a short delay
				setTimeout(() => {
					tryStartScanner();
				}, 500);
			});
		}
		
		tryStartScanner();
	}
	
	// Monitor for square detection (QR box overlay)
	function startSquareDetectionMonitor() {
		let lastCaptureTime = 0;
		const captureCooldown = 2000; // Don't capture more than once every 2 seconds
		let squareDetectedCount = 0;
		
		const checkInterval = setInterval(function() {
			if (!isScannerRunning()) {
				clearInterval(checkInterval);
				return;
			}
			
			// Look for the QR detection square overlay
			const qrReaderElement = document.getElementById('qr-reader');
			if (!qrReaderElement) {
				return;
			}
			
			// Html5Qrcode draws a canvas overlay with the detection square
			// Look for canvas elements or SVG elements that indicate detection
			const canvases = qrReaderElement.querySelectorAll('canvas');
			const svgs = qrReaderElement.querySelectorAll('svg');
			
			// Check if square is visible (detection box is drawn)
			let squareVisible = false;
			
			// Method 1: Check for canvas with drawing (detection overlay)
			canvases.forEach(function(canvas) {
				if (canvas.width > 0 && canvas.height > 0) {
					const ctx = canvas.getContext('2d');
					const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
					// Check if canvas has non-transparent pixels (square is drawn)
					for (let i = 3; i < imageData.data.length; i += 4) {
						if (imageData.data[i] > 0) { // Alpha channel > 0 means something is drawn
							squareVisible = true;
							break;
						}
					}
				}
			});
			
			// Method 2: Check for SVG elements (alternative detection indicator)
			if (svgs.length > 0) {
				squareVisible = true;
			}
			
			// Method 3: Check for specific Html5Qrcode detection classes/elements
			const detectionElements = qrReaderElement.querySelectorAll('[id*="qr"], [class*="qr"], [id*="scanner"], [class*="scanner"]');
			if (detectionElements.length > 1) { // More than just the video element
				squareVisible = true;
			}
			
			if (squareVisible) {
				squareDetectedCount++;
				
				// Only capture if enough time has passed since last capture
				const now = Date.now();
				if (now - lastCaptureTime > captureCooldown) {
					lastCaptureTime = now;
					
					// Update status
					$('#qr-scanning-status').html(`
						<div class="alert alert-warning">
							<i class="fas fa-spinner fa-spin me-2"></i>
							<strong>Square terdeteksi! Mengcapture gambar...</strong>
						</div>
					`);
					
					// Capture and decode
					setTimeout(function() {
						captureSquareRegion();
					}, 300); // Small delay to ensure square is stable
				}
			}
		}, 500); // Check every 500ms
	}
	
	// Capture the square region from video
	function captureSquareRegion() {
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			return;
		}
		
		const video = qrReaderElement.querySelector('video');
		if (!video || video.readyState !== 4) {
			return;
		}
		
		// Get video dimensions
		const videoWidth = video.videoWidth;
		const videoHeight = video.videoHeight;
		
		// Calculate square region (center 80% of video, as per qrbox config)
		const squareSize = Math.min(videoWidth, videoHeight) * 0.8;
		const squareX = (videoWidth - squareSize) / 2;
		const squareY = (videoHeight - squareSize) / 2;
		
		// Create canvas to capture square region
		const canvas = document.createElement('canvas');
		canvas.width = squareSize;
		canvas.height = squareSize;
		const ctx = canvas.getContext('2d');
		
		// Draw only the square region from video
		ctx.drawImage(
			video,
			squareX, squareY, squareSize, squareSize, // Source: square region
			0, 0, squareSize, squareSize // Destination: full canvas
		);
		
		// Convert to blob and try to decode
		canvas.toBlob(function(blob) {
			if (!blob) {
				return;
			}
			
			const file = new File([blob], 'qr-square.jpg', { type: 'image/jpeg' });
			const imageDataUrl = canvas.toDataURL('image/jpeg');
			
			// Stop camera scanner first before file scan
			// Check if scanner is running (handle both function and property)
			let isScanning = false;
			if (qrScanner) {
				if (typeof qrScanner.isScanning === 'function') {
					isScanning = qrScanner.isScanning();
				} else if (qrScanner.isScanning === true) {
					isScanning = true;
				}
			}
			
			if (isScanning) {
				qrScanner.stop().then(() => {
					decodeCapturedFile(file, imageDataUrl);
				}).catch(err => {
					// Try to decode anyway with a new instance
					decodeCapturedFile(file, imageDataUrl, true);
				});
			} else {
				// Scanner not running, decode directly
				decodeCapturedFile(file, imageDataUrl);
			}
		}, 'image/jpeg', 0.95);
	}
	
	// Decode captured file (helper function)
	function decodeCapturedFile(file, imageDataUrl, restartScanner = true) {
		// Use Html5Qrcode static method or create new instance for file scanning
		if (typeof Html5Qrcode !== 'undefined' && Html5Qrcode.scanFile) {
			Html5Qrcode.scanFile(file, true)
				.then(decodedText => {
					// Restart scanner if needed
					if (restartScanner && $('#qrScannerModal').is(':visible')) {
						setTimeout(() => {
							startQRScanner();
						}, 500);
					}
					
					handlePatrolDetection(decodedText);
				})
				.catch(err => {
					// Decode failed - show error message
					Swal.fire({
						icon: 'error',
						title: 'Decode Gagal',
						text: 'Gagal membaca QR code dari gambar. Pastikan QR code jelas dan dalam kotak deteksi.',
						confirmButtonText: 'OK'
					});
					
					// Restart scanner if needed
					if (restartScanner && $('#qrScannerModal').is(':visible')) {
						setTimeout(() => {
							startQRScanner();
						}, 1000);
					}
				});
		} else {
			// Fallback: show error
			Swal.fire({
				icon: 'error',
				title: 'Error',
				text: 'Library QR scanner tidak tersedia.',
				confirmButtonText: 'OK'
			});
			
			// Restart scanner if needed
			if (restartScanner && $('#qrScannerModal').is(':visible')) {
				setTimeout(() => {
					startQRScanner();
				}, 1000);
			}
		}
	}
	
	// Manual capture and decode QR from video frame
	function captureAndDecodeQR() {
		// Find video element in qr-reader
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			Swal.fire('Error', 'QR reader tidak ditemukan', 'error');
			return;
		}
		
		// Find video element
		const video = qrReaderElement.querySelector('video');
		if (!video || video.readyState !== 4) {
			Swal.fire('Error', 'Video tidak siap', 'error');
			return;
		}
		
		// Create canvas to capture frame
		const canvas = document.createElement('canvas');
		canvas.width = video.videoWidth;
		canvas.height = video.videoHeight;
		const ctx = canvas.getContext('2d');
		ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
		
		// Convert to blob
		canvas.toBlob(function(blob) {
			if (!blob) {
				Swal.fire('Error', 'Gagal membuat gambar', 'error');
				return;
			}
			
			// Create file from blob
			const file = new File([blob], 'captured-qr.jpg', { type: 'image/jpeg' });
			const imageDataUrl = canvas.toDataURL('image/jpeg');
			
			// Stop camera scanner first before file scan
			// Check if scanner is running (handle both function and property)
			let isScanning = false;
			if (qrScanner) {
				if (typeof qrScanner.isScanning === 'function') {
					isScanning = qrScanner.isScanning();
				} else if (qrScanner.isScanning === true) {
					isScanning = true;
				}
			}
			
			if (isScanning) {
				qrScanner.stop().then(() => {
					decodeCapturedFile(file, imageDataUrl);
				}).catch(err => {
					// Try to decode anyway with static method
					decodeCapturedFile(file, imageDataUrl, true);
				});
			} else {
				// Scanner not running, decode directly
				decodeCapturedFile(file, imageDataUrl);
			}
		}, 'image/jpeg', 0.95);
	}
	
	// QR scan success - WhatsApp-like behavior: auto-process without confirmation
	function onScanSuccess(decodedText, decodedResult) {
		// Clear OCR fallback timer since QR was detected
		if (ocrFallbackTimer) {
			clearTimeout(ocrFallbackTimer);
			ocrFallbackTimer = null;
			console.log('[QR SUCCESS] OCR fallback timer cleared - QR detected successfully');
		}
		
		// Calculate detection time
		const detectionTime = qrScannerStartTime ? ((Date.now() - qrScannerStartTime) / 1000).toFixed(2) : null;
		
		console.log('[QR SUCCESS] ========== QR CODE DETECTED ==========');
		console.log('[QR SUCCESS] Decoded text:', decodedText);
		console.log('[QR SUCCESS] Detection time:', detectionTime !== null ? detectionTime + ' seconds' : 'N/A');
		console.log('[QR SUCCESS] Company ID:', currentCompanyId);
		console.log('[QR SUCCESS] Patrol options:', patrolOptions ? patrolOptions.length : 0);
		mobileDebugLog(`QR Code detected: ${decodedText} (${detectionTime !== null ? detectionTime + 's' : 'N/A'})`, 'info');
		
		const scannerRunning = isScannerRunning();
		if (!scannerRunning) {
			console.warn('[QR SUCCESS] WARNING: Scanner not running when QR detected!');
		}
		
		// Show immediate feedback without stopping scanner
		const timingInfo = detectionTime !== null 
			? `<br><small class="text-muted">Ditemukan dalam ${detectionTime} detik</small>`
			: '';
		
		// Ensure status element is visible and update immediately
		$('#qr-scanning-status').show().html(`
			<div class="alert alert-info">
				<i class="fas fa-spinner fa-spin me-2"></i>
				<strong>QR Code terdeteksi!</strong>
				<br>
				<small>Memvalidasi...</small>
				${timingInfo}
			</div>
		`);
		
		// Process immediately without confirmation (like WhatsApp)
		// Only stop scanner if QR is successfully processed
		handlePatrolDetection(decodedText, { autoProcess: true, detectionTime: detectionTime });
	}
	
	// QR scan failure
	// NOTE: This is called VERY frequently (30+ times per second) during normal scanning
	// It's called every time the scanner processes a frame and doesn't find a QR code
	// This is EXPECTED and NORMAL - it does NOT mean there's an error
	// Only actual errors (camera issues, etc.) should be logged
	function onScanFailure(error) {
		// Completely suppress failure logs - they're too noisy and confusing
		// Failures during scanning are expected (means "no QR code in this frame")
		// Only log if it's an actual error (not just "QR code not found")
		const errorMessage = error ? error.toString() : '';
		const isActualError = errorMessage && (
			errorMessage.includes('camera') ||
			errorMessage.includes('permission') ||
			errorMessage.includes('device') ||
			errorMessage.includes('stream') ||
			errorMessage.includes('NotAllowedError') ||
			errorMessage.includes('NotFoundError') ||
			errorMessage.includes('NotReadableError')
		);
		
		// Only log actual errors, not "QR code not found" messages
		if (isActualError) {
			console.warn('[QR DEBUG] Actual scanner error:', error);
		}
		// Otherwise, silently ignore - this is normal scanning behavior
	}
	
	/**
	 * Process OCR fallback when QR scanning fails after 1 second
	 * Captures video frame and runs Tesseract.js OCR to extract PATROL_ pattern
	 */
	function processOCRFallback() {
		// Only process if OCR timer was actually triggered (not cleared by QR success)
		if (!ocrFallbackTimer) {
			console.log('[OCR FALLBACK] Timer was cleared - QR likely detected, skipping OCR');
			return;
		}
		
		// Clear the timer to prevent multiple triggers
		ocrFallbackTimer = null;
		
		console.log('[OCR FALLBACK] Starting OCR processing...');
		mobileDebugLog('OCR fallback triggered - starting OCR processing', 'info');
		
		// Check if Tesseract.js is available
		if (typeof Tesseract === 'undefined') {
			console.error('[OCR FALLBACK] Tesseract.js not loaded');
			mobileDebugLog('ERROR: Tesseract.js not loaded - OCR fallback disabled', 'error');
			// Show error but don't break the flow - user can still use manual input
		$('#qr-scanning-status').html(`
			<div class="alert alert-warning">
					<i class="fas fa-exclamation-triangle me-2"></i>
					<strong>OCR tidak tersedia</strong>
					<br>
					<small>Gunakan tombol "Validasi Manual" atau "Masukkan QR Code Manual"</small>
				</div>
			`);
			return;
		}
		
		// Get video element from QR scanner
		const qrReaderElement = document.getElementById('qr-reader');
		if (!qrReaderElement) {
			console.error('[OCR FALLBACK] qr-reader element not found');
			return;
		}
		
		// Find video element inside qr-reader
		const videoElement = qrReaderElement.querySelector('video');
		if (!videoElement) {
			console.error('[OCR FALLBACK] Video element not found in qr-reader');
			$('#qr-scanning-status').html(`
				<div class="alert alert-warning">
					<i class="fas fa-exclamation-triangle me-2"></i>
					<strong>Video tidak tersedia untuk OCR</strong>
					<br>
					<small>Gunakan tombol "Validasi Manual" atau "Masukkan QR Code Manual"</small>
				</div>
			`);
			return;
		}
		
		// Show OCR processing status
		$('#qr-scanning-status').html(`
			<div class="alert alert-info">
				<i class="fas fa-spinner fa-spin me-2"></i>
				<strong>Memproses OCR...</strong>
				<br>
				<small>Mengambil frame dan membaca teks...</small>
			</div>
		`);
		
		// Capture video frame to canvas
		const canvas = document.createElement('canvas');
		canvas.width = videoElement.videoWidth;
		canvas.height = videoElement.videoHeight;
		const ctx = canvas.getContext('2d');
		ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
		
		// Convert canvas to image data
		canvas.toBlob(function(blob) {
			if (!blob) {
				console.error('[OCR FALLBACK] Failed to capture frame');
				$('#qr-scanning-status').html(`
					<div class="alert alert-warning">
						<i class="fas fa-exclamation-triangle me-2"></i>
						<strong>Gagal mengambil frame</strong>
						<br>
						<small>Gunakan tombol "Validasi Manual" atau "Masukkan QR Code Manual"</small>
					</div>
				`);
				return;
			}
			
			// Initialize Tesseract worker if not already initialized
			if (!tesseractWorker) {
				console.log('[OCR FALLBACK] Initializing Tesseract worker...');
				tesseractWorker = Tesseract.createWorker();
			}
			
			// Run OCR on the captured image
			Tesseract.recognize(blob, 'eng', {
				logger: m => {
					if (m.status === 'recognizing text') {
						console.log('[OCR FALLBACK] Progress:', Math.round(m.progress * 100) + '%');
					}
				}
			}).then(function(result) {
				const ocrText = result.data.text || '';
				console.log('[OCR FALLBACK] OCR result:', ocrText);
				
				// Display all detected OCR text in the OCR result section (lines 421-431)
				if (ocrText.trim()) {
					$('#ocr-detected-text').text(ocrText);
					
					// Force display on mobile browsers - remove Bootstrap d-none class and ensure visibility
					const ocrResultEl = $('#ocr-scan-result');
					if (ocrResultEl.length) {
						// Remove Bootstrap d-none class
						ocrResultEl.removeClass('d-none');
						// Force CSS overrides for mobile compatibility
						ocrResultEl.css('display', 'block');
						ocrResultEl.css('visibility', 'visible');
						ocrResultEl.css('opacity', '1');
						// Add inline style with !important for mobile browsers
						const currentStyle = ocrResultEl.attr('style') || '';
						ocrResultEl.attr('style', currentStyle + '; display: block !important; visibility: visible !important;');
						
						// Ensure parent containers are visible
						const parentWrapper = ocrResultEl.closest('#qr-inline-wrapper');
						if (parentWrapper.length) {
							parentWrapper.css('display', 'block');
							parentWrapper.show();
						}
						
						// Scroll into view on mobile
						const ocrResultDom = ocrResultEl[0];
						if (ocrResultDom) {
							setTimeout(() => {
								ocrResultDom.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
							}, 100);
						}
						
						// Debug logging for mobile
						console.log('[OCR FALLBACK] OCR result element displayed:', {
							exists: ocrResultEl.length > 0,
							hasDNone: ocrResultEl.hasClass('d-none'),
							display: ocrResultEl.css('display'),
							visibility: ocrResultEl.css('visibility'),
							isVisible: ocrResultEl.is(':visible'),
							parentVisible: parentWrapper.length > 0 ? parentWrapper.is(':visible') : 'N/A'
						});
					} else {
						console.error('[OCR FALLBACK] OCR result element not found in DOM');
					}
					
					// Update status
					$('#qr-scanning-status').html(`
						<div class="alert alert-info">
							<i class="fas fa-eye me-2"></i>
							<strong>Teks terdeteksi via OCR!</strong>
							<br>
							<small>Memproses teks yang terdeteksi...</small>
						</div>
					`).show();
				}
				
				// Extract PATROL_ pattern using regex - format: PATROL_XXX_XXX_YYYYMMDDHHMMSS
				// More accurate pattern: PATROL_ + 3 digits (company) + _ + 3 digits (sequence) + _ + timestamp
				const patrolPattern = /PATROL_\d{3}_\d{3}_\d+/i;
				const match = ocrText.match(patrolPattern);
				
				if (match && match[0]) {
					const extractedCode = match[0];
					console.log('[OCR FALLBACK] PATROL code extracted:', extractedCode);
					mobileDebugLog(`OCR success: PATROL code extracted - ${extractedCode}`, 'info');
					
					// Process the extracted code as if it were a QR code
					handlePatrolDetection(extractedCode, { autoProcess: true, source: 'ocr' });
				} else {
					console.log('[OCR FALLBACK] No PATROL pattern found in OCR text');
					mobileDebugLog('OCR completed but no PATROL pattern found', 'warning');
					$('#qr-scanning-status').html(`
						<div class="alert alert-warning">
							<i class="fas fa-exclamation-triangle me-2"></i>
							<strong>Kode Patrol tidak ditemukan</strong>
							<br>
							<small>Gunakan tombol "Validasi Manual" atau "Masukkan QR Code Manual"</small>
						</div>
					`);
				}
			}).catch(function(error) {
				console.error('[OCR FALLBACK] OCR processing error:', error);
				mobileDebugLog(`OCR processing error: ${error.message || error}`, 'error');
				$('#qr-scanning-status').html(`
					<div class="alert alert-warning">
						<i class="fas fa-exclamation-triangle me-2"></i>
						<strong>Error saat memproses OCR</strong>
						<br>
						<small>Gunakan tombol "Validasi Manual" atau "Masukkan QR Code Manual"</small>
					</div>
				`);
			});
		}, 'image/jpeg', 0.95);
	}
	
	function handlePatrolDetection(barcode, options = {}) {
		console.log('[QR DEBUG] handlePatrolDetection called');
		console.log('[QR DEBUG] Barcode:', barcode);
		console.log('[QR DEBUG] Options:', options);
		
		const isTestMode = options.isTest === true;
		const manualTrigger = options.manualTrigger === true;
		const autoProcess = options.autoProcess === true; // Auto-process without confirmation (WhatsApp-like)
		
		// PRIORITY: If forcedScannerTargetId is set (user clicked a scan button), ALWAYS use it
		// This ensures user's choice (e.g., patrol 18) is respected, regardless of scanned barcode
		const buttonClicked = forcedScannerTargetId && forcedScannerTargetId !== null && forcedScannerTargetId !== '';
		
		console.log('[QR DEBUG] Button clicked:', buttonClicked, 'Forced ID:', forcedScannerTargetId);
		console.log('[QR DEBUG] Manual trigger:', manualTrigger);
		
		// If button clicked OR manual trigger: completely bypass ALL QR validation
		// Use the selected patrol ID directly, ignore any scanned barcode
		if (buttonClicked || manualTrigger) {
			console.log('[QR DEBUG] Using button/manual trigger path');
			// Get the patrol ID to use - ALWAYS use forcedScannerTargetId if it's set
			const patrolIdToUse = buttonClicked ? String(forcedScannerTargetId) : (manualTrigger && forcedScannerTargetId ? String(forcedScannerTargetId) : null);
			
			if (!patrolIdToUse) {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Patrol ID tidak valid. Silakan pilih patrol terlebih dahulu.'
				});
				restartScannerWithDelay();
				return;
			}
			
			// Skip ALL QR validation and barcode matching
			// Just use button's patrol ID directly - IGNORE scanned barcode completely
			const buttonPatrol = findPatrolById(patrolIdToUse);
			if (!buttonPatrol) {
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Patrol yang dipilih tidak ditemukan.'
				});
				restartScannerWithDelay();
				return;
			}
			
			// Use button's patrol ID directly, no validation - IGNORE scanned barcode
			selectPatrolForScan(patrolIdToUse);
			
			scannedPatrolData = Object.assign({}, buttonPatrol);
			if (!scannedPatrolData.nama_company && window.nearestCompany) {
				scannedPatrolData.nama_company = window.nearestCompany.nama_company || '';
			}
			
			$('#id_patrol').val(patrolIdToUse);
			// Use button's barcode if available, otherwise use scanned barcode (but patrol ID is from button)
			$('#scanned_barcode').val(buttonPatrol.barcode || barcode || '');
			$('#qr-code-text').text(barcode || buttonPatrol.barcode || '');
			$('#qr-result').show();
			
			$('#scanned-patrol-info').html(`
				<strong>${buttonPatrol.nama_patrol}</strong><br>
				<small>${scannedPatrolData.nama_company || (window.nearestCompany ? window.nearestCompany.nama_company : '')}</small>
			`);
			$('#qr-scan-result').show();
			
			// Auto-fill judul_activity with patrol name if field is empty
			const judulField = $('#judul_activity');
			if (judulField.length && (!judulField.val() || judulField.val().trim() === '')) {
				const patrolName = buttonPatrol.nama_patrol || '';
				if (patrolName) {
					judulField.val(patrolName);
				}
			}
			
			markPatrolAsScanned(patrolIdToUse);
			setNextPatrolInfo(getNextPendingPatrol());
			
			setTimeout(() => {
				showStep(2);
				// Ensure text is a string
				const patrolName = buttonPatrol && buttonPatrol.nama_patrol 
					? String(buttonPatrol.nama_patrol) 
					: 'Patrol siap diisi.';
				Swal.fire({
					icon: 'success',
					title: 'QR Terdeteksi',
					text: patrolName,
					timer: 1500,
					showConfirmButton: false
				});
			}, 200);
			
			// Keep forcedScannerTargetId for saving later - DO NOT reset it here
			// It will be reset after activity is saved or form is reset
			return;
		}
		
		// Normal flow (no button clicked, no forcedScannerTargetId): validate barcode and match to patrol
		console.log('[QR DEBUG] Using normal QR validation path');
		
		if (!barcode) {
			console.log('[QR DEBUG] ERROR: Barcode is empty');
			Swal.fire('Info', 'Barcode tidak tersedia. Silakan lakukan scan ulang.', 'info');
			restartScannerWithDelay();
			return;
		}
		
		if (!currentCompanyId) {
			console.log('[QR DEBUG] ERROR: Current company ID is not set');
			Swal.fire({
				icon: 'error',
				title: 'Company Belum Terdeteksi',
				text: 'Pastikan GPS aktif dan Anda berada di lokasi company, atau pilih company secara manual.',
				confirmButtonText: 'OK'
			});
			return;
		}
		
		if (!patrolOptions || patrolOptions.length === 0) {
			console.log('[QR DEBUG] ERROR: No patrol options available');
			Swal.fire({
				icon: 'info',
				title: 'Data Patrol Tidak Tersedia',
				text: 'Data patrol belum tersedia untuk company ini. Silakan hubungi admin.',
				confirmButtonText: 'OK'
			});
			restartScannerWithDelay();
			return;
		}
		
		console.log('[QR DEBUG] Searching for patrol with barcode:', barcode);
		console.log('[QR DEBUG] Available patrols:', patrolOptions.map(p => ({ id: p.id_patrol, barcode: p.barcode, nama: p.nama_patrol })));
		
		const matchedPatrol = findPatrolByBarcode(barcode);
		
		console.log('[QR DEBUG] Matched patrol:', matchedPatrol);
		
		if (!matchedPatrol) {
			console.log('[QR DEBUG] ERROR: No patrol found matching barcode');
			
			// Build list of available barcodes for debugging
			const availableBarcodes = patrolOptions.map(p => p.barcode || '(no barcode)').filter(b => b !== '(no barcode)');
			const availableBarcodesList = availableBarcodes.length > 0 
				? availableBarcodes.slice(0, 10).join(', ') + (availableBarcodes.length > 10 ? '...' : '')
				: 'Tidak ada barcode tersedia';
			
			console.log('[QR DEBUG] Scanned barcode:', barcode);
			console.log('[QR DEBUG] Available barcodes:', availableBarcodes);
			
			Swal.fire({
				icon: 'error',
				title: 'QR Code Tidak Dikenali',
				html: `
					<div class="text-start">
						<p><strong>Barcode yang di-scan:</strong></p>
						<p class="mb-2"><code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px;">${barcode}</code></p>
						<p class="mb-2"><strong>Barcode yang tersedia:</strong></p>
						<p class="small text-muted mb-3">${availableBarcodesList}</p>
						<p class="small text-muted">QR Code ini tidak cocok dengan data patrol untuk company ini. Pastikan QR code yang digunakan sesuai dengan company yang terdeteksi.</p>
					</div>
				`,
				confirmButtonText: 'OK',
				footer: '<button type="button" class="btn btn-link text-primary p-0" id="btn-manual-qr-entry">Masukkan QR Code Manual</button>'
			}).then(() => {
			restartScannerWithDelay();
			});
			
			// Add click handler for manual entry button
			setTimeout(() => {
				$('#btn-manual-qr-entry').on('click', function() {
					Swal.close();
					showManualQREntry();
				});
			}, 100);
			
			return;
		}
		
		console.log('[QR DEBUG] Patrol matched successfully:', matchedPatrol.nama_patrol, 'ID:', matchedPatrol.id_patrol);
		
		// Stop scanner only when QR is successfully matched (WhatsApp-like behavior)
		try {
			if (isScannerRunning()) {
				qrScanner.stop().then(() => {
					try {
						qrScanner.clear();
					} catch(e) {
						console.warn('[QR DEBUG] Error clearing scanner:', e);
					}
				}).catch(err => {
					console.warn('[QR DEBUG] Error stopping scanner:', err);
				});
			}
		} catch(e) {
			console.warn('[QR DEBUG] Error in scanner stop:', e);
		}
		
		// Free scan (no button clicked): use normal QR matching
		// NOTE: No sequence validation - users can scan any patrol in any order
		selectPatrolForScan(matchedPatrol.id_patrol);
		
		scannedPatrolData = Object.assign({}, matchedPatrol);
		if (!scannedPatrolData.nama_company && window.nearestCompany) {
			scannedPatrolData.nama_company = window.nearestCompany.nama_company || '';
		}
		
		$('#id_patrol').val(matchedPatrol.id_patrol);
		$('#scanned_barcode').val(matchedPatrol.barcode);
		$('#qr-code-text').text(barcode);
		$('#qr-result').show();
		
		// Display in appropriate section based on detection method
		if (options.source === 'ocr') {
			// OCR detection - show in OCR section (lines 421-431)
			$('#ocr-patrol-info').html(`
				<strong>${matchedPatrol.nama_patrol}</strong><br>
				<small>${scannedPatrolData.nama_company || (window.nearestCompany ? window.nearestCompany.nama_company : '')}</small>
			`);
			
			// Force display on mobile browsers - remove Bootstrap d-none class and ensure visibility
			const ocrResultEl = $('#ocr-scan-result');
			if (ocrResultEl.length) {
				// Remove Bootstrap d-none class
				ocrResultEl.removeClass('d-none');
				// Force CSS overrides for mobile compatibility
				ocrResultEl.css('display', 'block');
				ocrResultEl.css('visibility', 'visible');
				ocrResultEl.css('opacity', '1');
				// Add inline style with !important for mobile browsers
				const currentStyle = ocrResultEl.attr('style') || '';
				ocrResultEl.attr('style', currentStyle + '; display: block !important; visibility: visible !important;');
				
				// Ensure parent containers are visible
				const parentWrapper = ocrResultEl.closest('#qr-inline-wrapper');
				if (parentWrapper.length) {
					parentWrapper.css('display', 'block');
					parentWrapper.show();
				}
				
				// Scroll into view on mobile
				const ocrResultDom = ocrResultEl[0];
				if (ocrResultDom) {
					setTimeout(() => {
						ocrResultDom.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					}, 100);
				}
				
				// Debug logging for mobile
				console.log('[PATROL DETECTION] OCR result element displayed:', {
					exists: ocrResultEl.length > 0,
					hasDNone: ocrResultEl.hasClass('d-none'),
					display: ocrResultEl.css('display'),
					visibility: ocrResultEl.css('visibility'),
					isVisible: ocrResultEl.is(':visible'),
					parentVisible: parentWrapper.length > 0 ? parentWrapper.is(':visible') : 'N/A'
				});
			} else {
				console.error('[PATROL DETECTION] OCR result element not found in DOM');
			}
		} else {
			// QR detection - show in QR section (lines 432-446)
			$('#scanned-patrol-info').html(`
				<strong>${matchedPatrol.nama_patrol}</strong><br>
				<small>${scannedPatrolData.nama_company || (window.nearestCompany ? window.nearestCompany.nama_company : '')}</small>
			`);
			$('#qr-scan-result').show();
		}
		
		// Auto-fill judul_activity with patrol name if field is empty
		const judulField = $('#judul_activity');
		if (judulField.length && (!judulField.val() || judulField.val().trim() === '')) {
			const patrolName = matchedPatrol.nama_patrol || '';
			if (patrolName) {
				judulField.val(patrolName);
			}
		}
		
		markPatrolAsScanned(matchedPatrol.id_patrol);
		setNextPatrolInfo(getNextPendingPatrol());
		
		// Show success status with timing info - ensure it's visible immediately
		const detectionTime = options.detectionTime || null;
		const timingDisplay = detectionTime !== null 
			? `<br><small class="text-muted">Ditemukan dalam ${detectionTime} detik</small>`
			: '';
		const sourceDisplay = options.source === 'ocr'
			? '<br><small class="text-muted"><i class="fas fa-eye me-1"></i>Ditemukan via OCR</small>'
			: '';
		
		// Update status immediately and ensure it's visible
		$('#qr-scanning-status').show().html(`
			<div class="alert alert-success">
				<i class="fas fa-check-circle me-2"></i>
				<strong>QR Code Berhasil!</strong>
				<br>
				<small>${matchedPatrol.nama_patrol}</small>
				${timingDisplay}
				${sourceDisplay}
			</div>
		`);
		
		// Show success toast immediately
			const matchedPatrolName = matchedPatrol && matchedPatrol.nama_patrol 
				? String(matchedPatrol.nama_patrol) 
				: 'Patrol siap diisi.';
		const successTitle = options.source === 'ocr' ? 'QR Terdeteksi (via OCR)' : 'QR Terdeteksi';
		
			Swal.fire({
				icon: 'success',
			title: successTitle,
				text: matchedPatrolName,
			timer: 2000,
			showConfirmButton: false,
			toast: true,
			position: 'top-end'
		});
		
		setTimeout(() => {
			showStep(2);
		}, 200);
	}
	
	// Manual QR code entry function
	function showManualQREntry() {
		if (typeof Swal === 'undefined') {
			alert('Silakan masukkan QR code secara manual melalui input field.');
			return;
		}
		
		Swal.fire({
			title: 'Masukkan QR Code Manual',
			html: `
				<div class="text-start">
					<p class="mb-3">Masukkan barcode QR code secara manual:</p>
					<input type="text" class="form-control" id="manual-qr-input" placeholder="Contoh: PATROL-001" autofocus>
					<small class="text-muted d-block mt-2">Masukkan barcode yang tertera pada QR code Anda.</small>
				</div>
			`,
			showCancelButton: true,
			confirmButtonText: 'Gunakan',
			cancelButtonText: 'Batal',
			confirmButtonColor: '#0d6efd',
			cancelButtonColor: '#6c757d',
			preConfirm: () => {
				const input = document.getElementById('manual-qr-input');
				const value = input ? input.value.trim() : '';
				if (!value) {
					Swal.showValidationMessage('Barcode tidak boleh kosong');
					return false;
				}
				return value;
			}
		}).then((result) => {
			if (result.isConfirmed && result.value) {
				console.log('[QR DEBUG] Manual QR entry:', result.value);
				// Process the manually entered QR code
				handlePatrolDetection(result.value, { manualTrigger: false });
			}
		});
	}
	
	// Helper function to reset scanner UI
	function resetScannerUI() {
		return stopQRScanner().then(() => {
			if (isQrInModal) {
				$('#qrScannerModal').modal('hide');
			} else {
				resetQrInlineStatus();
			}
		});
	}
	
	// Close modal and stop scanner
	$('#qrScannerModal').on('hidden.bs.modal', function() {
		returnScannerToInline();
		stopQRScanner();
	});
	
	// Save activity from step 3
	$('#btn-save-activity').click(function() {
		saveActivity();
	});
	
	// Save activity function
	function saveActivity() {
		const id_company = $('#id_company').val();
		// Use button's patrol ID if button was clicked, otherwise use form value
		const id_patrol = (forcedScannerTargetId && forcedScannerTargetId !== null) 
			? String(forcedScannerTargetId) 
			: $('#id_patrol').val();
		const scanned_barcode = $('#scanned_barcode').val();
		const judul_activity = $('#judul_activity').val();
		const deskripsi_activity = $('#deskripsi_activity').val();
		const foto = $('#foto_activity').val();
		
		// Get patrol requirement status from nearest company
		let isPatrolRequired = false;
		if (window.nearestCompany && window.nearestCompany.isPatrolRequired === 1) {
			isPatrolRequired = true;
		}
		
		if (!id_company) {
			Swal.fire('Error', 'Lokasi company tidak valid', 'error');
			return;
		}
		
		// Only require patrol if it's mandatory
		if (isPatrolRequired && !id_patrol) {
			Swal.fire('Error', 'Titik patroli harus dipilih', 'error');
			return;
		}
		
		if (!judul_activity.trim()) {
			Swal.fire('Error', 'Judul activity harus diisi', 'error');
			return;
		}
		
		// Disable button and show loading
		$('#btn-save-activity').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengambil GPS...');
		
		// Get fresh GPS location before saving
		getGPSLocation()
			.then(location => {
				// Reset error flag on successful GPS
				gpsErrorShown = false;
				gpsErrorShownTime = 0;
				// Save with fresh GPS location
				performSaveActivity(id_company, id_patrol, scanned_barcode, judul_activity, deskripsi_activity, foto, location);
			})
			.catch(err => {
				console.warn('GPS Error, using cached location:', err);
				// Use cached location if GPS fails - don't show error modal here
				// Error was already shown in btn-capture handler if applicable
				// Just proceed with cached location silently
				performSaveActivity(id_company, id_patrol, scanned_barcode, judul_activity, deskripsi_activity, foto, currentLocation);
			});
	}
	
	// Perform actual save with location
	function performSaveActivity(id_company, id_patrol, scanned_barcode, judul_activity, deskripsi_activity, foto, location) {
		const data = {
			id_company: id_company,
			id_patrol: id_patrol,
			barcode_scanned: scanned_barcode,
			judul_activity: judul_activity,
			deskripsi_activity: deskripsi_activity,
			foto: foto,
			location: location
		};
		
		const data_encoded = btoa(JSON.stringify(data));
		
		$('#btn-save-activity').html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
		
		$.ajax({
			url: base_url + 'mobile-activity/ajaxSaveActivity',
			type: 'POST',
			data: { data: data_encoded },
			dataType: 'json',
			success: function(response) {
				$('#btn-save-activity').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Activity');
				
				if (response.status === 'ok') {
					Swal.fire({
						icon: 'success',
						title: 'Berhasil!',
						text: 'Activity berhasil disimpan',
						confirmButtonText: 'OK'
					}).then(() => {
						// Reset scanner UI, then reset form
						resetScannerUI().then(() => {
							// Stop activity camera as well
							stopActivityCamera();
							// Reset form and go back to step 1
							resetForm();
							showStep(1);
						});
					});
				} else {
					// Ensure message is a string, not an object
					let errorMessage = 'Gagal menyimpan activity';
					if (response.message) {
						if (typeof response.message === 'string') {
							errorMessage = response.message;
						} else if (typeof response.message === 'object') {
							errorMessage = JSON.stringify(response.message);
						}
					}
					Swal.fire('Error', errorMessage, 'error');
				}
			},
			error: function() {
				$('#btn-save-activity').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Activity');
				Swal.fire('Error', 'Gagal menyimpan activity', 'error');
			}
		});
	}
	
	// Camera switch functionality
	$('#btn-switch-camera').click(function() {
		switchActivityCamera();
	});
	
	// Function to switch between front and back camera
	async function switchActivityCamera() {
		if (!activityCameraStream) {
			activityCameraFacingMode = activityCameraFacingMode === 'environment' ? 'user' : 'environment';
			try {
				await startActivityCamera({ suppressError: true });
			} catch (err) {
				activityCameraFacingMode = lastSuccessfulFacingMode;
			}
			return;
		}
		
		const previousMode = activityCameraFacingMode;
		const desiredMode = activityCameraFacingMode === 'environment' ? 'user' : 'environment';
		activityCameraFacingMode = desiredMode;
		updateCameraStatus('Mengganti kamera...', 'info');
		
		activityCameraStream.getTracks().forEach(track => track.stop());
		activityCameraStream = null;
		
		try {
			await startActivityCamera({ suppressError: true });
			const label = desiredMode === 'environment' ? 'Kamera belakang aktif' : 'Kamera depan aktif';
			updateCameraStatus(label, 'success');
		} catch (err) {
			console.warn('Switch camera failed, reverting to previous camera', err);
			activityCameraFacingMode = previousMode;
			updateCameraStatus('Kamera depan tidak tersedia di perangkat ini. Menggunakan kamera belakang.', 'warning');
			try {
				await startActivityCamera({ suppressError: true });
			} catch (fallbackErr) {
				console.error('Failed to restore previous camera', fallbackErr);
				updateCameraStatus('Kamera tidak dapat digunakan.', 'error');
			}
		}
	}
	
	// Function to start camera for activity
	async function startActivityCamera(options = {}) {
		const { suppressError = false } = options;
		try {
			const constraints = {
				video: {
					facingMode: activityCameraFacingMode
				}
			};
			
			const stream = await navigator.mediaDevices.getUserMedia(constraints);
			activityCameraStream = stream;
			
			const video = document.getElementById('my_camera');
			if (video) {
				video.srcObject = stream;
				video.play();
			}
			
			cameraVideoTrack = stream.getVideoTracks()[0];
			lastSuccessfulFacingMode = activityCameraFacingMode;
			const label = activityCameraFacingMode === 'environment' ? 'Kamera belakang aktif' : 'Kamera depan aktif';
			updateCameraStatus(label, 'success');
			setupFlashControl();
		} catch (err) {
			console.error('Error accessing camera:', err);
			if (!suppressError) {
				Swal.fire('Error', 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.', 'error');
			}
			restorePatrolProgressAfterCamera();
			updateCameraStatus('Kamera tidak bisa diakses.', 'error');
			hideFlashControl('Lampu tidak tersedia.');
			throw err;
		}
	}
	
	// Stop camera stream
	function stopActivityCamera() {
		if (activityCameraStream) {
			activityCameraStream.getTracks().forEach(track => track.stop());
			activityCameraStream = null;
		}
		cameraVideoTrack = null;
		hideFlashControl();
		restorePatrolProgressAfterCamera();
	}
	
	// Handle camera button clicks
	$('#btn-open-camera').click(function() {
		$('#camera-container').show();
		$('#btn-open-camera').hide();
		startActivityCamera().catch(() => {
			$('#camera-container').hide();
			$('#btn-open-camera').show();
		});
		collapsePatrolProgressForCamera();
		focusPhotoCard();
	});
	
	$('#btn-open-gallery').click(function() {
		$('#gallery-file-input').trigger('click');
		focusPhotoCard();
	});
	
	$('#gallery-file-input').on('change', function(e) {
		const files = e.target.files;
		if (!files || !files.length) {
			return;
		}
		const file = files[0];
		const galleryBtn = $('#btn-open-gallery');
		galleryBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengambil Foto...');
		addGalleryPhoto(file).then(() => {
			galleryBtn.prop('disabled', false).html('<i class="fas fa-image me-2"></i>Pilih dari Galeri');
		}).catch(() => {
			galleryBtn.prop('disabled', false).html('<i class="fas fa-image me-2"></i>Pilih dari Galeri');
			Swal.fire('Error', 'Gagal memuat foto dari galeri.', 'error');
		}).finally(() => {
			$('#gallery-file-input').val('');
		});
	});
	
	// Initialize currentLocation variable to avoid undefined errors
	let currentLocation = null;
	
	// Track GPS error display to prevent repeated modals
	let gpsErrorShown = false;
	let gpsErrorShownTime = 0;
	const GPS_ERROR_DEBOUNCE_MS = 5000; // Only show error once per 5 seconds
	
	$('#btn-capture').click(function() {
		const button = $('#btn-capture');
		
		// Show loading while getting GPS
		button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mengambil Lokasi GPS...');
		
		// Request fresh GPS location
		getGPSLocation()
			.then(location => {
				capturePhotoWithLocation(location);
				button.prop('disabled', false).html('<i class="fas fa-camera me-2"></i>Ambil Foto');
			})
			.catch(err => {
				console.error('GPS Error:', err);
				// Use cached location if GPS fails, or null if no cached location
				if (currentLocation) {
				capturePhotoWithLocation(currentLocation);
				} else {
					// Only show error modal if not shown recently (debounce)
					const now = Date.now();
					if (!gpsErrorShown || (now - gpsErrorShownTime) > GPS_ERROR_DEBOUNCE_MS) {
						gpsErrorShown = true;
						gpsErrorShownTime = now;
						Swal.fire({
							icon: 'warning',
							title: 'GPS Tidak Tersedia',
							text: 'Tidak dapat mendapatkan lokasi GPS. Foto akan disimpan tanpa koordinat.',
							confirmButtonText: 'OK'
						});
					}
					// Capture photo with null location (function should handle this)
					capturePhotoWithLocation(null);
				}
				button.prop('disabled', false).html('<i class="fas fa-camera me-2"></i>Ambil Foto');
			});
	});
	
	// Function to get GPS location
	function getGPSLocation() {
		return new Promise((resolve, reject) => {
			if (!navigator.geolocation) {
				reject(new Error('GPS tidak tersedia'));
				return;
			}
			
			const options = {
				enableHighAccuracy: true,
				timeout: 10000,
				maximumAge: 0
			};
			
			navigator.geolocation.getCurrentPosition(
				position => {
					// Reset error flag on successful GPS
					gpsErrorShown = false;
					gpsErrorShownTime = 0;
					const location = {
						lat: position.coords.latitude,
						lng: position.coords.longitude,
						accuracy: position.coords.accuracy
					};
					currentLocation = location;
					resolve(location);
				},
				error => {
					reject(error);
				},
				options
			);
		});
	}
	
	// Function to capture photo with GPS location
	function capturePhotoWithLocation(location) {
		const video = document.getElementById('my_camera');
		const canvas = document.createElement('canvas');
		canvas.width = video.videoWidth;
		canvas.height = video.videoHeight;
		
		const ctx = canvas.getContext('2d');
		ctx.drawImage(video, 0, 0);
		
		const imageData = canvas.toDataURL('image/jpeg');
		
		// Get location
		let lat = location && location.lat ? location.lat : null;
		let lon = location && location.lng ? location.lng : null;
		
		// Create photo object
		const photoData = {
			file_name: 'photo_' + Date.now() + '.jpg',
			image: imageData,
			lat: lat,
			lon: lon
		};
		
		// Add to photos array
		activityPhotos.push(photoData);
		
		// Update hidden field with JSON
		$('#foto_activity').val(JSON.stringify(activityPhotos));
		
		// Show photo in preview
		addPhotoToPreview(photoData, activityPhotos.length - 1);
		
		// Stop camera and hide camera container
		stopActivityCamera();
		$('#camera-container').hide();
		
		// Show "Buka Kamera" button again for taking more photos
		$('#btn-open-camera').show();
		$('#btn-open-camera').html('<i class="fas fa-camera me-2"></i>Ambil Foto Lagi');
		
		// Show photos preview
		$('#photos-preview-container').show();
	}
	
	// Function to pause camera (keep stream alive)
	function pauseActivityCamera() {
		const video = document.getElementById('my_camera');
		if (video && activityCameraStream) {
			video.pause();
			// Keep the stream alive but pause video
		}
	}
	
	// Function to resume camera
	function resumeActivityCamera() {
		const video = document.getElementById('my_camera');
		if (video && activityCameraStream) {
			video.play();
		}
	}
	
	// Add photo to preview
	function addPhotoToPreview(photoData, index) {
		const photoHtml = `
			<div class="photo-item mb-2" data-index="${index}">
				<div class="card">
					<div class="card-body p-2">
						<div class="row align-items-center">
							<div class="col-3">
								<img src="${photoData.image}" class="img-fluid rounded" alt="Photo ${index + 1}">
							</div>
							<div class="col-6">
								<small class="text-muted d-block">${photoData.file_name}</small>
								<small class="text-muted d-block">Lat: ${photoData.lat || 'N/A'}</small>
								<small class="text-muted d-block">Lon: ${photoData.lon || 'N/A'}</small>
							</div>
							<div class="col-3 text-end">
								<button type="button" class="btn btn-danger btn-sm remove-photo" data-index="${index}">
									<i class="fas fa-trash"></i>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
		
		$('#photos-preview-container').append(photoHtml);
		
		// Handle remove button
		$('#photos-preview-container .remove-photo').off('click').on('click', function() {
			const indexToRemove = $(this).data('index');
			activityPhotos.splice(indexToRemove, 1);
			$('#foto_activity').val(JSON.stringify(activityPhotos));
			
			// Rebuild preview
			$('#photos-preview-container').empty();
			activityPhotos.forEach(function(photo, idx) {
				addPhotoToPreview(photo, idx);
			});
			
			// Update button text if no photos
			if (activityPhotos.length === 0) {
				$('#btn-open-camera').html('<i class="fas fa-camera me-2"></i>Buka Kamera');
				$('#photos-preview-container').hide();
			}
		});
	}
	
	// Reset form
	function resetForm() {
		$('#form-activity')[0].reset();
		$('#qr-scan-result').hide();
		$('#id_patrol').val('');
		$('#scanned_barcode').val('');
		scannedPatrolData = null;
		$('#photos-preview-container').empty();
		$('#photos-preview-container').hide();
		activityPhotos = [];
		$('#camera-container').hide();
		$('#btn-open-camera').show();
		$('#btn-open-camera').html('<i class="fas fa-camera me-2"></i>Buka Kamera');
		$('#foto_activity').val('');
		stopActivityCamera();
		// Clear forced scanner target when form is reset
		forcedScannerTargetId = null;
	}
	
	function addGalleryPhoto(file) {
		return new Promise((resolve, reject) => {
			const reader = new FileReader();
			reader.onload = function(evt) {
				const imageData = evt.target.result;
				const photoData = {
					file_name: file.name || ('gallery_' + Date.now() + '.jpg'),
					image: imageData,
					lat: currentLocation && currentLocation.lat ? currentLocation.lat : (currentLocation && currentLocation.coords ? currentLocation.coords.latitude : null),
					lon: currentLocation && currentLocation.lng ? currentLocation.lng : (currentLocation && currentLocation.coords ? currentLocation.coords.longitude : null)
				};
				activityPhotos.push(photoData);
				$('#foto_activity').val(JSON.stringify(activityPhotos));
				addPhotoToPreview(photoData, activityPhotos.length - 1);
				$('#photos-preview-container').show();
				resolve();
			};
			reader.onerror = reject;
			reader.readAsDataURL(file);
		});
	}
}