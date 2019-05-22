(function($) {
	'use strict';

	$(document).ready(function() {

		/*$('.wpcampus-login-ajax form').each(function(e) {
			e.preventDefault();

			console.log('login');

			return false;
		});*/

		/*$('.wpc-login-ajax').on('click',function(e){
			e.preventDefault();
			//wpcampus_login();
		});*/

		$('.wpc-logout-ajax').on('click',function(e){
			e.preventDefault();

			var args = {},
				$form = null,
				updateForm = false;

			if ($(this).hasClass('wpc-logout-update-gform')) {
				$form = $(this).closest('.gform_wrapper');
				if ($form.length) {
					var formID = $form.attr('id');
					if (formID){
						updateForm = true;
						args.update_gform = formID;
					}
				}
			}

			const logout = wpcampus_logout(args);
            logout.done(function(response){
            	if (updateForm) {
            		$form.replaceWith(response);
            	}
            });
		});
	});

	$.fn.replaceWith = function(html) {
		if ('' != html) {
			var $newElement = $(html);
			console.log($newElement);
			$(this).replaceWith(html);
		}
	}

	/*function wpcampus_login() {
		$.ajax({
			url: wpc_ajax_login.ajaxurl,
			type: 'POST',
			dataType: 'text',
			cache: false,
			async: true,
			data: {
				action: 'wpc_ajax_login',
				user_login: '',
				user_password: '',
				remember: ''
			},
			success: function(  ) {
				console.log('login success');
				console.log(response);
			},
			complete: function() {}
		});
	}*/

	function wpcampus_logout(args) {

		var data = {
			action: 'wpc_ajax_logout',
			wpc_ajax_logout_nonce: $('#wpc_ajax_logout_nonce').val()
		};

		console.log(data);

		return $.ajax({
			url: wpc_ajax_login.ajaxurl,
			type: 'POST',
			dataType: 'html',
			cache: false,
			async: true,
			data: data
		});
	}
})(jQuery);