<?php
/**
 * Channel3 for WooCommerce Uninstall
 *
 * Cleans up all plugin data when uninstalled.
 *
 * @package Channel3
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Revoke and delete API keys.
$channel3_key_id = get_option( 'channel3_api_key_id' );
if ( $channel3_key_id ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce API keys table, no WP API available.
	$wpdb->delete(
		$wpdb->prefix . 'woocommerce_api_keys',
		array( 'key_id' => $channel3_key_id ),
		array( '%d' )
	);
}

// Delete all Channel3 options.
$channel3_options = array(
	'channel3_connected',
	'channel3_connected_at',
	'channel3_api_key_id',
	'channel3_store_id',
	'channel3_webhook_secret',
	'woocommerce_channel3_settings',
);

foreach ( $channel3_options as $channel3_option ) {
	delete_option( $channel3_option );
}

// Delete admin notes.
if ( class_exists( 'Automattic\WooCommerce\Admin\Notes\Notes' ) ) {
	\Automattic\WooCommerce\Admin\Notes\Notes::delete_notes_with_name( 'channel3-welcome-note' );
}

// Clear any transients.
delete_transient( 'channel3_connection_check' );

// Log uninstallation (if WooCommerce logger is available).
if ( function_exists( 'wc_get_logger' ) ) {
	$channel3_logger = wc_get_logger();
	$channel3_logger->info( 'Channel3 for WooCommerce uninstalled and data cleaned up.', array( 'source' => 'channel3' ) );
}
