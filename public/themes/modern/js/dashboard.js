$(document).ready(function() {
		
	/* Presensi perbulan */
	presensi_perbulan = JSON.parse(presensi_perbulan);
	let dataChartPresensiPerbulan = {
		labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        datasets: [{
			label: 'Tepat Waktu',
			backgroundColor: 'rgba(147, 221, 179, 0.9)',
			borderColor: 'rgb(99 174 206)',
			borderWidth: 0,
			data: presensi_perbulan.tepat_waktu
		}, {
			label: 'Terlambat Masuk',
			backgroundColor: 'rgba(240, 201, 125, 0.8)',
			borderColor: 'rgb(251 179 66)',
			borderWidth: 0,
			data: presensi_perbulan.terlambat_masuk
		}, {
			label: 'Pulang Sebelum Waktunya',
			backgroundColor: 'rgba(255, 153, 85, 0.8)',
			borderColor: 'rgb(251 179 66)',
			borderWidth: 0,
			data: presensi_perbulan.pulang_sebelum_waktunya
		}, {
			label: 'Tidak Absen',
			backgroundColor: 'rgba(255, 0, 57, 0.8)',
			// borderColor: 'rgb(251 179 66)',
			borderWidth: 0,
			data: presensi_perbulan.tidak_absen
		}]
	};

	configChartPresensiPerbulan = {
		type: 'bar',
		data: dataChartPresensiPerbulan,
		options: {
			responsive: false,
			maintainAspectRatio: false,
			plugins: {
			  legend: {
				display: true,
				position: 'top',
				fullWidth: false,
				labels: {
					padding: 10,
					boxWidth: 30
				}
			  }
			},
			
			tooltips: {
				callbacks: {
					label: function(tooltipItems, data) {
						// return data.labels[tooltipItems.index] + ": " + data.datasets[0].data[tooltipItems.index].toLocaleString();
						// return "Total : " + data.datasets[0].data[tooltipItems.index].toLocaleString();
						return "Total : " + data.datasets[0].data[tooltipItems.index].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
					}
				}
			},
			scales: {
				x: {
					stacked: true
				},
				y: {
					stacked: true,
					beginAtZero: false,
					ticks: {
						callback: function(value, index, values) {
							// return value.toLocaleString();
							return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
						}
					}
				}
			}
		}
	}

	var ctx = document.getElementById('bar-container').getContext('2d');
	window.chartPenjualan = new Chart(ctx, configChartPresensiPerbulan);
	
	/* Presensi pertahun */
	presensi_pertahun = JSON.parse(presensi_pertahun);
	var configChartTotalPresensi = {
		type: 'pie',
		data: {
			datasets: [{
				data: [presensi_pertahun.tepat_waktu, presensi_pertahun.terlambat_masuk, presensi_pertahun.pulang_sebelum_waktunya, presensi_pertahun.tidak_absen],
				backgroundColor: ['rgba(190,124,222,0.8)', 'rgba(255,247,166,0.8)', 'rgba(238,212,119,0.8)', 'rgba(255, 0, 57, 0.8)'],
				borderWidth:[0,0,0,0]
			}],
			labels: ['Tepat Waktu', 'Terlambat', 'Pulang Sebelum Waktunya', 'Tidak Absen']
		},
		options: {
			responsive: false,
			// maintainAspectRatio: false,
			title: {
				display: true,
				text: '',
				fontSize: 14,
				lineHeight:3
			},
			plugins: {
			  legend: {
				display: true,
				position: 'bottom',
				fullWidth: false,
				labels: {
					padding: 10,
					boxWidth: 30
				}
			  },
			  title: {
				display: false,
				text: 'Statistik Presensi'
			  }
			}
		}
	};
	
	var ctx = document.getElementById('chart-total-presensi').getContext('2d');
	window.chartTotalPresensi = new Chart(ctx, configChartTotalPresensi);
	
	if ($('#tabel-presensi-terbaru').length > 0) 
	{
		column = $.parseJSON($('#presensi-terbaru-column').html());
		url = $('#presensi-terbaru-url').text();
		
		settings = {
			"processing": true,
			"serverSide": true,
			"scrollX": true,
			pageLength : 5,
			lengthChange: false,
			"ajax": {
				"url": url,
				"type": "POST"
			},
			"columns": column,
			initComplete: function (settings, json) {
				console.log(json.data.length);
				if (json.data.length) {
					$('.btn-export').prop('disabled', false);
				}
			}
		}
		
		$add_setting = $('#presensi-terbaru-setting');
		if ($add_setting.length > 0) {
			add_setting = $.parseJSON($('#presensi-terbaru-setting').html());
			for (k in add_setting) {
				settings[k] = add_setting[k];
			}
		}
		
		dataTablesPenjualanTerbesar =  $('#tabel-presensi-terbaru').DataTable( settings );
		$('#tahun-presensi-terbaru').change(function() {
			new_url = base_url + 'dashboard/getDataDTPresensiTerbaru?tahun=' + $(this).val();
			dataTablesPenjualanTerbesar.ajax.url( new_url ).load();
		})
		
		html = '<div class="input-group">'+
				'<button class="btn btn-outline-secondary me-0 btn-export btn-xs" type="button" id="btn-excel" disabled="disabled"><i class="fas fa-file-excel me-2"></i>XLSX</button></div>';
				
		$('#tabel-presensi-terbaru_wrapper').children().eq(0).children().eq(0).append(html);
	}
	
	$('body').delegate('#btn-excel', 'click', function() {
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$spinner.prependTo($this);
		tahun = $('#tahun-presensi-terbaru').val();
		filename = 'Presensi Terbaru Tahun ' + tahun + '.xlsx';
		url = base_url + 'dashboard/ajaxExportExcelPresensiTerbaru?tahun=' + tahun;
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
	});
});