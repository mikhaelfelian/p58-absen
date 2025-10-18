/**
* Written by: Agus Prawoto Hadi
* Year		: 2024
* Website	: https://jagowebdev.com
*/

let optionFlatpickr = {
	enableTime: false,
	dateFormat: "d-m-Y",
	time_24hr: true,
	locale: "id"
}

jQuery(document).ready(function () 
{
	$('.has-children').mouseenter(function(){
		$(this).children('ul').stop(true, true).fadeIn('fast');
	}).mouseleave(function(){
		$(this).children('ul').stop(true, true).fadeOut('fast');
	});
	
	$('.has-children').click(function(){
		var $this = $(this);
		
		$(this).next().stop(true, true).slideToggle('fast', function(){
			$this.parent().toggleClass('tree-open');
		});
		return false;
	});
	
	$('#mobile-menu-btn').click(function(){
		$('body').toggleClass('mobile-menu-show');
		if ($('body').hasClass('mobile-menu-show')) {
			if (!is_mobile()) {
				Cookies.set('jwd_adm_mobile', '1');
			}
		} else {
			Cookies.set('jwd_adm_mobile', '0');
		}
		return false;
	});
	
	$('.sidebar-guide').mouseenter(function(){
		$('body').addClass('show-sidebar');
	});
	$('.sidebar').mouseleave(function(){
		$('body').removeClass('show-sidebar');
	});
	
	$('#mobile-menu-btn-right').click(function(){
		$('header').toggleClass('mobile-right-menu-show');
		return false;
	});
	
	bootbox.setDefaults({
		animate: false,
		centerVertical : true
	});
	
	// DELETE
	$('table').on('click', '[data-action="delete-data"]', function(e){
		e.preventDefault();
		var $this =  $(this)
			, $form = $this.parents('form:eq(0)');
		bootbox.confirm({
			message: $this.attr('data-delete-title'),
			callback: function(confirmed) {
				if (confirmed) {
					$form.submit();
				}
			},
			centerVertical: true
		});
	})
	
	var { 
		OverlayScrollbars, 
		ScrollbarsHidingPlugin, 
		SizeObserverPlugin, 
		ClickScrollPlugin  
	} = OverlayScrollbarsGlobal;
	const osInstance = OverlayScrollbars(document.querySelector('.sidebar'), {
		scrollbars : {autoHide: 'leave', autoHideDelay: 100}
	});
	
	$('.number-only').keyup(function(){
		this.value = this.value.replace(/\D/i, '');
	});
	
	if (typeof flatpickr != 'undefined') {
		flatpickr.localize(flatpickr.l10ns.id);
	}
	
	$.extend( $.fn.dataTable.defaults, {
		"language": {
			"processing": '<span><span class="spinner-border text-secondary" role="status"></span></span>',
		}
	});
});