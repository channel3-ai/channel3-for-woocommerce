<?php
/**
 * OAuth Connection Handler
 *
 * @package Channel3
 */

namespace Channel3\OAuth;

use Channel3\API\Key_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles OAuth connection requests from Channel3.
 */
class Connection_Handler {

	/**
	 * Handle the OAuth callback request.
	 */
	public function handle_request() {
		// Verify this is a valid request.
		if ( ! $this->validate_request() ) {
			$this->send_error( \__( 'Invalid request.', 'channel3-for-woocommerce' ), 400 );
			return;
		}

		// Check if user is logged in and has permission.
		if ( ! \is_user_logged_in() ) {
			// Redirect to login, then back here.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified later in process_authorization.
			$safe_params = array_map( 'sanitize_text_field', \wp_unslash( $_GET ) );
			$redirect_url = \add_query_arg( $safe_params, \home_url( '/wc-api/channel3-connect' ) );
			\wp_safe_redirect( \wp_login_url( $redirect_url ) );
			exit;
		}

		if ( ! \current_user_can( 'manage_woocommerce' ) ) {
			$this->send_error( \__( 'You do not have permission to connect this store.', 'channel3-for-woocommerce' ), 403 );
			return;
		}

		// Get request parameters.
		$callback_url = isset( $_GET['callback_url'] ) ? \esc_url_raw( \wp_unslash( $_GET['callback_url'] ) ) : '';
		$store_id     = isset( $_GET['store_id'] ) ? \sanitize_text_field( \wp_unslash( $_GET['store_id'] ) ) : '';
		$merchant_id  = isset( $_GET['merchant_id'] ) ? \sanitize_text_field( \wp_unslash( $_GET['merchant_id'] ) ) : '';
		$signature    = isset( $_GET['signature'] ) ? \sanitize_text_field( \wp_unslash( $_GET['signature'] ) ) : '';
		$timestamp    = isset( $_GET['timestamp'] ) ? \absint( \wp_unslash( $_GET['timestamp'] ) ) : 0;

		// Check if this is a confirmation step.
		if ( isset( $_GET['confirm'] ) && 'yes' === $_GET['confirm'] ) {
			$this->process_authorization( $callback_url, $store_id, $merchant_id );
			return;
		}

		// Show authorization prompt.
		$this->show_authorization_prompt( $callback_url, $store_id, $merchant_id, $timestamp );
	}

	/**
	 * Validate the incoming request.
	 *
	 * @return bool
	 */
	private function validate_request() {
		// Check required parameters.
		if ( empty( $_GET['callback_url'] ) ) {
			return false;
		}

		// Validate callback URL is from Channel3.
		$callback_url = \esc_url_raw( \wp_unslash( $_GET['callback_url'] ) );
		$parsed_url   = \wp_parse_url( $callback_url );

		if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
			return false;
		}

		// Allow Channel3 production.
		$allowed_hosts = array(
			'api.trychannel3.com',
			'trychannel3.com',
			'www.trychannel3.com',
		);

