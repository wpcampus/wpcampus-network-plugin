/**
 * Function that captures a click on an outbound link in Analytics.
 * This function takes a valid URL string as an argument, and uses that URL string
 * as the event label. Setting the transport method to 'beacon' lets the hit be sent
 * using 'navigator.sendBeacon' in browser that support it.
 */
var wpcampus_captureOutboundLink = function(url) {
	if ( typeof ga === 'undefined' ) {
		return;
	}
	ga('send', 'event', 'outbound', 'click', url, {
		'transport': 'beacon',
		'hitCallback': function(){document.location = url;}
	});
}

if ( 'loading' === document.readyState ) {

	// The DOM has not yet been loaded.
	document.addEventListener( 'DOMContentLoaded', wpcampus_initNetwork );
} else {

	// The DOM has already been loaded.
	wpcampus_initNetwork();
}

function wpcampus_initNetwork() {

	const ACTIONS = document.querySelectorAll( 'a' );
	if ( ! ACTIONS.length ) {
		return;
	}

	ACTIONS.forEach( function( action ) {
		action.addEventListener( 'click', wpcampus_captureAction );
    });
}

/* @TODO: right now it captures all links */
function wpcampus_captureAction(e) {
	wpcampus_captureOutboundLink( e.target.href );
}

