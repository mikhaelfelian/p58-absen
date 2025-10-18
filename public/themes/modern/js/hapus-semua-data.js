/**
* Written by: Agus Prawoto Hadi
* Year		: 2021-2022
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	
	$('#btn-delete-all-data').click(function() {
		
		$bootbox =  bootbox.dialog({
			title: 'Hapus Semua Data',
			message: 'Hapus semua data?'+
			'</form>',
			buttons: {
				cancel: {
					label: 'Cancel'
				},
				success: {
					label: 'Submit',
					className: 'btn-danger submit',
					callback: function() 
					{
						var $button = $bootbox.find('button').prop('disabled', true);
						var $button_submit = $bootbox.find('button.submit');
						
						$bootbox.find('.alert').remove();
						$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
						$button_submit.prepend($spinner);
						$button.prop('disabled', true);
						delete_image = $('#hapus-semua-gambar-barang').is(':checked') ? 'Y' : 'N';
						
						$.ajax({
							type: 'POST',
							url: base_url + 'hapus-semua-data/ajaxDeleteAllData',
							data: 'submit=submit&delete_image=' + delete_image,
							dataType: 'text',
							success: function (data) {
								data = $.parseJSON(data);
								console.log(data);
								$spinner.remove();
								$button.prop('disabled', false);
								
								if (data.status == 'ok') 
								{
									$bootbox.modal('hide');
									alert_toast('Data berhasil dihapus');
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
		});

	});
});