		// Allow local development hosts only if in debug mode.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$allowed_hosts = array_merge(
				$allowed_hosts,
				array(
					'localhost',
					'127.0.0.1',
					'channel3.ngrok.dev',
					'channel3-2.ngrok.dev',
					'channel3-evan.ngrok.dev',
				)
			);
		}

		// Allow filtering for development/staging environments.
		$allowed_hosts = \apply_filters( 'channel3_allowed_callback_hosts', $allowed_hosts );

		if ( ! in_array( $parsed_url['host'], $allowed_hosts, true ) ) {
			$this->log( 'Invalid callback host: ' . $parsed_url['host'], 'warning' );
			return false;
		}

		// Validate timestamp if provided (must be within 5 minutes).
		if ( isset( $_GET['timestamp'] ) ) {
			$timestamp = \absint( \wp_unslash( $_GET['timestamp'] ) );
			if ( \abs( time() - $timestamp ) > 300 ) {
				$this->log( 'Request timestamp expired.', 'warning' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Show authorization prompt to the user.
	 *
	 * @param string $callback_url Callback URL.
	 * @param string $store_id     Store ID from Channel3.
	 * @param string $merchant_id  Merchant ID from Channel3.
	 * @param int    $timestamp    Request timestamp.
	 */
	private function show_authorization_prompt( $callback_url, $store_id, $merchant_id, $timestamp ) {
		// Build confirmation URL using query parameter format for WooCommerce API.
		$confirm_url = \wp_nonce_url(
			\add_query_arg(
				array(
					'wc-api'       => 'channel3-connect',
					'callback_url' => \rawurlencode( $callback_url ),
					'store_id'     => $store_id,
					'merchant_id'  => $merchant_id,
					'timestamp'    => $timestamp,
					'confirm'      => 'yes',
				),
				\home_url( '/' )
			),
			'channel3_connect',
			'_wpnonce'
		);

		// Build cancel URL.
		$cancel_url = \add_query_arg(
			array(
				'error' => 'cancelled',
			),
			$callback_url
		);

		// Output authorization page.
		\wp_enqueue_style( 'wp-admin' );

		?>
		<!DOCTYPE html>
		<html <?php \language_attributes(); ?>>
		<head>
			<meta charset="<?php \bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php \esc_html_e( 'Authorize Channel3', 'channel3-for-woocommerce' ); ?></title>
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background: #f0f0f1;
					margin: 0;
					padding: 20px;
				}
				.channel3-auth-container {
					max-width: 500px;
					margin: 50px auto;
					background: #fff;
					border: 1px solid #c3c4c7;
					box-shadow: 0 1px 3px rgba(0,0,0,.04);
					padding: 30px;
				}
				.channel3-auth-header {
					text-align: center;
					margin-bottom: 30px;
				}
				.channel3-auth-header h1 {
					font-size: 24px;
					margin: 0 0 10px;
					color: #1d2327;
				}
				.channel3-auth-content {
					margin-bottom: 30px;
				}
				.channel3-auth-content p {
					color: #50575e;
					line-height: 1.6;
				}
				.channel3-permissions {
					background: #f6f7f7;
					border: 1px solid #c3c4c7;
					padding: 15px;
					margin: 20px 0;
				}
				.channel3-permissions h3 {
					margin: 0 0 10px;
					font-size: 14px;
				}
				.channel3-permissions ul {
					margin: 0;
					padding-left: 20px;
				}
				.channel3-permissions li {
					margin: 5px 0;
					color: #50575e;
				}
				.channel3-auth-actions {
					display: flex;
					gap: 10px;
					justify-content: center;
				}
				.channel3-auth-actions a {
					padding: 10px 20px;
					text-decoration: none;
					border-radius: 3px;
					font-size: 14px;
				}
				.button-primary {
					background: #2271b1;
					border: 1px solid #2271b1;
					color: #fff;
				}
				.button-primary:hover {
					background: #135e96;
					border-color: #135e96;
					color: #fff;
				}
				.button-secondary {
					background: #f6f7f7;
					border: 1px solid #c3c4c7;
					color: #50575e;
				}
				.button-secondary:hover {
					background: #f0f0f1;
					border-color: #8c8f94;
					color: #1d2327;
				}
				.channel3-store-info {
					text-align: center;
					color: #8c8f94;
					font-size: 13px;
					margin-top: 20px;
				}
			</style>
		</head>
		<body>
			<div class="channel3-auth-container">
				<div class="channel3-auth-header">
					<h1><?php \esc_html_e( 'Connect to Channel3', 'channel3-for-woocommerce' ); ?></h1>
					<p><?php \esc_html_e( 'Channel3 is requesting access to your WooCommerce store.', 'channel3-for-woocommerce' ); ?></p>
				</div>

				<div class="channel3-auth-content">
					<div class="channel3-permissions">
						<h3><?php \esc_html_e( 'Channel3 will be able to:', 'channel3-for-woocommerce' ); ?></h3>
						<ul>
							<li><?php \esc_html_e( 'Read your product catalog (names, descriptions, prices, images)', 'channel3-for-woocommerce' ); ?></li>
							<li><?php \esc_html_e( 'Read product categories and tags', 'channel3-for-woocommerce' ); ?></li>
							<li><?php \esc_html_e( 'Read product inventory levels', 'channel3-for-woocommerce' ); ?></li>
						</ul>
					</div>

					<p><strong><?php \esc_html_e( 'Channel3 will NOT be able to:', 'channel3-for-woocommerce' ); ?></strong></p>
					<ul>
						<li><?php \esc_html_e( 'Access customer personal data', 'channel3-for-woocommerce' ); ?></li>
						<li><?php \esc_html_e( 'Modify your products or orders', 'channel3-for-woocommerce' ); ?></li>
						<li><?php \esc_html_e( 'Access payment information', 'channel3-for-woocommerce' ); ?></li>
					</ul>
				</div>

				<div class="channel3-auth-actions">
					<a href="<?php echo \esc_url( $confirm_url ); ?>" class="button-primary">
						<?php \esc_html_e( 'Authorize', 'channel3-for-woocommerce' ); ?>
					</a>
					<a href="<?php echo \esc_url( $cancel_url ); ?>" class="button-secondary">
						<?php \esc_html_e( 'Cancel', 'channel3-for-woocommerce' ); ?>
					</a>
				</div>

				<div class="channel3-store-info">
					<?php
					\printf(
						/* translators: %s: Store name */
						\esc_html__( 'Store: %s', 'channel3-for-woocommerce' ),
						\esc_html( \get_bloginfo( 'name' ) )
					);
					?>
				</div>
			</div>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Process the authorization after user confirms.
	 *
	 * @param string $callback_url Callback URL.
	 * @param string $store_id     Store ID from Channel3.
	 * @param string $merchant_id  Merchant ID from Channel3.
	 */
	private function process_authorization( $callback_url, $store_id, $merchant_id ) {
		// Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_GET['_wpnonce'] ) ), 'channel3_connect' ) ) {
			$this->send_error( \__( 'Security check failed.', 'channel3-for-woocommerce' ), 403 );
			return;
		}

		// Generate API keys.
		$api_keys = Key_Manager::generate_keys();

		if ( \is_wp_error( $api_keys ) ) {
			$this->log( 'Failed to generate API keys: ' . $api_keys->get_error_message(), 'error' );
			$error_url = \add_query_arg(
				array(
					'error' => 'key_generation_failed',
				),
				$callback_url
			);
			\wp_safe_redirect( $error_url );
			exit;
		}

		// Generate a unique webhook secret for securing disconnects.
		$webhook_secret = \wp_generate_password( 32, false );

		// Store connection data.
		\update_option( 'channel3_connected', 'yes' );
		\update_option( 'channel3_connected_at', \current_time( 'mysql' ) );
		\update_option( 'channel3_api_key_id', $api_keys['key_id'] );
		\update_option( 'channel3_store_id', $store_id );
		\update_option( 'channel3_merchant_id', $merchant_id );
		\update_option( 'channel3_webhook_secret', $webhook_secret );

		$this->log( 'Store successfully connected to Channel3.' );

		// Get store currency.
		$currency = function_exists( 'get_woocommerce_currency' ) ? \get_woocommerce_currency() : '';

		// Build success redirect URL with credentials and webhook secret.
		$success_url = \add_query_arg(
			array(
				'success'         => '1',
				'consumer_key'    => $api_keys['consumer_key'],
				'consumer_secret' => $api_keys['consumer_secret'],
				'webhook_secret'  => $webhook_secret,
				'store_url'       => \rawurlencode( \home_url() ),
				'store_name'      => \rawurlencode( \get_bloginfo( 'name' ) ),
				'store_id'        => $store_id,
				'merchant_id'     => $merchant_id,
				'currency'        => $currency,
			),
			$callback_url
		);

		\wp_redirect( $success_url );
		exit;
	}

	/**
	 * Send an error response.
	 *
	 * @param string $message Error message.
	 * @param int    $code    HTTP status code.
	 */
	private function send_error( $message, $code = 400 ) {
		$this->log( 'OAuth error: ' . $message, 'error' );

		\status_header( $code );
		\wp_die(
			\esc_html( $message ),
			\esc_html__( 'Authorization Error', 'channel3-for-woocommerce' ),
			array( 'response' => $code )
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
