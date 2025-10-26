/**
*	App Name	: Aplikasi Absensi Online	
*	Author		: Agus Prawoto Hadi
*	Website		: https://jagowebdev.com
*	Year		: 2024
*/

// Faster, more responsive update for map/radius/location

let map, marker, circle;
let mapSetting, markerSetting, circleSetting;

$(function() {

	//=========== FAST: Current Location Button ===========//
	$('#btn-current-location').on('click', function() {
		const btn = $(this);
		const origHtml = btn.html();

		btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

		if (!navigator.geolocation) {
			Swal.fire({icon:'error',title:'GPS Tidak Didukung!','text':'Browser Anda tidak mendukung Geolocation.'});
			btn.html(origHtml).prop('disabled', false);
			return;
		}

		let geoWatch = null;
		let locationUpdate = (position) => {
			let lat = position.coords.latitude, lng = position.coords.longitude;
			$('#latitude').val(lat);
			$('#longitude').val(lng);
			if (map && marker) {
				const latlng = L.latLng(lat, lng);
				marker.setLatLng(latlng);
				map.setView(latlng, 15, {animate:true});
				Swal.fire({
					icon: 'success',
					title: 'Lokasi Terdeteksi!',
					text: 'Lokasi Anda: ' + lat.toFixed(6) + ', ' + lng.toFixed(6),
					timer: 1200,
					showConfirmButton: false
				});
			}
			btn.html(origHtml).prop('disabled', false);
			// Hentikan watch setelah update pertama
			if (geoWatch) navigator.geolocation.clearWatch(geoWatch);
		};

		let locationError = (error) => {
			let msg = 'Gagal mendapatkan lokasi';
			if (error.code === error.PERMISSION_DENIED)
				msg = 'Akses lokasi ditolak. Silahkan izinkan akses lokasi di browser.';
			else if (error.code === error.POSITION_UNAVAILABLE)
				msg = 'Informasi lokasi tidak tersedia.';
			else if (error.code === error.TIMEOUT)
				msg = 'Request timeout. Silahkan coba lagi.';
			Swal.fire({icon:'error',title:'Gagal!',text:msg});
			btn.html(origHtml).prop('disabled', false);
			if (geoWatch) navigator.geolocation.clearWatch(geoWatch);
		};

		geoWatch = navigator.geolocation.watchPosition(locationUpdate, locationError, {
			enableHighAccuracy: true, timeout: 7000, maximumAge: 0
		});
	});

	//=========== FAST: Leaflet Map for Form ===========//
	if ($('#map').length) initMapFast();

	//=========== FAST: DataTables ===========//
	if ($('#table-result').length) {
		const col = JSON.parse($('#dataTables-column').text());
		const set = JSON.parse($('#dataTables-setting').text());
		const url = $('#dataTables-url').text();
		set.processing = true; set.serverSide = true;
		set.ajax = {url: url, type:'POST'};
		set.columns = col;
		const table = $('#table-result').DataTable(set);

		$('#table-result').on('click', '.btn-delete', function() {
			const id = $(this).data('id');
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
					$.post(module_url + '/ajaxDelete', {id}, function(response) {
						if (response.status == 'ok') {
							Swal.fire('Berhasil!', response.message, 'success');
							table.ajax.reload(null, false);
						} else {
							Swal.fire('Error!', response.message, 'error');
						}
					},'json').fail(function(xhr,stat,err){
						Swal.fire('Error!', 'Terjadi kesalahan saat menghapus data: '+err, 'error');
					});
				}
			});
		});
	}

	//=========== FAST: Form Validation ===========//
	$('.form-company').on('submit', function(e) {
		let lat = $('#latitude').val(),
			lng = $('#longitude').val(),
			rad = $('input[name="radius_nilai"]').val();
		if (!lat || !lng) {
			e.preventDefault();
			Swal.fire('Error!','Lokasi GPS harus diisi','error');
			return false;
		}
		if (!rad || parseFloat(rad) <= 0) {
			e.preventDefault();
			Swal.fire('Error!','Radius harus lebih dari 0','error');
			return false;
		}
	});
});

//=============== FAST MAP FUNC ===============//

