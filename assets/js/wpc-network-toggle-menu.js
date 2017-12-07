(function( $ ) {
	'use strict';

	var openMenuClass = 'wpc-menu-open';

	$(document).ready(function() {

		$('.wpc-toggle-menu').on('click', function() {
			$(this).wpc_network_toggle_menu();
		});
	});

	$.fn.wpc_network_toggle_menu = function() {
		var $toggle = $(this),
			$menuContainer = null;

		if ($toggle.data('toggle')) {
			$menuContainer = $('#' + $toggle.data('toggle'));
		}

		if ( !$menuContainer ) {
			$menuContainer = $toggle.closest('.wpc-menu-container');
		}

		// Open or close the menu.
		if ( $menuContainer.hasClass(openMenuClass) ) {
			$menuContainer.wpc_network_close_toggle_menu();
		} else {
			$menuContainer.wpc_network_open_toggle_menu();
		}
	};

	$.fn.wpc_network_open_toggle_menu = function() {
		var $menuContainer = $(this);
		$('body').addClass(openMenuClass);
		$menuContainer.addClass(openMenuClass);
		$(window).on('keydown.wpcMenu', function(e) {

			switch (e.keyCode) {

				// ESC
				case 27:
					$menuContainer.wpc_network_close_toggle_menu();
					break;

				// TAB
				case 9:
					var $target = $(e.target),
						$firstFocusableChild = $menuContainer.find(':tabbable:first'),
						$lastFocusableChild = $menuContainer.find(':tabbable:last');

					if (e.shiftKey) {
						if ($target.get(0) === $firstFocusableChild.get(0)) {
							e.preventDefault();
							$lastFocusableChild.focus();
						}
					} else {
						if ($target.get(0) === $lastFocusableChild.get(0)) {
							e.preventDefault();
							$firstFocusableChild.focus();
						}
					}
					break;
			}
		});
	};

	$.fn.wpc_network_close_toggle_menu = function() {
		var $menuContainer = $(this);
		$('body').removeClass(openMenuClass);
		$menuContainer.removeClass(openMenuClass).find('.wpc-toggle-menu').focus();
		$(window).off('keydown.wpcMenu');
	};
})(jQuery);