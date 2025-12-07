/**
* Written by: Agus Prawoto Hadi
* Year		: 2021-2022
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	$('#btn-delete-data-aktivasi').click(function(){
		$this = $(this);
		$bootbox = bootbox.dialog({
			title: 'Hapus Data Aktivasi',
			message: '<p>Yakin akan menghapus data aktivasi?</p><p>Data aktivasi pada server Jagowebdev.com tidak ikut terhapus. Anda dapat melakukan aktivasi ulang sewaktu waktu</p>',
			buttons: {
				cancel: {
					label: 'Cancel'
				},
				success: {
					label: 'Delete',
					className: 'btn-danger submit',
					callback: function() 
					{
						$button = $bootbox.find('button').prop('disabled', true);
						$button_submit = $bootbox.find('button.submit');
						
						$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
						$button_submit.prepend($spinner);
						$button.prop('disabled', true);
						
						$.ajax({
							type: 'POST',
							url: base_url + '/aktivasi/ajaxDeleteAktivasi',
							dataType: 'text',
							success: function (data) {
								data = $.parseJSON(data);
								$spinner.remove();
								$button.prop('disabled', false);
								
								if (data.status == 'ok') 
								{
									$('.card-body').find('.alert').remove();
									$alert = $('<div class="alert alert-success">Data aktivasi berhasil dihapus, silakan <a href="' + base_url + '/aktivasi" title="Refresh Halaman">refresh halaman</a> untuk memperbarui tampilan halaman</div>');
									$this.replaceWith($alert);
									
									$bootbox.modal('hide');
									$alert = alert_icon({
										title: 'Hapus data aktivasi',
										message: data.message,
										closeButton : false,
										btnSuccessLabel: 'Tutup dan Refresh',
										callback: function(){ window.location.replace(base_url + 'aktivasi') }
									}, 'success');
								} else {
									alert_icon(data.message);
								}
							},
							error: function (xhr) {
								console.log(xhr.responseText);
								$spinner.remove();
								$button.prop('disabled', false);
								alert_icon(xhr);
							}
						})
						return false;
					}
				}
			}
			
		})
	})
});