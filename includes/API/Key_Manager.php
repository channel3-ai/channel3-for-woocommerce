<?php
/**
 * REST API Key Manager
 *
 * @package Channel3
 */

namespace Channel3\API;

defined( 'ABSPATH' ) || exit;

/**
 * Manages REST API keys for Channel3 integration.
 */
class Key_Manager {

	/**
	 * Generate REST API keys for Channel3.
	 *
	 * @return array|\WP_Error Array with key data or WP_Error on failure.
	 */
	public static function generate_keys() {
		global $wpdb;

		// Check if keys already exist and revoke them first.
		$existing_key_id = \get_option( 'channel3_api_key_id' );
		if ( $existing_key_id ) {
			self::revoke_keys();
		}

		// Get current user ID.
		$user_id = \get_current_user_id();
		if ( ! $user_id ) {
			return new \WP_Error( 'no_user', \__( 'No user logged in.', 'channel3-for-woocommerce' ) );
		}

		// Generate consumer key and secret.
		$consumer_key    = 'ck_' . \wc_rand_hash();
		$consumer_secret = 'cs_' . \wc_rand_hash();

		// Prepare data for insertion.
		$data = array(
			'user_id'         => $user_id,
			'description'     => \__( 'Channel3 - Product Catalog Sync', 'channel3-for-woocommerce' ),
			'permissions'     => 'read', // Read-only access.
			'consumer_key'    => \wc_api_hash( $consumer_key ),
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, -7 ),
		);

		// Insert into woocommerce_api_keys table.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce API keys table, no WP API available.
		$result = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			$data,
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( ! $result ) {
			return new \WP_Error( 'db_error', \__( 'Failed to create API keys.', 'channel3-for-woocommerce' ) );
		}

		$key_id = $wpdb->insert_id;

		// Log key creation.
		self::log( \sprintf( 'API key created with ID: %d', $key_id ) );

		return array(
			'key_id'          => $key_id,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'permissions'     => 'read',
		);
	}

	/**
	 * Revoke (delete) Channel3 API keys.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function revoke_keys() {
		global $wpdb;

		$key_id = \get_option( 'channel3_api_key_id' );

		if ( ! $key_id ) {
			return true; // No key to revoke.
		}

		// Delete the key from the database.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce API keys table, no WP API available.
		$result = $wpdb->delete(
			$wpdb->prefix . 'woocommerce_api_keys',
			array( 'key_id' => $key_id ),
			array( '%d' )
		);

		if ( false !== $result ) {
			// Clear stored key ID.
			\delete_option( 'channel3_api_key_id' );
			self::log( \sprintf( 'API key revoked with ID: %d', $key_id ) );
			return true;
		}

		self::log( \sprintf( 'Failed to revoke API key with ID: %d', $key_id ), 'error' );
		return false;
	}

	/**
	 * Check if API keys exist for Channel3.
	 *
	 * @return bool
	 */
	public static function keys_exist() {
		global $wpdb;

		$key_id = \get_option( 'channel3_api_key_id' );

		if ( ! $key_id ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce API keys table, no WP API available.
		$key = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT key_id FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d",
				$key_id
			)
		);

		return ! empty( $key );
	}

	/**
	 * Get API key info.
	 *
	 * @return array|null Key info or null if not found.
	 */
	public static function get_key_info() {
		global $wpdb;

		$key_id = \get_option( 'channel3_api_key_id' );

		if ( ! $key_id ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- WooCommerce API keys table, no WP API available.
		$key = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT key_id, user_id, description, permissions, truncated_key, last_access 
				FROM {$wpdb->prefix}woocommerce_api_keys 
				WHERE key_id = %d",
				$key_id
			),
			ARRAY_A
		);

		return $key ? $key : null;
	}

	/**
	 * Log a message.
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level.
	 */
	private static function log( $message, $level = 'info' ) {
		$settings = \get_option( 'woocommerce_channel3_settings', array() );
		if ( 'yes' !== ( $settings['debug'] ?? 'no' ) ) {
			return;
		}

		$logger = \wc_get_logger();
		$logger->log( $level, $message, array( 'source' => 'channel3' ) );
	}
}
