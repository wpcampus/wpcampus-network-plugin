(function($) {
	'use strict';

	$(document).ready(function() {

		// Make sure the template exists.
		var template_id = 'wpc-watch-template';
		if ( ! $( '#' + template_id ).length ) {
			return;
		}

		var videos_url = wpc_network.main_url + '/wp-json/wpcampus/data/videos';

		$( '.wpc-watch' ).each(function() {
			var $wpc_watch = $(this),
				playlist = $wpc_watch.data( 'playlist' );

			if ( playlist !== undefined && playlist != '' ) {
				videos_url += '?playlist=' + playlist;
			}

			// Get the videos data.
			$.get( videos_url, function(videos) {

				/*
				 * Get the template HTML.
				 *
				 * It is up to each theme to
				 * provide this template.
				 */
				var template_html = $( '#' + template_id ).html();
				var template = Handlebars.compile(template_html);

				// Render the template.
				var rendered = template( videos );

				// Add the result to the page.
				$wpc_watch.fadeTo(1000,0,function(){
					$wpc_watch.removeClass('loading').html(rendered).fadeTo(1000,1,function(){

						$('.video-popup').magnificPopup({
							disableOn: 700,
							type: 'iframe',
							mainClass: 'mfp-fade',
							removalDelay: 160,
							preloader: false,
							fixedContentPos: false
						});
					});
				});
			});
		});
	});

	Handlebars.registerHelper( 'videos_count_message', function( options ) {
		var message = '';
		if ( 1 == this.length ) {
			message = 'There is ' + this.length + ' video.';
		} else {
			message = 'There are ' + this.length + ' videos.';
		}
		return new Handlebars.SafeString( '<p class="wpc-watch-count">' + message + '</p>' );
	});

})(jQuery);
