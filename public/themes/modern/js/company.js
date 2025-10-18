/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

var map, marker, circle;
var mapSetting, markerSetting, circleSetting;

$(document).ready(function() {
	
	// Current Location Button Handler
	$('#btn-current-location').on('click', function() {
		var btn = $(this);
		var originalHtml = btn.html();
		
		// Show loading state
		btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
		
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function(position) {
				var lat = position.coords.latitude;
				var lng = position.coords.longitude;
				
				// Update input fields
				$('#latitude').val(lat);
				$('#longitude').val(lng);
				
				// Update map if it exists
				if (typeof map !== 'undefined' && typeof marker !== 'undefined') {
					var newLatLng = L.latLng(lat, lng);
					marker.setLatLng(newLatLng);
					map.setView(newLatLng, 15);
					
					// Show success message
					Swal.fire({
						icon: 'success',
						title: 'Lokasi Terdeteksi!',
						text: 'Lokasi Anda: ' + lat.toFixed(6) + ', ' + lng.toFixed(6),
						timer: 2000,
						showConfirmButton: false
					});
				}
				
				// Restore button
				btn.html(originalHtml).prop('disabled', false);
			}, function(error) {
				// Error handling
				var errorMsg = 'Gagal mendapatkan lokasi';
				switch(error.code) {
					case error.PERMISSION_DENIED:
						errorMsg = 'Akses lokasi ditolak. Silahkan izinkan akses lokasi di browser.';
						break;
					case error.POSITION_UNAVAILABLE:
						errorMsg = 'Informasi lokasi tidak tersedia.';
						break;
					case error.TIMEOUT:
						errorMsg = 'Request timeout. Silahkan coba lagi.';
						break;
				}
				
				Swal.fire({
					icon: 'error',
					title: 'Gagal!',
					text: errorMsg
				});
				
				// Restore button
				btn.html(originalHtml).prop('disabled', false);
			}, {
				enableHighAccuracy: true,
				timeout: 10000,
				maximumAge: 0
			});
		} else {
			Swal.fire({
				icon: 'error',
				title: 'GPS Tidak Didukung!',
				text: 'Browser Anda tidak mendukung Geolocation.'
			});
			btn.html(originalHtml).prop('disabled', false);
		}
	});
	
	
	// Initialize Leaflet Map for form
	if ($('#map').length) {
		initializeMap();
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
		
		// Delete button handler
		$('#table-result').on('click', '.btn-delete', function() {
			var id = $(this).data('id');
			
			if (!id) {
				Swal.fire('Error!', 'ID tidak ditemukan. Silahkan refresh halaman dan coba lagi.', 'error');
				return;
			}
			
			Swal.fire({
				title: 'Konfirmasi',
				text: 'Apakah Anda yakin ingin menghapus data ini?',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Ya, Hapus!',
				cancelButtonText: 'Batal'
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						url: module_url + '/ajaxDelete',
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
						error: function(xhr, status, error) {
							Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data: ' + error, 'error');
						}
					});
				}
			});
		});
	}
	
	// Form validation
	$('.form-company').on('submit', function(e) {
		var latitude = $('#latitude').val();
		var longitude = $('#longitude').val();
		var radius = $('input[name="radius_nilai"]').val();
		
		if (!latitude || !longitude) {
			e.preventDefault();
			Swal.fire('Error!', 'Lokasi GPS harus diisi', 'error');
			return false;
		}
		
		if (!radius || parseFloat(radius) <= 0) {
			e.preventDefault();
			Swal.fire('Error!', 'Radius harus lebih dari 0', 'error');
			return false;
		}
	});
});