function initMapFast() {
	const $lat = $('#latitude'), $lng = $('#longitude'), $rad = $('input[name="radius_nilai"]'), $unit = $('select[name="radius_satuan"]');
	let lat = parseFloat($lat.val()) || -7.797068,
		lng = parseFloat($lng.val()) || 110.370529;
	map = L.map('map').setView([lat, lng], 13);
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap contributors'}).addTo(map);
	marker = L.marker([lat, lng], {draggable:true}).addTo(map);

	updateCircleFast();

	marker.on('drag', function(e) {
		const pos = e.target.getLatLng();
		$lat.val(pos.lat.toFixed(6));
		$lng.val(pos.lng.toFixed(6));
		updateCircleFast();
	});
	map.on('click', function(e){
		const pos = e.latlng;
		marker.setLatLng(pos);
		$lat.val(pos.lat.toFixed(6));
		$lng.val(pos.lng.toFixed(6));
		updateCircleFast();
	});

	$rad.add($unit).on('input change', updateCircleFast);
}

function updateCircleFast() {
	if (circle) map.removeLayer(circle);
	let radius = parseFloat($('input[name="radius_nilai"]').val())||1;
	let satuan = $('select[name="radius_satuan"]').val();
	if (satuan == 'km') radius = radius * 1000;
	const pos = marker.getLatLng();
	circle = L.circle(pos, {
		color:'red', fillColor:'#f03', fillOpacity:0.2, radius: radius
	}).addTo(map);
	map.fitBounds(circle.getBounds(), {animate:false});
}

//=============== FAST: Setting Map ===============//
function initSettingMap() {
	if (typeof L === 'undefined') return;
	const $lat = $('#setting-latitude'), $lng = $('#setting-longitude'),
		  $rad = $('#setting-radius-nilai'), $unit = $('#setting-radius-satuan');
	let lat = parseFloat($lat.val()) || -7.797068,
		lng = parseFloat($lng.val()) || 110.370529;
	let radius = parseFloat($rad.val()) || 1.0;
	let satuan = $unit.val() || 'km';
	if (satuan === 'km') radius = radius * 1000;

	mapSetting = L.map('map-setting').setView([lat, lng], 15);
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap contributors'}).addTo(mapSetting);

	markerSetting = L.marker([lat, lng], {draggable:true}).addTo(mapSetting);
	circleSetting = L.circle([lat, lng], {
		color:'blue', fillColor:'#3388ff', fillOpacity:.2, radius:radius
	}).addTo(mapSetting);

	markerSetting.on('drag',function(e){
		let pos = e.target.getLatLng();
		$lat.val(pos.lat.toFixed(6));
		$lng.val(pos.lng.toFixed(6));
		circleSetting.setLatLng(pos);
	});
	mapSetting.on('click',function(e){
		let pos = e.latlng;
		markerSetting.setLatLng(pos);
		$lat.val(pos.lat.toFixed(6));
		$lng.val(pos.lng.toFixed(6));
		circleSetting.setLatLng(pos);
	});
	$rad.add($unit).on('input change', updateSettingCircleFast);
}

function updateSettingCircleFast() {
	if (!circleSetting) return;
	let radius = parseFloat($('#setting-radius-nilai').val()) || 1.0,
		satuan = $('#setting-radius-satuan').val() || 'km';
	if (satuan === 'km') radius = radius * 1000;
	let pos = markerSetting.getLatLng();
	circleSetting.setLatLng(pos).setRadius(radius);
}

//=============== FAST: Toggle radius location ===============//
$('#gunakan-radius-lokasi').on('change', function() {
	let v = $(this).val();
	if (v === 'Y') {
		$('#row-radius-lokasi-setting').show();
		if (!mapSetting) setTimeout(initSettingMap,20);
	} else {
		$('#row-radius-lokasi-setting').hide();
	}
});
if ($('#gunakan-radius-lokasi').val() === 'Y') initSettingMap();

// Patrol Points Management
var patrolCounter = 0;

$(document).ready(function() {
	// Add patrol point button
	$('#btn-add-patrol').on('click', function() {
		addPatrolPoint();
	});
	
	// Load existing patrol points if editing
	loadExistingPatrolPoints();
	
	// Toggle patrol section based on is_patrol_mode
	togglePatrolSection();
	$('#is-patrol-mode').on('change', function() {
		togglePatrolSection();
	});
});

// Toggle patrol section visibility
function togglePatrolSection() {
	var isPatrolMode = $('#is-patrol-mode').val();
	if (isPatrolMode === 'Y') {
		$('#patrol-section').show();
		$('#patrol-card').show();
	} else {
		$('#patrol-section').hide();
		$('#patrol-card').hide();
	}
}

