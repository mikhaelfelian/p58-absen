/**
* Written by: Agus Prawoto Hadi
* Year		: 2021
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {

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
		maxDate: new Date(),   
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
		// load_data(start_date, end_date);
	
	})
	
	$('.form-data').find('select').change(function() 
	{
		start_date = $('#start-date').val();
		end_date = $('#end-date').val();
		// load_data(start_date, end_date);
	});
});