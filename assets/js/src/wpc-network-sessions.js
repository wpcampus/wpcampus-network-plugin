(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Process each schedule.
		$('.wpcampus-sessions-container').each(function(){
			$(this).render_wpc_sessions_reset_focus();
		});
	});

	function get_wpc_sessions( filters ) {
		return $.ajax({
			url: wpc_sessions.ajaxurl,
			type: 'GET',
			dataType: 'json',
			async: true,
			cache: true,
			data: {
				action: 'wpcampus_get_sessions',
				filters: filters
			}
		});
	}

	// Invoked by the sessions container.
	$.fn.render_wpc_sessions = function() {
		let $sessionsCont = $(this),
			dfd = $.Deferred();

		// Let us know we're loading.
		$sessionsCont.addClass('loading');
		$sessionsCont.find('#wpcampus-sessions-notification').html( 'The library list is updating.' );

		// Get the query arguments.
		let stateFilters = $sessionsCont.get_sessions_data_state(),
			queryStr = get_wpcampus_sessions_query_str( stateFilters );

		// Update the URL.
		window.history.pushState( stateFilters, '', window.location.pathname + queryStr );

		// Listen for navigation and update items from state.
        window.onpopstate = function(e) {
			if ( e.state ) {
				$sessionsCont.update_sessions_from_filters(e.state);
			}
		};

		let sessionsData = undefined;

		const getSessions = get_wpc_sessions( stateFilters );
		getSessions.done(function( data ) {
			sessionsData = data;
		}).always(function(data){

			// @TODO setup error?
			if ( sessionsData === undefined ) {
				$sessionsCont.print_sessions_error_msg();
				return;
			}

			if ( ! sessionsData.sessions ) {
				sessionsData.sessions = null;
			}

			// Take care of the sessions.
			let sessions_template = $('#wpc-sessions-template').html();

			// Process the template.
			let process_sessions = Handlebars.compile( sessions_template );

			// Store the active element right before we re-load.
			$sessionsCont.data( 'activeElement', document.activeElement );

			// Update the content.
			$sessionsCont.find( '.wpcampus-sessions' ).html( process_sessions( sessionsData.sessions ).trim() );

			// Update the count.
			$sessionsCont.set_sessions_count( sessionsData );

			// Update filters.
			$sessionsCont.update_sessions_filters();

			$sessionsCont.removeClass( 'loading' );
			$sessionsCont.find( '#wpcampus-sessions-notification' ).html();

			dfd.resolve();
		});

		return dfd.promise();
	};

	// Invoked by the sessions container.
	$.fn.render_wpc_sessions_reset_focus = function() {
		let $sessionsCont = $(this),
			activeElement = document.activeElement,
			render = $sessionsCont.render_wpc_sessions();
		render.always(function() {
			let currentActiveElement = $sessionsCont.data('activeElement');
			if ( currentActiveElement.id ) {
				document.getElementById(currentActiveElement.id).focus();
			} else if ( activeElement.id ) {
				document.getElementById(activeElement.id).focus();
			}
		});
	};

	// Invoked by the sessions container.
	$.fn.update_sessions_from_filters = function(filters) {
		let $sessionsCont = $(this);

		// Reset all data.
		$sessionsCont.removeData();

		// Validate filters.
		filters = validate_wpcampus_sessions_filters(filters);

		// Update data.
		$.each(filters,function(filter,value) {
			$sessionsCont.update_sessions_data( filter, value, false, false );
		});

		// Update items.
		$sessionsCont.render_wpc_sessions_reset_focus();
	};

	// Invoked by the sessions container.
	// Updates the session data but doesn't update the sessions. Returns true if valid.
	// If remove is true, then we should remove the value from the data.
	$.fn.update_sessions_data = function( filterName, value, remove, replace ) {
		let $sessionsCont = $(this);

		// Make sure we have filter info.
		if ( filterName == '' || filterName === undefined || filterName === null ) {
			return false;
		}

		// Remove array indicator from filter name.
		filterName = filterName.replace( '[]', '' );

		// You can have empty values.
		if ( ! value ) {
			value = null;
		} else {

			value = value.toLowerCase();

			// Separate out orderby.
			if ( 'orderby' == filterName ) {
				let valueSplit = value.split(',');

				// Set the new orderby value.
				value = valueSplit.shift();

				// Update the order.
				$sessionsCont.update_sessions_data( 'order', valueSplit.shift(), remove, replace );

			}

			// Make sure its a valid filter.
			if ( ! is_valid_wpcampus_sessions_filter(filterName,value) ) {
				return false;
			}
		}

		if ( !remove && !value) {
			remove = true;
		}

		// Get existing data.
		let existingFilter = $sessionsCont.data( filterName );

		if ( undefined === existingFilter || !existingFilter ) {

			if (remove) {
				$sessionsCont.removeData( filterName );
			} else {
				$sessionsCont.data( filterName, value );
			}

			return true;
		}

		// The value is already defined.
		if ( existingFilter === value ) {

			if (remove) {
				$sessionsCont.removeData( filterName );
			}

			return true;
		}

		if ( Array.isArray( existingFilter ) ) {

			if (replace) {
				existingFilter = [];
			} else {

				let findValue = existingFilter.indexOf(value);

				if (findValue >= 0) {

					if (!remove) {
						return true;
					}

					existingFilter.splice(findValue, 1);
				}
			}
		} else {

			if (remove) {
				$sessionsCont.removeData( filterName );
				return true;
			}

			// Convert to array and add new value.
			if (replace) {
				existingFilter = [];
			} else {
				existingFilter = [ existingFilter ];
			}
		}

		if (!remove) {
			existingFilter.push( value );
		}

		$sessionsCont.data( filterName, existingFilter );

		return true;
	};

	// Invoked by the sessions container.
	// Updates the session data but doesn't update the sessions. Returns true if valid.
	$.fn.update_sessions_data_from_filter = function($filter) {
		let $sessionsCont = $(this),
			filterName = $filter.attr('name'),
			value = $filter.val().toLowerCase(),
			remove = ( 'checkbox' === $filter.prop('type') && !$filter.prop('checked') ),
			replace = ( 'checkbox' !== $filter.prop('type') );

		return $sessionsCont.update_sessions_data( filterName, value, remove, replace );
	};

	// Invoked by the sessions container.
	$.fn.update_sessions_filters = function() {
		let $sessionsCont = $(this);

		// Get filters info.
		let filters = $sessionsCont.get_wpcampus_sessions_filters();

		// Take care of the sessions.
		let filters_template = $('#wpc-sessions-filters-template').html();

		// Process the template.
		let process_filters = Handlebars.compile( filters_template );

		// Update the content.
		$sessionsCont.find('.wpcampus-sessions-filters').html( process_filters( filters ).trim() );

		$sessionsCont.find('.wpcampus-sessions-filters-form').on('submit',function(e){
			e.preventDefault();

			// Will be true if we can update items render.
			let result = false;

			$sessionsCont.find( 'input.wpcampus-sessions-filter, select.wpcampus-sessions-filter' ).each(function(){
				result = $sessionsCont.update_sessions_data_from_filter($(this));
			});

			if ( true === result ) {
				$sessionsCont.render_wpc_sessions_reset_focus();
			}
		});

		/*$sessionsCont.find('input.wpcampus-sessions-filter, select.wpcampus-sessions-filter').on('change',function(e){
			e.preventDefault();
			if ( true === $sessionsCont.update_sessions_data_from_filter($(this)) ) {
				$sessionsCont.render_wpc_sessions_reset_focus();
			}
		});*/
	};

	// Invoked by the sessions container.
    $.fn.get_wpcampus_sessions_filter = function(filter) {
		return $(this).data(filter);
	};

	// Invoked by the sessions container.
    $.fn.get_wpcampus_sessions_filters = function() {
    	let data = $(this).data(),
    		defaults = get_default_wpcampus_sessions_filters();

    	// Set defaults.
    	$.each(defaults, function(filter,value){
			if ( ! data.hasOwnProperty(filter) ) {
				data[filter] = value;
			} else if ( data[filter] === undefined || data[filter] === null ) {
				data[filter] = value;
			}
    	});

		return data;
	};

	// Invoked by the sessions container.
	$.fn.set_sessions_count = function( sessionsData ) {
		let $sessionsCont = $(this),
			message = '',
			count = 0;

		if ( undefined !== sessionsData.count ) {
			count = sessionsData.count;
		}

		if ( 1 === count ) {
			message = 'There is 1 item.';
		} else if ( 0 === count ) {
			message = 'There are no items that match your selection.';
		} else {
			message = 'There are ' + count + ' items.';
		}

		$sessionsCont.find('.wpcampus-sessions-count').html( message );
	};

	// Invoked by the sessions container.
	$.fn.print_sessions_error_msg = function() {
		$(this).addClass('error').find('.wpcampus-sessions-error').html( wpc_sessions.load_error_msg );
		$(this).removeClass('loading');
	};

	// Invoked by the sessions container.
	$.fn.get_sessions_data_state = function() {
		let $sessionsCont = $(this),
			stateFilters = {},
			currentData = $sessionsCont.data();

		// Don't store this in state.
		if ( currentData.activeElement ) {
			delete currentData.activeElement;
		}

		$.each(currentData,function(filter,value) {

			// Make sure it has a value.
			if ( value === undefined || value == '' || value === null ) {
				return true;
			}

			// Covers strings and arrays.
			if ( value && 'string' === typeof value ) {
				value = value.split(',');
			}

			let filteredValues = [];

			$.each( value, function(index,subvalue) {

				// Make sure its a valid filter.
				if ( 'string' === typeof subvalue ) {
					if ( is_valid_wpcampus_sessions_filter(filter,subvalue) ) {
						filteredValues.push( subvalue.toLowerCase() );
					}
				}
			});

			// Store in state.
			if ( filteredValues.length ) {
				stateFilters[ filter ] = filteredValues.join( ',' );
			}
		});
		return stateFilters;
	}

	// Create query string for GET request.
	function get_wpcampus_sessions_query_str(filters) {
		let queryStr = '',
			defaults = get_default_wpcampus_sessions_filters();

		$.each(filters,function(filter,value) {

			value = value.toLowerCase();

			// Don't add if a default value.
			if ( defaults.hasOwnProperty(filter) && defaults[filter] == value ) {
				return true;
			}

			if ( queryStr != '' ) {
				queryStr += '&';
			} else {
				queryStr += '?';
			}
			queryStr += filter + '=' + value;

		});

		return queryStr;
	}

	function validate_wpcampus_sessions_filters(filters) {
		let validatedFilters = {};

		$.each(filters,function(filter,value) {
			value = value.toLowerCase();
			if ( is_valid_wpcampus_sessions_filter(filter,value) ) {
				validatedFilters[ filter ] = value;
			}
		});
		return validatedFilters;
	}

	function is_valid_wpcampus_sessions_filter( filter, value ) {

		// @TODO: HACK until fix
		if ( $.inArray( filter, ['search','subject','format'] ) >= 0 ) {
			return true;
		}

		let validFilters = get_valid_wpcampus_sessions_filters();

		// Remove array indicator from filter name.
		filter = filter.replace( '[]', '' );

		if ( ! validFilters.hasOwnProperty(filter) ) {
			return false;
		}

		// Allows us to only check the filter.
		if ( ! value ) {
			return true;
		}

		return ( $.inArray( value.toLowerCase(), validFilters[filter] ) >= 0 );
	}

	function get_default_wpcampus_sessions_filters() {
		return {
			orderby: 'title',
			order: 'asc'
		};
	}

	function get_valid_wpcampus_sessions_filters() {
		return {
			assets: ['slides','video'],
			orderby: ['date','title'],
			order: ['asc','desc'],
			event: ['wpcampus-2019','wpcampus-2018','wpcampus-2017','wpcampus-2016','wpcampus-online-2019','wpcampus-online-2018','wpcampus-online-2017']
		};
	}

	Handlebars.registerHelper( 'sessionInfoWrapperClasses', function() {
		if ( this.session_slides_url || this.session_video_url ) {
		let classes = [];
			classes.push('has-session-sidebar');
		}
		return classes.join(' ');
	});

	Handlebars.registerHelper( 'sessionSidebar', function() {
		let assets = [];

		if ( this.session_slides_url ) {
			/*let label = '',
				wrapperStart = '',
				wrapperEnd = '';*/

			//if ( this.permalink ) {
				let label = 'Slides';
				wrapperStart = '<a class="session-sidebar__asset" href="' + this.session_slides_url + '#slides">';
				wrapperEnd = '</a>';
			/*} else {
				label = 'Slides';
				wrapperStart = '<span class="session-sidebar__asset">';
				wrapperEnd = '</span>';
			}*/

			assets.push( '<li>' + wrapperStart + '<i aria-hidden="true" class="conf-sch-icon conf-sch-icon-slides"></i> <span class="session-sidebar__asset__label">' + label + wrapperEnd + '</span></li>' );
		}

		if ( this.session_video_url ) {
			let label = '',
				wrapperStart = '',
				wrapperEnd = '';

			if ( this.permalink ) {
				label = 'Video';
				wrapperStart = '<a class="session-sidebar__asset" href="' + this.permalink + '#video">';
				wrapperEnd = '</a>';
			} else {
				label = 'Video';
				wrapperStart = '<span class="session-sidebar__asset">';
				wrapperEnd = '</span>';
			}

			assets.push( '<li>' + wrapperStart + '<i aria-hidden="true" class="conf-sch-icon conf-sch-icon-video"></i> <span class="session-sidebar__asset__label">' + label + wrapperEnd + '</span></li>' );
		}

		if ( ! assets.length ) {
			return null;
		}

		return new Handlebars.SafeString( '<div class="session-sidebar"><ul>' + assets.join( '' ) + '</ul></div>' );
    });

	Handlebars.registerHelper( 'selected_orderby', function( orderBy, order ) {
		let selected = ' selected="selected"';
		if ( orderBy == this.orderby && order == this.order ) {
			return selected;
		} else if ( ! this.orderby && order == this.order && 'title' == orderBy ) {
			return selected;
		} else if ( ! this.order && orderBy == this.orderby && 'asc' == order ) {
			return selected;
		} else if ( ! this.order && 'date' == this.orderby && this.orderby == orderBy && 'desc' == order ) {
			return selected;
		} else if ( ! this.orderby && ! this.order && 'title' == orderBy && 'asc' == order ) {
			return selected;
		}
		return null;
	});

	Handlebars.registerHelper( 'checked_assets', function( assets ) {
		if (!this.assets) {
			return null;
		}

		let checked = ' checked="checked"';

		if (this.assets.includes(assets)) {
			return checked;
		}

		return null;
	});

	Handlebars.registerHelper( 'selected', function(value,selectedValue) {
		return ( value == selectedValue ) ? ' selected="selected"' : null;
    });

	// Prints session date in site time.
    Handlebars.registerHelper('session_date', function() {
    	if ( ! this.post_date ) {
    		return null;
    	}

    	let origDate = this.post_date,
			dateSplit = origDate.split(' '),
    		newDate = dateSplit.join('T'),
    		dateString = '',
    		sessionDate = new Date( newDate ), //this.post_date_gmt
    		month = sessionDate.getMonth(),
    		months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

		// Add offset to match site timezone. I hate timezones.
        //sessionDate.setHours(sessionDate.getUTCHours() + parseInt(wpc_sessions.tz_offset));

		dateString += months[month] + ' ' + sessionDate.getDate() + ', ' + sessionDate.getFullYear();

		return dateString;
	});

	Handlebars.registerHelper( 'session_event_name', function() {
		if (this.event_slug && this.event_slug.startsWith('wpcampus-online')) {
			return 'WPCampus Online';
		}
		return this.event_name;
	});

    Handlebars.registerHelper( 'media_thumbnail', function(defaultThumb) {
    	if (!this.session_video_url) {
    		return defaultThumb;
    	}
    	return this.session_video_thumbnail ? this.session_video_thumbnail : defaultThumb;
	});
})(jQuery);