function addPatrolPoint() {
	patrolCounter++;
	var patrolId = 'patrol_' + patrolCounter;
	
	var patrolHtml = `
		<div class="patrol-item border rounded p-3 mb-3 bg-white" data-patrol-id="${patrolId}">
			<div class="row">
				<div class="col-12">
					<div class="d-flex justify-content-between align-items-center mb-2">
						<h6 class="mb-0 fw-semibold text-dark">
							<i class="fas fa-map-marker-alt me-2"></i>Titik Patroli #${patrolCounter}
						</h6>
						<button type="button" class="btn btn-danger btn-sm btn-remove-patrol" data-patrol-id="${patrolId}">
							<i class="fas fa-trash"></i>
						</button>
					</div>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-6 mb-2">
					<label class="form-label fw-medium">Nama Titik Patroli <span class="text-danger">*</span></label>
					<input type="text" class="form-control" name="patrol[${patrolCounter}][nama_patrol]" required>
				</div>
				<div class="col-md-6 mb-2">
					<label class="form-label fw-medium">Foto</label>
					<input type="file" class="form-control" name="patrol[${patrolCounter}][foto]" accept="image/*">
				</div>
			</div>
		</div>
	`;
	
	$('#patrol-container').append(patrolHtml);
	$('#no-patrol-message').hide();
	
	// Bind events
	bindPatrolEvents(patrolId);
}

// Removed map functions - not needed for simplified version

function bindPatrolEvents(patrolId) {
	// Remove patrol point
	$(`[data-patrol-id="${patrolId}"] .btn-remove-patrol`).on('click', function() {
		removePatrolPoint(patrolId);
	});
}

function removePatrolPoint(patrolId) {
	Swal.fire({
		title: 'Konfirmasi',
		text: 'Apakah Anda yakin ingin menghapus titik patroli ini?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#d33',
		cancelButtonColor: '#3085d6',
		confirmButtonText: 'Ya, Hapus!',
		cancelButtonText: 'Batal'
	}).then((result) => {
		if (result.isConfirmed) {
			// Remove HTML element
			$(`[data-patrol-id="${patrolId}"]`).remove();
			
			// Show no patrol message if no patrols left
			if ($('.patrol-item').length === 0) {
				$('#no-patrol-message').show();
			}
		}
	});
}

function loadExistingPatrolPoints() {
	// This will be populated by the controller with existing patrol data
	var existingPatrols = window.existingPatrols || [];
	
	if (existingPatrols.length > 0) {
		existingPatrols.forEach(function(patrol, index) {
			patrolCounter = index + 1;
			var patrolId = 'patrol_' + patrolCounter;
			
			var patrolHtml = `
				<div class="patrol-item border rounded p-3 mb-3 bg-white" data-patrol-id="${patrolId}">
					<div class="row">
						<div class="col-12">
							<div class="d-flex justify-content-between align-items-center mb-2">
								<h6 class="mb-0 fw-semibold text-dark">
									<i class="fas fa-map-marker-alt me-2"></i>Titik Patroli #${patrolCounter}
								</h6>
								<button type="button" class="btn btn-danger btn-sm btn-remove-patrol" data-patrol-id="${patrolId}">
									<i class="fas fa-trash"></i>
								</button>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-6 mb-2">
							<label class="form-label fw-medium">Nama Titik Patroli <span class="text-danger">*</span></label>
							<input type="text" class="form-control" name="patrol[${patrolCounter}][nama_patrol]" value="${patrol.nama_patrol || ''}" required>
						</div>
						<div class="col-md-6 mb-2">
							<label class="form-label fw-medium">Foto</label>
							<input type="file" class="form-control" name="patrol[${patrolCounter}][foto]" accept="image/*">
							${patrol.foto ? '<small class="text-muted">Current: ' + patrol.foto + '</small>' : ''}
						</div>
					</div>
					
					${patrol.barcode ? '<div class="row"><div class="col-12 mb-2"><label class="form-label fw-medium">QR Code</label><div class="form-control-plaintext text-center"><img id="barcode-img-' + patrolId + '" src="" alt="QR Code" class="img-fluid" style="max-width: 150px;"><br><small class="text-muted">' + patrol.barcode + '</small><br><button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="printQRCode(\'' + patrol.barcode + '\')"><i class="fas fa-print me-1"></i>Print</button></div></div></div>' : ''}
				</div>
			`;
			
			$('#patrol-container').append(patrolHtml);
			$('#no-patrol-message').hide();
			
			// Bind events
			bindPatrolEvents(patrolId);
			
			// Load barcode image if exists
			if (patrol.barcode) {
				loadBarcodeImage(patrolId, patrol.barcode);
			}
		});
	}
}

// Load QR code image
function loadBarcodeImage(patrolId, barcode) {
	$.ajax({
		url: base_url + 'company/getBarcodeBase64/' + patrolId,
		type: 'GET',
		dataType: 'json',
		success: function(response) {
			if (response.status === 'ok') {
				$('#barcode-img-' + patrolId).attr('src', response.barcode_image);
			} else {
				// Generate QR code directly if API fails
				generateQRCodeDirectly(patrolId, barcode);
			}
		},
		error: function(xhr, status, error) {
			// Generate QR code directly if API fails
			generateQRCodeDirectly(patrolId, barcode);
		}
	});
}

