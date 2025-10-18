/**
* Written by: Agus Prawoto Hadi
* Year		: 2024
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	
	/* $(document).undelegate('#btn-submit-edit-profile', 'click')
	.delegate('#btn-submit-edit-profile', 'click', function(e) {
		e.preventDefault();
		$button = $(this);
		$form = $button.parents('form').eq(0);
		
		$file = $form.find('input[type="file"]')[0];

		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$button.prop('disabled', true);
		$button.prepend($spinner);
		
		formData = new FormData();
		formData.append($file.name, $file.files[0]);
		data = $form.serializeArray();
		$.each(data, function(i, elm) {
			formData.append(elm.name, elm.value);
		})
		formData.append('submit', 'submit');
		
		$.ajax({
			url: base_url + 'builtin/user/edit',
			method: 'post',
			data: formData,
			processData: false,
			contentType: false,
			success: function( data ) {
				$spinner.remove();
				$button.prop('disabled', false);
				console.log(data);
				data = JSON.parse(data);
				if (data.status == 'ok') {
					toast_mobile(data.message);
				} else {
					alert_icon(data.message);
				}

			}, error: function (xhr) {
				$spinner.remove();
				$button.prop('disabled', false);
				alert_icon(xhr);
				console.log(xhr);
			}
		})
	}); */
	
	$(document).undelegate('#btn-submit-edit-password', 'click')
	.delegate('#btn-submit-edit-password', 'click', function(e) {
		e.preventDefault();
		$button = $(this);
		$form = $button.parents('form').eq(0);
		
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$button.prop('disabled', true);
		$button.prepend($spinner);
		
		$.ajax({
			url: base_url + 'builtin/user/edit-password?mobile=true',
			method: 'post',
			data: $form.serialize() + '&submit=submit',
			success: function( data ) {
				$spinner.remove();
				$button.prop('disabled', false);
				
				data = JSON.parse(data);
				if (data.status == 'ok') {
					toast_mobile('<i class="bi bi-check-circle me-2"></i>' + data.message);
				} else {
					alert_icon(data.message);
				}

			}, error: function (xhr) {
				$spinner.remove();
				$button.prop('disabled', false);
				alert_icon(xhr);
				console.log(xhr);
			}
		})
	});
});