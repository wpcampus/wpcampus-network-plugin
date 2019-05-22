(function($) {
	'use strict';

	var template_id = 'wpc-watch-template';

	$(document).ready(function() {

		// Make sure the template exists.
		if ( ! $( '#' + template_id ).length ) {
			return;
		}

		$('.wpc-watch').each(function() {
			var $wpc_watch = $(this),
				videos_query = '';

			$.each(['playlist','category','search'], function(index, key) {
				var value = $wpc_watch.data(key);
				if ( value !== undefined && value != '' ) {
					if ( videos_query != '' ) {
						videos_query += '&';
					} else {
						videos_query += '?';
					}
					videos_query += key + '=' + value;
				}
			});

			$wpc_watch.wpcampus_load_watch_videos( videos_query );

		});

		$('.wpc-watch-filters').each(function(){
			var $wpc_filters = $(this);

			// Make sure we have an ID and it exists.
			if ( $wpc_filters.data('videos') === undefined || ! $wpc_filters.data('videos') ) {
				return;
			}

			var $filter_videos = $( '#' + $wpc_filters.data('videos') );
			if ( ! $filter_videos.length ) {
				return;
			}

			$wpc_filters.find('.button.clear').on('click keypress',function(e) {
				e.preventDefault();

				$wpc_filters.find('form select,input[type="search"]').each(function(){
					$(this).val('');
				});

				$wpc_filters.addClass('loading').removeClass('has-filters').find('input[type="submit"]').focus();

				$filter_videos.addClass('loading').wpcampus_load_watch_videos();
			});

			$wpc_filters.find('form').on('submit', function(e) {
				e.preventDefault();
				var $form = $(this),
					search_query = '';

				$form.find('select,input[type="search"]').each(function(){
					var field_val = $(this).val();
					if ( field_val != '' ) {
						if ( search_query != '' ) {
							search_query += '&';
						} else {
							search_query += '?';
						}
						search_query += $(this).attr('name') + '=' + field_val;
					}
				});

				if ( search_query != '' ) {
					$wpc_filters.addClass('has-filters');
				} else {
					$wpc_filters.removeClass('has-filters');
				}

				$wpc_filters.addClass('loading');

				$filter_videos.addClass('loading').wpcampus_load_watch_videos( search_query );

			});
		});
	});

	Handlebars.registerHelper( 'video_event', function( options ) {
		var event = '';
		if ( 'podcast' == this.post_type ) {
			event = 'WPCampus Podcast';
		} else if ( this.event_name != '' ) {
			event = this.event_name;
		} else {
			return '';
		}
		return new Handlebars.SafeString( '<span class="video-event">' + event + '</span>' );
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

	$.fn.wpcampus_load_watch_videos = function( videos_query ) {
		var $wpc_watch = $(this),
			$wpc_filters = $('*[data-videos="' + $wpc_watch.attr('id') + '"]'),
			videos_url = wpc_net_watch.main_url + 'wp-json/wpcampus/data/videos',
			videos = '';

		if ( videos_query === undefined ) {
			videos_query = '';
		}

		// Get the videos data.
		$.get( videos_url + videos_query, function(the_videos) {
			videos = the_videos;
		}).always(function() {

			if ( videos === undefined || videos === null || ! videos ) {
				$wpc_watch.fadeTo(1000,0,function(){
					$wpc_filters.removeClass('loading');
					$wpc_watch.addClass('no-videos').html('<p class="wpc-watch-no-videos">' + wpc_net_watch.no_videos + '</p>').removeClass('loading').fadeTo(1000,1);
				});
			} else {

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

					$wpc_filters.removeClass('loading');

					$wpc_watch.html(rendered).removeClass('loading').fadeTo(1000,1,function(){

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
			}
		});
	}
})(jQuery);
