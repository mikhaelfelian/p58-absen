jQuery(document).ready(function () {	
	bootbox.setDefaults({
		animate: false,
		centerVertical : true
	});
	
	$('form').submit(function(e) 
	{
		e.preventDefault();
		$button = $(this).find('button');
		$spinner = $('<div class="spinner-border spinner-border-sm me-2" role="status">');
		$button.prop('disabled', true);
		$button.prepend($spinner);
		$form = $('form');
		$.ajax({
			url: base_url + 'login',
			type: 'POST',
			data: $form.serialize() + '&ajax=true',
			success: function(data) {
				data = JSON.parse(data);
				if (data.status == 'error') {
					$spinner.remove();
					$button.prop('disabled', false);
					bootbox.alert('<div class="d-flex align-items-center"><span class="text-danger" style="font-size:25px"><i class="far fa-times-circle me-3"></i></span>' + data.message + '</div>');
				} else {
					window.location = base_url;
				}
			}, error: function(xhr) {
				$spinner.remove();
				$button.prop('disabled', false);
				bootbox.alert('AJAX Error, cek console browser');
				console.log(xhr)
			}
		})
	});
});