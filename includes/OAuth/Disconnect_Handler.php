<?php
/**
 * OAuth Disconnect Handler
 *
 * @package Channel3
 */

namespace Channel3\OAuth;

use Channel3\API\Key_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles disconnect webhook requests from Channel3.
 *
 * When a merchant disconnects from the Channel3 dashboard, this handler
 * receives the webhook and performs the local disconnect (revoke keys, clear data).
 */
class Disconnect_Handler {

	/**
	 * Handle the disconnect webhook request.
	 */
	public function handle_request() {
		// Verify this is a valid request from Channel3.
		if ( ! $this->validate_request() ) {
			$this->send_error( \__( 'Invalid request.', 'channel3-for-woocommerce' ), 400 );
			return;
		}

		// Perform the disconnect (even if already disconnected locally, to ensure cleanup).
		$was_connected = channel3_is_connected();
		$this->perform_disconnect();

		// Send success response.
		\wp_send_json_success(
			array(
				'message' => \__( 'Store disconnected successfully.', 'channel3-for-woocommerce' ),
				'was_connected' => $was_connected,
			),
			200
		);
	}

	/**
	 * Validate the incoming request.
	 *
	 * @return bool
	 */
	private function validate_request() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Webhook from external service, authenticated via webhook_secret.
		// Verify the request is coming from Channel3.
		// Check for required parameters.
		if ( empty( $_GET['store_url'] ) || empty( $_GET['webhook_secret'] ) ) {
			$this->log( 'Disconnect webhook: missing required parameters', 'warning' );
			return false;
		}

		// Verify webhook secret to ensure this is an authentic request from Channel3.
		$stored_secret = \get_option( 'channel3_webhook_secret' );
		$passed_secret = \sanitize_text_field( \wp_unslash( $_GET['webhook_secret'] ) );

		if ( ! $stored_secret || ! \hash_equals( $stored_secret, $passed_secret ) ) {
			$this->log( 'Disconnect webhook: webhook secret verification failed', 'warning' );
			return false;
		}

		$requested_store_url = \esc_url_raw( \wp_unslash( $_GET['store_url'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$actual_store_url    = \home_url();

		// Normalize URLs for comparison (remove trailing slashes, protocol).
		$requested_store_url = \untrailingslashit( \str_replace( array( 'https://', 'http://' ), '', $requested_store_url ) );
		$actual_store_url    = \untrailingslashit( \str_replace( array( 'https://', 'http://' ), '', $actual_store_url ) );

		$this->log( "Disconnect webhook: comparing URLs - requested: {$requested_store_url}, actual: {$actual_store_url}", 'info' );

		if ( $requested_store_url !== $actual_store_url ) {
			$this->log( 'Disconnect webhook: store URL mismatch', 'warning' );
			return false;
		}

		return true;
	}

	/**
	 * Perform the disconnect operation.
	 */
	private function perform_disconnect() {
		// Revoke API keys.
		if ( class_exists( 'Channel3\API\Key_Manager' ) ) {
			Key_Manager::revoke_keys();
		}

		// Clear connection data.
		\delete_option( 'channel3_connected' );
		\delete_option( 'channel3_connected_at' );
		\delete_option( 'channel3_api_key_id' );
		\delete_option( 'channel3_store_id' );
		\delete_option( 'channel3_webhook_secret' );

		// Log disconnection.
		$this->log( 'Store disconnected via Channel3 webhook.' );
	}

	/**
	 * Send an error response.
	 *
	 * @param string $message Error message.
	 * @param int    $code    HTTP status code.
	 */
	private function send_error( $message, $code = 400 ) {
		$this->log( 'Disconnect webhook error: ' . $message, 'error' );

		\wp_send_json_error(
			array(
				'message' => $message,
			),
			$code
		);
	}

	/**
	 * Log a message.
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level.
	 */
	private function log( $message, $level = 'info' ) {
		$settings = \get_option( 'woocommerce_channel3_settings', array() );
		if ( 'yes' !== ( $settings['debug'] ?? 'no' ) ) {
			return;
		}

		/** @disregard P1010 - WooCommerce function, available at runtime */
		$logger = \wc_get_logger();
		$logger->log( $level, $message, array( 'source' => 'channel3' ) );
	}
}