// Generate QR code directly using canvas
function generateQRCodeDirectly(patrolId, barcode) {
	// Create a simple QR code using canvas
	var canvas = document.createElement('canvas');
	var ctx = canvas.getContext('2d');
	var size = 200;
	
	canvas.width = size;
	canvas.height = size;
	
	// Fill with white background
	ctx.fillStyle = '#ffffff';
	ctx.fillRect(0, 0, size, size);
	
	// Create QR pattern
	var matrixSize = 25;
	var cellSize = size / matrixSize;
	
	// Generate pattern based on barcode
	var hash = btoa(barcode).replace(/[^A-Za-z0-9]/g, '');
	var hashIndex = 0;
	
	ctx.fillStyle = '#000000';
	
	for (var y = 0; y < matrixSize; y++) {
		for (var x = 0; x < matrixSize; x++) {
			// Skip corner markers
			if ((x < 7 && y < 7) || (x >= matrixSize - 7 && y < 7) || (x < 7 && y >= matrixSize - 7)) {
				continue;
			}
			
			// Skip timing patterns
			if (x === 6 || y === 6) {
				continue;
			}
			
			// Use hash to determine pattern
			var hashChar = hash[hashIndex % hash.length];
			var hashValue = hashChar.charCodeAt(0);
			
			if (hashValue % 2 === 0) {
				ctx.fillRect(x * cellSize, y * cellSize, cellSize, cellSize);
			}
			
			hashIndex++;
		}
	}
	
	// Add corner markers
	drawCornerMarker(ctx, 0, 0, cellSize);
	drawCornerMarker(ctx, (matrixSize - 7) * cellSize, 0, cellSize);
	drawCornerMarker(ctx, 0, (matrixSize - 7) * cellSize, cellSize);
	
	// Add timing patterns
	ctx.fillStyle = '#000000';
	for (var i = 8; i < matrixSize - 8; i++) {
		if (i % 2 === 0) {
			ctx.fillRect(6 * cellSize, i * cellSize, cellSize, cellSize);
			ctx.fillRect(i * cellSize, 6 * cellSize, cellSize, cellSize);
		}
	}
	
	// Convert to base64
	var dataURL = canvas.toDataURL('image/png');
	$('#barcode-img-' + patrolId).attr('src', dataURL);
}

// Draw corner marker
function drawCornerMarker(ctx, x, y, cellSize) {
	var markerSize = 7 * cellSize;
	
	// Outer square
	ctx.fillStyle = '#000000';
	ctx.fillRect(x, y, markerSize, markerSize);
	
	// Inner white square
	ctx.fillStyle = '#ffffff';
	ctx.fillRect(x + cellSize, y + cellSize, markerSize - 2 * cellSize, markerSize - 2 * cellSize);
	
	// Center black square
	ctx.fillStyle = '#000000';
	ctx.fillRect(x + 2 * cellSize, y + 2 * cellSize, markerSize - 4 * cellSize, markerSize - 4 * cellSize);
}

// Print QR code
function printQRCode(barcode) {
	// Create a new window for printing
	var printWindow = window.open('', '_blank');
	printWindow.document.write(`
		<!DOCTYPE html>
		<html>
		<head>
			<title>Print QR Code - ${barcode}</title>
			<style>
				body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }
				.qr-container { margin: 20px 0; }
				.qr-text { font-size: 14px; margin-top: 10px; }
				@media print {
					body { margin: 0; }
					.no-print { display: none; }
				}
			</style>
		</head>
		<body>
			<div class="qr-container">
				<div id="qr-image"></div>
				<div class="qr-text">${barcode}</div>
			</div>
			<div class="no-print">
				<button onclick="window.print()">Print</button>
				<button onclick="window.close()">Close</button>
			</div>
		</body>
		</html>
	`);
	
	// Load QR code image
	$.ajax({
		url: base_url + 'company/getBarcodeBase64/' + barcode.split('_')[2], // Extract patrol ID from barcode
		type: 'GET',
		dataType: 'json',
		success: function(response) {
			if (response.status === 'ok') {
				printWindow.document.getElementById('qr-image').innerHTML = '<img src="' + response.barcode_image + '" style="max-width: 100%;">';
			}
		}
	});
}

// Print all QR codes for a company
function printAllBarcodes(companyId) {
	window.open(base_url + 'company/printBarcodes/' + companyId, '_blank');
}

