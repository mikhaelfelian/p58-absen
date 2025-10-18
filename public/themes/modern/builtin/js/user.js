/**
* Written by: Agus Prawoto Hadi
* Year		: 2024
* Website	: jagowebdev.com
*/

jQuery(document).ready(function () {
	
	$('.select2').select2({
		theme: 'bootstrap-5'
	})
	
	if ($('input[name="mobile"').val() == 'true') {
		$('.flatpickr').flatpickr({
			dateFormat: "d-m-Y",
			animate: false,
			onClose: function(selectedDates, dateStr, instance){
				$('.overlay').remove();
			},
			onOpen: [
				function(selectedDates, dateStr, instance){
					$flatpickr_calendar = $('.flatpickr-calendar');
					$('body').append('<div class="overlay" style="background:rgba(0,0,0,0.5);position:fixed;width:100%;height:100vh;z-index:10"></div>');
					$flatpickr_calendar.appendTo('body');
			}]
		});
	} else {
		$('.flatpickr').flatpickr({
			dateFormat: "d-m-Y"
		})
	}
	
	if ($('#table-result').length) {
		column = $.parseJSON($('#dataTables-column').html());
		url = $('#dataTables-url').text();
		
		 var settings = {
			"processing": true,
			"serverSide": true,
			"scrollX": true,
			"ajax": {
				"url": url,
				"type": "POST",
				/* "dataSrc": function (json) {
					console.log(json)
				} */
			},
			"columns": column,
			"initComplete": function( settings, json ) {
				if (json.data.length > 0) {
					$('#btn-excel').prop('disabled', false);
				} else {
					$('#btn-excel').prop('disabled', true);
				}
			 }
		}
		
		$add_setting = $('#dataTables-setting');
		if ($add_setting.length > 0) {
			add_setting = $.parseJSON($('#dataTables-setting').html());
			for (k in add_setting) {
				settings[k] = add_setting[k];
			}
		}
		
		dataTables =  $('#table-result').DataTable( settings );
	}
	
	$('.select2').select2({
		theme: 'bootstrap-5'
	})
	
	$('.select-role').change(function() {

		list_role = $(this).val();
		list_option = '';
		$('.select-role').find('option').each(function(i, elm) 
		{
			$elm = $(elm)
			value = $elm.attr('value');
			label = $elm.html();
			if (list_role.includes(value)) {
				list_option += '<option value="' + value + '">' + label  + '</option>';
			}
		})
		current_value = $('.default-page-id-role').val();
		$select = $('.default-page-id-role').children('select');
		$select.empty();
		
		if (list_option) {
			
			$select.append(list_option);
			if (!current_value) {
				current_value = $select.find('option:eq(0)').val();
			} 
			$select.val(current_value);
		} else {
			$select.append('<option value="">-- Pilih Role --</option>');
		}
		
	})
	
	$('#option-default-page').change(function(){
		$this = $(this);
		$parent = $this.parent();
		$parent.find('.default-page').hide();
		if ($this.val() == 'url') {
			$parent.find('.default-page-url').show();
		} else if ($this.val() == 'id_module') {
			$parent.find('.default-page-id-module').show();
		} else {
			$parent.find('.default-page-id-role').show();
		}
	})
	
	$('#option-ubah-password').change(function() {
		if ($(this).val() == 'Y') {
			$('#password-container').show();
		} else {
			$('#password-container').hide();
		}
	});
	
	$('.submit-data').click(function(e) {
		e.preventDefault();
		form = $('.form-user')[0];
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<span class="spinner-border spinner-border-sm me-2"></span>');
		$spinner.prependTo($this);
		$.ajax({
			type: 'POST',
			url: base_url + 'builtin/user/ajaxSaveData',
			data: new FormData(form),
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function (data) {
				
				if (data.status == 'ok') 
				{
					if ($('input[name="mobile"]').val() == 'true') {
						toast_mobile('<i class="bi bi-check-circle me-2"></i>Data berhasil disimpan');
					} else {
						alert_toast('Data berhasil disimpan');
					}
					
					$('.file').val('');
					$upload_image_thumb = $('.upload-file-thumb');
					$file = $('.file').parent();
					$new_image = $upload_image_thumb.find('img');
					if ($new_image.length > 0) {
						image_url = base_url + 'public/images/pegawai/' + file.name + '?r=' + (Math.random() * 1000);
						$file.find('.img-choose').remove();
						$img_choose = '<div class="img-choose" style="margin:inherit;margin-bottom:10px">'
							+ '<div class="img-choose-container">'
								+ '<img src="' + image_url + '"/>'
								+ '<a href="javascript:void(0)" class="remove-img"><i class="fas fa-times"></i></a>'
							+ '</div>'
						+ '</div>';
						$file.prepend($img_choose);
					}
					$upload_image_thumb.find('img').remove();
					$upload_image_thumb.find('.file-prop').empty();
					$upload_image_thumb.hide();
					email = $('input[name="email"]').val();
					$('input[name="email_lama"]').val(email);
					
					$('#id-pegawai').val(data.id_pegawai);
				
				} else {
					alert_icon(data.message);
				}
				
				$spinner.remove();
				$this.prop('disabled', false);
			},
			error: function (xhr) {
				$spinner.remove();
				$this.prop('disabled', false);
				alert_icon(xhr);
				console.log(xhr);
			}
		})
	})
	
	$('.clear-form').click(function(e) {
		$form = $('.form-user');
		$form.find('input, textarea').val('');
		$form.find('.remove-img').trigger('click');
		$select = $form.find('select');
		$select.each(function(i, elm) {
			$elm = $(elm);
			
			if ($elm.attr('name') == 'option_default_page') {
				$elm.val('id_module').trigger('change');
			} else if ($elm.attr('name') == 'default_page_id_module') {
				$elm.val(5);
			} else if (!$elm.hasClass('propinsi') && !$elm.hasClass('kabupaten') && !$elm.hasClass('kecamatan') && !$elm.hasClass('kelurahan')) {
				$elm.find('option').prop("selected", false);
				$('.select2').select2({
					theme: 'bootstrap-5'
				})
				// $(elm).val(value);
			}
		});
		$image_choose = $form.find('.upload-file-thumb').hide();
		$image_choose.find('img').remove();
		$image_choose.find('.file-prop').empty();
	})
	
	$('#btn-excel').click(function() {
		$this = $(this);
		$this.prop('disabled', true);
		$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
		$spinner.prependTo($this);
		
		filename = 'Daftar Pegawai - ' + format_date('dd-mm-yyyy') + '.xlsx';
		export_url = base_url + 'builtin/user/ajaxExportExcel';
		fetch(export_url)
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
	})
	
	$('body').delegate('.btn-delete', 'click', function(e) {
		e.preventDefault();
		id = $(this).attr('data-id');
		$bootbox = bootbox.confirm({
			message: $(this).attr('data-delete-title'),
			callback: function(confirmed) {
				if (confirmed) {
					$button = $bootbox.find('button');
					$button.attr('disabled', 'disabled');
					$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
					$spinner.prependTo($bootbox.find('.bootbox-accept'));
					$.ajax({
						type: 'POST',
						url: base_url + 'builtin/user/ajaxDeleteData',
						data: 'id=' + id,
						dataType: 'json',
						success: function (data) {
							$bootbox.modal('hide');
							$spinner.remove();
							$button.removeAttr('disabled');
							if (data.status == 'ok') {
								alert_toast('Data berhasil dihapus');
								dataTables.draw();
							} else {
								alert_icon(data.message);
							}
						},
						error: function (xhr) {
							$spinner.remove();
							$button.removeAttr('disabled');
							alert_icon(xhr);
							console.log(xhr);
						}
					})
					return false;
				}
			},
			centerVertical: true
		});
	})
	
	$('.btn-delete-all-user').click(function() {
		$bootbox =  bootbox.dialog({
			title: 'Hapus Semua Data',
			message: '<div class="text-center text-secondary"><div class="spinner-border"></div></div>',
			buttons: {
				cancel: {
					label: 'Cancel'
				},
				success: {
					label: 'Hapus',
					className: 'btn-danger submit',
					callback: function() 
					{
						$bootbox.find('.alert').remove();
						$spinner = $('<div class="spinner-border spinner-border-sm me-2"></div>');
						$button_submit.prepend($spinner);
						$button.prop('disabled', true);
						
						$.ajax({
							type: 'POST',
							url: base_url + 'builtin/user/ajaxDeleteAllUser',
							dataType: 'text',
							success: function (data) {
								data = $.parseJSON(data);
								console.log(data);
								$spinner.remove();
								$button.prop('disabled', false);
								
								if (data.status == 'ok') {
									$bootbox.modal('hide');
									alert_toast('Data berhasil dihapus');
									dataTables.draw();
								} else {
									show_alert('Error !!!', data.message, 'error');
								}
							},
							error: function (xhr) {
								show_alert('Error !!!', xhr.responseText, 'error');
								console.log(xhr.responseText);
							}
						})
						return false;
					}
				}
			}
		});
		
		var $button = $bootbox.find('button').prop('disabled', true);
		var $button_submit = $bootbox.find('button.submit');
		
		$.get(base_url + 'builtin/user/ajaxGetUserAdmin', function(data){
			list_pegawai = '';
			if (data) {
				data = JSON.parse(data);
				list_pegawai = '<ul class="list-circle">';
				data.map(function(v) {
					list_pegawai += '<li>' + v.nama + '</li>';
				})
				list_pegawai += '</ul>';
			}
			
			if (list_pegawai) {
				content = '<div>Semua data pegawai akan dihapus <strong>kecuali pegawai dengan role admin</strong>. Berikut pegawai dengan role admin:</div>' + list_pegawai;
				$button.prop('disabled', false);
			} else {
				content = 'Untuk dapat menghapus semua data pegawai setidaknya harus ada satu pegawai dengan role admin';
				$bootbox.find('.close, .bootbox-cancel').prop('disabled', false);
			}
			$bootbox.find('.modal-body').empty().append(content);
		});
	});
	
	
});