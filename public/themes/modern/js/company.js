/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

var map, marker, circle;

$(document).ready(function() {
	
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
						url: base_url + module_url + '/ajaxDelete',
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
						error: function() {
							Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data', 'error');
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

