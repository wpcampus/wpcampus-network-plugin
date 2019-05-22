(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Process each schedule.
		$('.wpcampus-sessions-container').each(function(){
			$(this).render_wpc_sessions();
		});
	});

	// Invoked by the sessions container.
	$.fn.render_wpc_sessions = function() {
		var $sessionsCont = $(this);

		// Let us know we're loading.
		$sessionsCont.addClass('loading');

		// Get the query arguments.
		var stateFilters = $sessionsCont.get_sessions_data_state(),
			queryStr = get_wpcampus_sessions_query_str(stateFilters);

		// Update the URL.
		window.history.pushState(stateFilters, '', window.location.pathname + queryStr);

		// Listen for navigation and update sessions from state.
        window.onpopstate = function(e) {
			if (e.state) {
				$sessionsCont.update_sessions_from_filters(e.state);
			}
		};

		// Get the sessions data.
		$.get( '/wp-json/wpcampus/data/sessions/' + queryStr, function(sessions) {

			if ( sessions === undefined ) {
				$sessionsCont.print_sessions_error_msg();
				return;
			}

			// Take care of the sessions.
			var sessions_template = $('#wpc-sessions-template').html();

			// Process the template.
			var process_sessions = Handlebars.compile(sessions_template);

			// Update the content.
			$sessionsCont.find('.wpcampus-sessions').html( process_sessions(sessions).trim() );

			// Update the count.
			$sessionsCont.set_sessions_count(sessions);

			// Update filters.
			$sessionsCont.update_sessions_filters();

			$sessionsCont.removeClass('loading');

		})
		.fail( function () {
			$sessionsCont.print_sessions_error_msg();
		});
	};

	// Invoked by the sessions container.
	$.fn.update_sessions_from_filters = function(filters) {
		var $sessionsCont = $(this);

		// Reset all data.
		$sessionsCont.removeData();

		console.log('filters before');
		console.log(filters);

		// Validate filters
		filters = validate_wpcampus_sessions_filters(filters);

		console.log('filters after');
		console.log(filters);

		// Update data.
		$.each(filters,function(filter,value) {
			$sessionsCont.data(filter,value);
		});

		// Update sessions.
		$sessionsCont.render_wpc_sessions();

	};

	// Invoked by the sessions container.
	$.fn.update_sessions_from_filter = function($filter) {
		var $sessionsCont = $(this),
			filter_name = $filter.attr('name'),
			value = $filter.val().toLowerCase();

		// Make sure we have filter info.
		if ( filter_name == '' || filter_name === undefined || filter_name === null ) {
			return false;
		}

		// You can have empty values.
		if ( ! value ) {
			value = null;
		} else {

			// Make sure its a valid filter.
			value = value.toLowerCase();
			if ( ! is_valid_wpcampus_sessions_filter(filter_name,value) ) {
				return false;
			}
		}

		// Store new filter value.
		if ( 'subjects' == filter_name ) {

			// Convert to array.
			$sessionsCont.data(filter_name,[ value ]);

		} else {
			$sessionsCont.data(filter_name,value);
		}

		// Update sessions.
		$sessionsCont.render_wpc_sessions();

	};

	// Invoked by the sessions container.
	$.fn.update_sessions_filters = function() {
		var $sessionsCont = $(this);

		// Get filters info.
		var filters = $sessionsCont.get_wpcampus_sessions_filters();

		// Take care of the sessions.
		var filters_template = $('#wpc-sessions-filters-template').html();

		// Process the template.
		var process_filters = Handlebars.compile( filters_template );

		console.log('filters');
		console.log(filters);

		// Update the content.
		$sessionsCont.find('.wpcampus-sessions-filters').html( process_filters( filters ).trim() );

		$sessionsCont.find('.wpcampus-sessions-filters-form').on('submit',function(e){
			e.preventDefault();
			//console.log($(this).serializeArray());
			//$(this).closest('.wpcampus-sessions-container').render_wpc_sessions();
		});

		/*$sessionsCont.find('.wpcampus-sessions-update').on('click',function(e){
			e.preventDefault();
			$(this).closest('.wpcampus-sessions-container').render_wpc_sessions();
		});*/

		$sessionsCont.find('input.wpcampus-sessions-filter').on('change',function(e){
			e.preventDefault();
			console.log(e);
			$sessionsCont.update_sessions_from_filter($(this));
		});

		$sessionsCont.find('select.wpcampus-sessions-filter').on('change',function(e){
			e.preventDefault();
			$sessionsCont.update_sessions_from_filter($(this));
		});
	};

	// Invoked by the sessions container.
    $.fn.get_wpcampus_sessions_filter = function(filter) {
		return $(this).data(filter);
	};

	// Invoked by the sessions container.
    $.fn.get_wpcampus_sessions_filters = function() {
    	var data = $(this).data(),
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

		/*var filterObj = {},
			sessionsData = $sessionsCont.data();*/

		// Process each session and get the subjects.
		//$.each(sessions, function(index,session){

			// Make sure we have subjects.
			/*if ( session.hasOwnProperty('subjects') ) {
				$.each(session.subjects, function(subIndex,subject){

					// Keeps track of IDs so we don't have duplicates.
					var subjectKey = 'subject-' + subject.term_id;
					if ($.inArray( subjectKey, subjects ) >= 0) {
						return true;
					}

					// Add to list of subjects.
					subjects.push( subjectKey );
					filterObj.subjects.push( subject );

				});
			}*/

			/*if ( session.hasOwnProperty('event') ) {

				// Keeps track of IDs so we don't have duplicates.
				var eventKey = 'event-' + session.event;
				if ($.inArray( eventKey, events ) < 0) {

					// Add to list of events.
					events.push( eventKey );
					filterObj.events.push({
						ID: session.event,
						name: session.event_name,
						slug: session.event_slug
					});
				}
			}*/
		//});

		// Sort subjects.
		/*if ( filterObj.subjects.length > 0 ) {
			filterObj.subjects.sort(function(a, b) {
				return a.name - b.name;
			});
		}*/

		/*// Add any filters.
		$.each(stateFilters, function(filter, value) {
			filterObj[ filter ] = value;
		});

		return filterObj;*/
	};

	// Invoked by the sessions container.
	$.fn.set_sessions_count = function(sessions) {
		var $sessionsCont = $(this),
			count = sessions.length,
			message = '';

		if ( count === 1 ) {
			message = 'There is 1 session.';
		} else {
			message = 'There are ' + count + ' sessions.';
		}

		$sessionsCont.find('.wpcampus-sessions-count').html(message);
	};

	// Invoked by the sessions container.
	$.fn.print_sessions_error_msg = function() {
		$(this).addClass('error').find('.wpcampus-sessions-error').html( wpc_sessions.load_error_msg );
		$(this).removeClass('loading');
	};

	// Invoked by the sessions container.
	$.fn.get_sessions_data_state = function() {
		var $sessionsCont = $(this),
			stateFilters = {};
		$.each($sessionsCont.data(),function(filter,value) {

			// Make sure it has a value.
			if ( value === undefined || value == '' || value === null ) {
				return true;
			}

			// Covers strings and arrays.
			if ( ! $.isArray(value) ) {
				value = value.split(',');
			}

			$.each(value, function(index,subvalue) {

				subvalue = subvalue.toLowerCase();

				// Make sure its a valid filter.
				if ( ! is_valid_wpcampus_sessions_filter(filter,subvalue) ) {
					return true;
				}

				// Store in state.
				stateFilters[ filter ] = subvalue;
			});
		});
		return stateFilters;
	}

	// Create query string for GET request.
	function get_wpcampus_sessions_query_str(filters) {
		var queryStr = '',
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
		var validatedFilters = {};
		$.each(filters,function(filter,value) {
			value = value.toLowerCase();
			if ( is_valid_wpcampus_sessions_filter(filter,value) ) {
				validatedFilters[ filter ] = value;
			}
		});
		return validatedFilters;
	}

	function is_valid_wpcampus_sessions_filter(filter,value) {

		// @TODO: HACK until fix
		if ( $.inArray( filter, ['search','subjects'] ) >= 0 ) {
			return true;
		}

		var validFilters = get_valid_wpcampus_sessions_filters(),
			value = value.toLowerCase();
		if ( ! validFilters.hasOwnProperty(filter) ) {
			return false;
		}
		return ( $.inArray( value, validFilters[filter] ) >= 0 );
	}

	function get_default_wpcampus_sessions_filters() {
		return {
			orderby: 'title'
		};
	}

	function get_valid_wpcampus_sessions_filters() {
		return {
			orderby: ['date','title'],
			order: ['asc','desc'],
			event: ['wpcampus-2018','wpcampus-2017','wpcampus-2016','wpcampus-online-2018','wpcampus-online-2017']
		};
	}

	Handlebars.registerHelper('selected', function(value,selectedValue) {
		return ( value == selectedValue ) ? ' selected="selected"' : null;
    });

	// Prints session date in site time.
    Handlebars.registerHelper('session_date', function() {
    	var dateString = '',
    		sessionDate = new Date( this.post_date_gmt + ' GMT' ),
    		month = sessionDate.getMonth(),
    		months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

		// Add offset to match site timezone.
        //sessionDate.setHours(sessionDate.getUTCHours() + parseInt(wpc_sessions.tz_offset));

		dateString += months[month] + ' ' + sessionDate.getDate() + ', ' + sessionDate.getFullYear();

		return dateString;
	});

	Handlebars.registerHelper('session_event_name', function() {
		if (this.event_slug.startsWith('wpcampus-online')) {
			return 'WPCampus Online';
		}
		return this.event_name;
	});

    Handlebars.registerHelper('media_thumbnail', function(defaultThumb) {
    	if (!this.session_video_url) {
    		return defaultThumb;
    	}
    	return this.session_video_thumbnail ? this.session_video_thumbnail : defaultThumb;
	});
})(jQuery);