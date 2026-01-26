/**
 * Channel3 Tracking Script for WooCommerce
 *
 * Sends page view events to Channel3 for analytics tracking.
 * Configuration is passed via wp_localize_script as channel3TrackingConfig.
 */
(function() {
	'use strict';

	var config = window.channel3TrackingConfig || {};
	var CLIENT_ID_KEY = 'channel3_client_id';
	var pageSent = false;

	/**
	 * Get or generate a persistent client ID for visitor tracking.
	 *
	 * @return {string} UUID for the visitor.
	 */
	function getClientId() {
		var stored = localStorage.getItem( CLIENT_ID_KEY );
		if ( stored ) {
			return stored;
		}

		// Generate a UUID v4.
		var id;
		if ( typeof crypto !== 'undefined' && crypto.randomUUID ) {
			id = crypto.randomUUID();
		} else {
			// Fallback for older browsers.
			id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function( c ) {
				var r = Math.random() * 16 | 0;
				var v = c === 'x' ? r : ( r & 0x3 | 0x8 );
				return v.toString( 16 );
			});
		}

		localStorage.setItem( CLIENT_ID_KEY, id );
		return id;
	}

	/**
	 * Send a page view event to Channel3.
	 */
	function sendPageView() {
		// Guard against duplicate sends.
		if ( pageSent ) {
			return;
		}

		// Validate required config.
		if ( ! config.endpoint || ! config.accountId ) {
			return;
		}

		pageSent = true;

		var payload = {
			event: 'page_view',
			timestamp: new Date().toISOString(),
			url: window.location.href,
			pathname: window.location.pathname,
			referrer: document.referrer || null,
			title: document.title || null,
			clientId: getClientId(),
			accountId: config.accountId,
			productId: config.productId || null,
			sku: config.productSku || null,
			currency: config.currency || null
		};

		fetch( config.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( payload ),
			keepalive: true // Ensure delivery even on page unload.
		}).catch( function() {
			// Silently ignore errors - tracking should never break the site.
		});
	}

	/**
	 * Initialize tracking when DOM is ready.
	 */
	function init() {
		sendPageView();
	}

	// Run on DOMContentLoaded or immediately if already loaded.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
