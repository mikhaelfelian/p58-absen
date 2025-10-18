/**
* Written by: Agus Prawoto Hadi
* Year		: 2021
* Website	: jagowebdev.com
*/

setting = JSON.parse($('#setting-presensi').text());
map = '';
jQuery(document).ready(function () {
	
	$('.select2').select2({
		'theme' : 'bootstrap-5'
	})
	
	$('#gunakan-radius-lokasi').change(function(){
		if (this.value == 'Y') {
			$('#row-radius-lokasi').show();
			if (!map) {
				initiateMap();
			}
		} else {
			$('#row-radius-lokasi').hide();
		}
	})
	
	if (!$('#row-radius-lokasi').is(':hidden')) {
		initiateMap();
	}
	
	
	function initiateMap() 
	{
		map = L.map('map').setView([setting.latitude, setting.longitude], 13);
		// map.attributionControl.addAttribution('Tiles &copy; Esri &mdash; Source: Esri');
		map.attributionControl.setPrefix('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="me-1" viewBox="0 0 32 32"><path d="M31,8c0-2.209-1.791-4-4-4H5c-2.209,0-4,1.791-4,4v9H31V8Z" fill="#ea3323"></path><path d="M5,28H27c2.209,0,4-1.791,4-4v-8H1v8c0,2.209,1.791,4,4,4Z" fill="#fff"></path><path d="M5,28H27c2.209,0,4-1.791,4-4V8c0-2.209-1.791-4-4-4H5c-2.209,0-4,1.791-4,4V24c0,2.209,1.791,4,4,4ZM2,8c0-1.654,1.346-3,3-3H27c1.654,0,3,1.346,3,3V24c0,1.654-1.346,3-3,3H5c-1.654,0-3-1.346-3-3V8Z" opacity=".15"></path><path d="M27,5H5c-1.657,0-3,1.343-3,3v1c0-1.657,1.343-3,3-3H27c1.657,0,3,1.343,3,3v-1c0-1.657-1.343-3-3-3Z" fill="#fff" opacity=".2"></path></svg><a target="blank" href="https://jagowebdev.com">Jagowebdev</a>')

		L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
		}).addTo(map);
		var marker = L.marker([setting.latitude, setting.longitude]).addTo(map);
		
		map.on('move', function() {
			center = map.getCenter();
			$('#latitude').val(center.lat.toFixed(6));
			$('#longitude').val(center.lng.toFixed(6));
			marker.setLatLng(center);
		});
	}
	
	formatNumber('#radius-nilai');
	
	$('#radius-nilai').keyup(function() {
		$this = $(this);
		val = getFormatNumberValue (this.value);
		if (val >= 1000) {
			if ($('#radius-satuan').val() == 'm') {
				$('#radius-satuan').val('km');
				$this.val(1);
			}
		}
	});
	
	$('#radius-satuan').change(function() {
		$nilai = $('#radius-nilai');
		val = getFormatNumberValue ($('#radius-nilai').val());
		if (this.value == 'm') {
			if (val >= 1000) {
				$nilai.val(999);
			}
		}
	});
	
});