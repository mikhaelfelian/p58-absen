$(document).ready(function() 
{ 
	destroyFlatpickr();
	flatpickr_instance = $('.flatpickr').flatpickr({
		enableTime: false,
		dateFormat: "d-m-Y",
		mode: 'range',
		locale: "id",
		maxDate: new Date(),
		animate: false,
		onClose: function(selectedDates, dateStr, instance){
			$('.overlay').remove();
			if (selectedDates.length < 2) {
				curr_date = $('#periode-presensi-current').text();
				instance.setDate('', false, curr_date);
				$('#periode-presensi').val(curr_date);
				return false;
			}
			
			if (moment(selectedDates[0]).format('dd-mm-yyyy') == moment(selectedDates[1]).format('dd-mm-yyyy')) {
				$('#periode-presensi').val(dateStr + ' s.d. ' + dateStr);
			}
			
			if ($('#periode-presensi-current').text() == $('#periode-presensi').val()) {
				return false;
			}
			
			$('#periode-presensi-current').text($('#periode-presensi').val());
			
			$periode_presensi = $('input[name="periode_presensi"]');
			$periode_presensi.prop('disabled', true);
			$spinner = $('<div class="spinner-container" style="z-index:999"><span class="spinner-border"></span></div>');
			$spinner.appendTo('.riwayat-absen-container');
			
			$.get(base_url + 'mobile-presensi-riwayat?periode=' + $('input[name="periode_presensi"]').val(), function(data) {
				
				$html = $(data);
				$new_content = $html.find('.riwayat-absen-container').children('.riwayat-absen-content').html();
				$('.riwayat-absen-container').children('.riwayat-absen-content').html($new_content);
				$spinner.remove();
				$periode_presensi.prop('disabled', false);
				if (osRiwayatPresensi != '') {
					osRiwayatPresensi.destroy();
				}
				osRiwayatPresensi =  OverlayScrollbars( $('.riwayat-absen-content'), {scrollbars : {autoHide: 'leave', autoHideDelay: 100}} );
			})
		},
		onChange: [function(selectedDates, dateStr, instance){
			if (selectedDates.length == 2) {
				return;
			}
						
			let minDate = new Date(selectedDates);
			minDate.setDate(minDate.getDate() - 10);
			instance.set('minDate', minDate);
			
			let maxDate = new Date(selectedDates);
			selisih = ( Math.floor((new Date() - maxDate) / (1000 * 60 * 60 * 24)) );
			if (selisih > 10) {
				maxDate.setDate(maxDate.getDate() + 10);
				instance.set('maxDate', maxDate)
			} else {
				instance.set('maxDate', new Date());
			}

		}],
		onOpen: [
			function(selectedDates, dateStr, instance){
				instance.set('minDate', false);
				instance.set('maxDate', new Date());
				$flatpickr_calendar = $('.flatpickr-calendar');
				$('body').append('<div class="overlay" style="background:rgba(0,0,0,0.5);position:fixed;width:100%;height:100vh;z-index:10"></div>');
				$flatpickr_calendar.appendTo('body');
        }]
	});
	osRiwayatPresensi = '';
	
	if (osRiwayatPresensi != '') {
		osRiwayatPresensi.destroy();
	}
	osRiwayatPresensi =  OverlayScrollbars( $('.riwayat-absen-content'), {scrollbars : {autoHide: 'leave', autoHideDelay: 100}} );
	
});