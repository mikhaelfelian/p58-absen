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
	
	$bootbox_lokasi = bootbox.dialog({
		centerVertical: true,
		closeButton: false,
		title: '',
		message: '<div class="text-center"><span class="spinner-border spinner-border-sm me-2"></span>Memuat lokasi...</div>'
	});
	$bootbox_lokasi.find('.modal-dialog').css('max-width', 250);
	
	success = function(pos) {
		$bootbox_lokasi.modal('hide');
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
		
		if (geolocation.coords == undefined) {
			alert_icon('Lokasi harus diaktifkan');
			return;
		}
		
		date = new Date();
		jam_sekarang = ("0" + date.getHours()).substr(-2);
		menit_sekarang = ("0" + date.getMinutes()).substr(-2);
		detik_sekarang = ("0" + date.getSeconds()).substr(-2);
		waktu_sekarang = jam_sekarang + ':' + menit_sekarang + ':' + detik_sekarang;

		if (jenis_presensi == 'masuk') {
			if (waktu_sekarang < setting.waktu_masuk_awal || waktu_sekarang > setting.waktu_masuk_akhir) {
				alert_icon('Waktu presensi masuk mulai pukul ' + setting.waktu_masuk_awal + ' hingga pukul ' + setting.waktu_masuk_akhir );
				return;
			}
		} else {
			if (waktu_sekarang < setting.waktu_pulang_awal || waktu_sekarang > setting.waktu_pulang_akhir) {
				alert_icon('Waktu presensi pulang mulai pukul ' + setting.waktu_pulang_awal + ' hingga pukul ' + setting.waktu_masuk_akhir );
				return;
			}
		}
		
		if (setting.gunakan_radius_lokasi == 'Y') {
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
		}
				
		// Get selected company ID
		var selectedCompanyId = $('#selected-company-id').val() || $('#id_company').val();
		
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
			
			$('#video-container, #btn-ambil-foto').show();
			
			$btn_close = $('<button type="button" class="bootbox-close-button btn-close" aria-hidden="true" style="position: absolute;right: 10px;top: 10px;z-index:99999"></button>')
			$bootbox_presensi.find('.modal-content').prepend($btn_close);

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
			
			video = document.getElementById("video");
			video.srcObject  = stream;
			video.onloadedmetadata = () => {
				$('#video').width(webcam_container_width);
				video.play();
				$('#webcam-loader').remove();
				$('#btn-ambil-foto').prop('disabled', false);
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
					// Update waktu presensi if btn_clicked is set
					if (btn_clicked && btn_clicked !== '') {
					$(`#${btn_clicked}`).find('.waktu-presensi').text(data.data.waktu);
					}
					toast_mobile('<i class="bi bi-check-circle me-2"></i>Data berhasil disimpan');
					$('.nav-footer-home').click();
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
	let qrScanner = null;
	let currentCompanyId = null;
	let patrolOptions = [];
	let currentStep = 1;
	let scannedPatrolData = null;
	
	// Load patrol points when company is detected
	window.addEventListener('companyDetected', function(event) {
		currentCompanyId = event.detail.companyId;
		loadPatrolPoints(currentCompanyId);
	});
	
	// Step navigation
	$('#btn-proceed-to-step2').click(function() {
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
		$('.step-container').hide();
		$('#step-' + step).show();
		currentStep = step;
		
		if (step === 3) {
			populateReviewContent();
		}
	}
	
	// Validate step 2 before proceeding
	function validateStep2() {
		const judul = $('#judul_activity').val();
		const deskripsi = $('#deskripsi_activity').val();
		
		if (!judul.trim()) {
			Swal.fire('Error', 'Judul activity harus diisi', 'error');
			return;
		}
		
		if (!deskripsi.trim()) {
			Swal.fire('Error', 'Deskripsi activity harus diisi', 'error');
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
		$.ajax({
			url: base_url + 'activity/getPatrolPoints/' + companyId,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				if (response.status === 'ok') {
					patrolOptions = response.data;
					updatePatrolDropdown();
				}
			},
			error: function() {
				console.log('Error loading patrol points');
			}
		});
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
	$('#btn-scan-qr').click(function() {
		$('#qrScannerModal').modal('show');
		// Don't start scanner here, let modal event handle it
	});
	
	// Handle modal opened event
	$(document).on('qrScannerModalOpened', function() {
		startQRScanner();
	});
	
	// Handle modal shown event
	$('#qrScannerModal').on('shown.bs.modal', function() {
		startQRScanner();
	});
	
	
	// Start QR scanner
	function startQRScanner() {
		// Check if Html5Qrcode is available
		if (typeof Html5Qrcode === 'undefined') {
			console.error('Html5Qrcode library not loaded');
			Swal.fire('Error', 'QR Scanner library tidak tersedia', 'error');
			return;
		}
		
		// Check if scanner is already running
		if (qrScanner && qrScanner.isScanning && qrScanner.isScanning()) {
			console.log('QR Scanner already running');
			return;
		}
		
		// Clear existing scanner
		if (qrScanner) {
			qrScanner.clear();
		}
		
		const config = {
			fps: 30,
			qrbox: { width: 250, height: 250 },
			aspectRatio: 1.0,
			rememberLastUsedCamera: true,
			supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA],
			showTorchButtonIfSupported: true,
			showZoomSliderIfSupported: true,
			defaultZoomValueIfSupported: 2,
			useBarCodeDetectorIfSupported: true
		};
		
		qrScanner = new Html5Qrcode("qr-reader");
		
		// Try environment camera first, then user camera as fallback
		const cameraConfigs = [
			{ facingMode: "environment" },
			{ facingMode: "user" },
			"environment",
			"user"
		];
		
		let currentConfigIndex = 0;
		
		function tryStartScanner() {
			if (currentConfigIndex >= cameraConfigs.length) {
				Swal.fire('Error', 'Tidak dapat mengakses kamera. Pastikan izin kamera diberikan.', 'error');
				return;
			}
			
			const cameraConfig = cameraConfigs[currentConfigIndex];
			console.log('Trying camera config:', cameraConfig);
			
			qrScanner.start(
				cameraConfig,
				config,
				onScanSuccess,
				onScanFailure
			).then(() => {
				console.log('QR Scanner started successfully with config:', cameraConfig);
			}).catch(err => {
				console.error("Failed with config:", cameraConfig, err);
				currentConfigIndex++;
				tryStartScanner();
			});
		}
		
		tryStartScanner();
	}
	
	// QR scan success
	function onScanSuccess(decodedText, decodedResult) {
		console.log(`QR Code detected: ${decodedText}`);
		
		// Stop scanner
		qrScanner.stop().then(() => {
			qrScanner.clear();
		}).catch(err => {
			console.log("Error stopping scanner after success", err);
		});
		
		// For testing: Auto-proceed to step 2 without validation
		// Store the scanned barcode
		$('#scanned_barcode').val(decodedText);
		$('#id_patrol').val('TEST_PATROL_ID'); // Set a test patrol ID
		
		// Store scanned patrol data for display
		scannedPatrolData = {
			id_patrol: 'TEST_PATROL_ID',
			nama_patrol: 'Test Patrol Point',
			nama_company: 'Test Company',
			barcode: decodedText
		};
		
		// Show success feedback and auto-proceed
		Swal.fire({
			icon: 'success',
			title: 'QR Code Terdeteksi!',
			text: 'Kode: ' + decodedText + ' - Lanjut ke Step 2',
			timer: 2000,
			showConfirmButton: false
		}).then(() => {
			// Close modal and proceed to step 2
			$('#qrScannerModal').modal('hide');
			showStep(2);
		});
	}
	
	// QR scan failure
	function onScanFailure(error) {
		// Only log if it's not a common "not found" error
		if (error && 
			!error.includes("No QR code found") && 
			!error.includes("No MultiFormat Readers were able to detect the code") &&
			!error.includes("QR code parse error")) {
			console.log("QR Scan error:", error);
		}
	}
	
	// Validate QR code
	$('#btn-validate-qr').click(function() {
		const barcode = $(this).data('barcode');
		validateQRCode(barcode);
	});
	
	// Test QR code button
	$('#btn-test-qr').click(function() {
		// Simulate a QR code detection for testing
		const testBarcode = 'TEST_PATROL_' + Date.now();
		onScanSuccess(testBarcode, {});
	});
	
	// Validate QR code with server
	function validateQRCode(barcode) {
		$.ajax({
			url: base_url + 'mobile-activity/validateQRCode',
			type: 'POST',
			data: { 
				barcode: barcode,
				id_company: currentCompanyId
			},
			dataType: 'json',
			success: function(response) {
				if (response.status === 'ok') {
					// Store scanned patrol data
					scannedPatrolData = response.data;
					
					// Update form fields
					$('#id_patrol').val(scannedPatrolData.id_patrol);
					$('#scanned_barcode').val(barcode);
					
					// Show scan result
					$('#scanned-patrol-info').html(`
						<strong>${scannedPatrolData.nama_patrol}</strong><br>
						<small>${scannedPatrolData.nama_company}</small>
					`);
					$('#qr-scan-result').show();
					
					$('#qrScannerModal').modal('hide');
					Swal.fire('Berhasil!', `QR Code valid: ${scannedPatrolData.nama_patrol}`, 'success');
				} else {
					Swal.fire('Error', response.message || 'QR Code tidak valid', 'error');
				}
			},
			error: function() {
				Swal.fire('Error', 'Gagal memvalidasi QR Code', 'error');
			}
		});
	}
	
	// Close modal and stop scanner
	$('#qrScannerModal').on('hidden.bs.modal', function() {
		if (qrScanner) {
			qrScanner.stop().then(() => {
				qrScanner.clear();
				qrScanner = null; // Reset scanner instance
			}).catch(err => {
				console.log("Error stopping scanner", err);
				qrScanner = null; // Reset even if stop fails
			});
		}
		$('#qr-scanning-status').show();
		$('#qr-result').hide();
		$('#btn-validate-qr').hide();
	});
	
	// Save activity from step 3
	$('#btn-save-activity').click(function() {
		saveActivity();
	});
	
	// Save activity function
	function saveActivity() {
		const id_company = $('#id_company').val();
		const id_patrol = $('#id_patrol').val();
		const scanned_barcode = $('#scanned_barcode').val();
		const judul_activity = $('#judul_activity').val();
		const deskripsi_activity = $('#deskripsi_activity').val();
		const foto = $('#foto_activity').val();
		
		if (!id_company) {
			Swal.fire('Error', 'Lokasi company tidak valid', 'error');
			return;
		}
		
		if (!id_patrol) {
			Swal.fire('Error', 'Titik patroli harus dipilih', 'error');
			return;
		}
		
		if (!judul_activity.trim()) {
			Swal.fire('Error', 'Judul activity harus diisi', 'error');
			return;
		}
		
		if (!deskripsi_activity.trim()) {
			Swal.fire('Error', 'Deskripsi activity harus diisi', 'error');
			return;
		}
		
		const data = {
			id_company: id_company,
			id_patrol: id_patrol,
			barcode_scanned: scanned_barcode,
			judul_activity: judul_activity,
			deskripsi_activity: deskripsi_activity,
			foto: foto,
			location: currentLocation
		};
		
		const data_encoded = btoa(JSON.stringify(data));
		
		$('#btn-save-activity').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...');
		
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
						// Reset form and go back to step 1
						resetForm();
						showStep(1);
					});
				} else {
					Swal.fire('Error', response.message || 'Gagal menyimpan activity', 'error');
				}
			},
			error: function() {
				$('#btn-save-activity').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Simpan Activity');
				Swal.fire('Error', 'Gagal menyimpan activity', 'error');
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
		$('#preview-container').hide();
		$('#camera-container').hide();
		$('#foto_activity').val('');
	}
}