function initializeMap() {
	var lat = parseFloat($('#latitude').val()) || -7.797068;
	var lng = parseFloat($('#longitude').val()) || 110.370529;
	
	// Initialize map
	map = L.map('map').setView([lat, lng], 13);
	
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors'
	}).addTo(map);
	
	// Add marker
	marker = L.marker([lat, lng], {draggable: true}).addTo(map);
	
	// Add circle for radius
	updateCircle();
	
	// Update coordinates when marker is dragged
	marker.on('dragend', function(e) {
		var position = marker.getLatLng();
		$('#latitude').val(position.lat.toFixed(6));
		$('#longitude').val(position.lng.toFixed(6));
		updateCircle();
	});
	
	// Update marker when clicking on map
	map.on('click', function(e) {
		marker.setLatLng(e.latlng);
		$('#latitude').val(e.latlng.lat.toFixed(6));
		$('#longitude').val(e.latlng.lng.toFixed(6));
		updateCircle();
	});
	
	// Update circle when radius changes
	$('input[name="radius_nilai"], select[name="radius_satuan"]').on('change', function() {
		updateCircle();
	});
}

function updateCircle() {
	if (circle) {
		map.removeLayer(circle);
	}
	
	var radius = parseFloat($('input[name="radius_nilai"]').val()) || 1;
	var satuan = $('select[name="radius_satuan"]').val();
	
	if (satuan == 'km') {
		radius = radius * 1000;
	}
	
	var position = marker.getLatLng();
	circle = L.circle(position, {
		color: 'red',
		fillColor: '#f03',
		fillOpacity: 0.2,
		radius: radius
	}).addTo(map);
	
	map.fitBounds(circle.getBounds());
}

// Setting Form Functionality
function initSettingMap() {
	if (typeof L === 'undefined') return;
	
	var lat = parseFloat($('#setting-latitude').val()) || -7.797068;
	var lng = parseFloat($('#setting-longitude').val()) || 110.370529;
	var radius = parseFloat($('#setting-radius-nilai').val()) || 1.0;
	var satuan = $('#setting-radius-satuan').val() || 'km';
	
	// Convert to meters for Leaflet
	if (satuan === 'km') {
		radius = radius * 1000;
	}
	
	// Initialize map
	mapSetting = L.map('map-setting').setView([lat, lng], 15);
	
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '© OpenStreetMap contributors'
	}).addTo(mapSetting);
	
	// Add marker
	markerSetting = L.marker([lat, lng], {draggable: true}).addTo(mapSetting);
	
	// Add circle
	circleSetting = L.circle([lat, lng], {
		color: 'blue',
		fillColor: '#3388ff',
		fillOpacity: 0.2,
		radius: radius
	}).addTo(mapSetting);
	
	// Marker drag event
	markerSetting.on('drag', function(e) {
		var position = e.target.getLatLng();
		$('#setting-latitude').val(position.lat.toFixed(6));
		$('#setting-longitude').val(position.lng.toFixed(6));
		
		// Update circle
		circleSetting.setLatLng(position);
	});
	
	// Map click event
	mapSetting.on('click', function(e) {
		var latlng = e.latlng;
		markerSetting.setLatLng(latlng);
		$('#setting-latitude').val(latlng.lat.toFixed(6));
		$('#setting-longitude').val(latlng.lng.toFixed(6));
		
		// Update circle
		circleSetting.setLatLng(latlng);
	});
	
	// Radius change event
	$('#setting-radius-nilai, #setting-radius-satuan').on('change', function() {
		updateSettingCircle();
	});
}

function updateSettingCircle() {
	if (!circleSetting) return;
	
	var radius = parseFloat($('#setting-radius-nilai').val()) || 1.0;
	var satuan = $('#setting-radius-satuan').val() || 'km';
	
	// Convert to meters for Leaflet
	if (satuan === 'km') {
		radius = radius * 1000;
	}
	
	var position = markerSetting.getLatLng();
	circleSetting.setLatLng(position).setRadius(radius);
}

// Toggle radius location setting
$('#gunakan-radius-lokasi').on('change', function() {
	var value = $(this).val();
	if (value === 'Y') {
		$('#row-radius-lokasi-setting').show();
		if (!mapSetting) {
			initSettingMap();
		}
	} else {
		$('#row-radius-lokasi-setting').hide();
	}
});

// Initialize setting map if radius is enabled
if ($('#gunakan-radius-lokasi').val() === 'Y') {
	initSettingMap();
}

