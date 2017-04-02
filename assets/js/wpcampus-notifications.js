(function( $ ) {
	'use strict';

	// Process the notifications.
	$.get( 'https://wpcampus.org/wp-json/wp/v2/notifications?per_page=1' ).done(function( data ) {

		/*
		 * Get the template HTML.
		 *
		 * It is up to each theme to
		 * provide this template.
		 */
		var template = $( '#wpc-notification-template' ).html();
		Mustache.parse( template );

		// Render the template.
		var rendered = Mustache.render( template, data );

		// Add the result to the page.
		$( '#wpc-notifications' ).html( rendered ).fadeIn();

	});

})( jQuery );