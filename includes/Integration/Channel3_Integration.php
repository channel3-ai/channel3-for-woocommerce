<?php
/**
 * Channel3 Integration Class
 *
 * @package Channel3
 */

namespace Channel3\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Channel3 Integration Class
 *
 * Extends WC_Integration to provide settings page under WooCommerce > Settings > Integrations.
 */
class Channel3_Integration extends \WC_Integration {

	/**
	 * Debug toggle.
	 *
	 * @var string
	 */
	public $debug;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'channel3';
		$this->method_title       = __( 'Channel3', 'channel3-for-woocommerce' );
		$this->method_description = __( 'Connect your WooCommerce store to Channel3 to sync your product catalog.', 'channel3-for-woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->debug = $this->get_option( 'debug' );

		// Actions.
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );

		// Add disconnect action handler.
		add_action( 'admin_init', array( $this, 'handle_disconnect' ) );
	}

	/**
	 * Initialize integration settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'connection_status' => array(
				'title'       => __( 'Connection Status', 'channel3-for-woocommerce' ),
				'type'        => 'title',
				'description' => $this->get_connection_status_html(),
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'channel3-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'channel3-for-woocommerce' ),
				'default'     => 'no',
				'description' => sprintf(
					/* translators: %s: Log file path */
					__( 'Log Channel3 events, such as API requests. Logs can be viewed in %s.', 'channel3-for-woocommerce' ),
					'<a href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '">' . __( 'WooCommerce > Status > Logs', 'channel3-for-woocommerce' ) . '</a>'
				),
			),
			'privacy_section' => array(
				'title'       => __( 'Privacy', 'channel3-for-woocommerce' ),
				'type'        => 'title',
				'description' => $this->get_privacy_description(),
			),
		);
	}

	/**
	 * Get connection status HTML.
	 *
	 * @return string
	 */
	private function get_connection_status_html() {
		$is_connected = channel3_is_connected();
		$connection_data = channel3_get_connection_data();

		if ( $is_connected ) {
			$connected_at = ! empty( $connection_data['connected_at'] ) 
				? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $connection_data['connected_at'] ) )
				: __( 'Unknown', 'channel3-for-woocommerce' );

			$html = '<div class="channel3-connection-status channel3-connected">';
			$html .= '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> ';
			$html .= '<strong>' . __( 'Connected to Channel3', 'channel3-for-woocommerce' ) . '</strong>';
			$html .= '<p>' . sprintf(
				/* translators: %s: Connection date */
				__( 'Connected on: %s', 'channel3-for-woocommerce' ),
				$connected_at
			) . '</p>';
			$html .= '<p><a href="' . esc_url( channel3_get_dashboard_url() ) . '" target="_blank" class="button">' . __( 'Go to Channel3 Dashboard', 'channel3-for-woocommerce' ) . '</a> ';
			$html .= '<a href="' . esc_url( $this->get_disconnect_url() ) . '" class="button" onclick="return confirm(\'' . esc_js( __( 'Are you sure you want to disconnect from Channel3? This will revoke API access.', 'channel3-for-woocommerce' ) ) . '\');">' . __( 'Disconnect', 'channel3-for-woocommerce' ) . '</a></p>';
			$html .= '</div>';
		} else {
			$html = '<div class="channel3-connection-status channel3-disconnected">';
			$html .= '<span class="dashicons dashicons-warning" style="color: #dba617;"></span> ';
			$html .= '<strong>' . __( 'Not Connected', 'channel3-for-woocommerce' ) . '</strong>';
			$html .= '<p>' . __( 'To connect your store, log in to your Channel3 account and click "Connect WooCommerce Store".', 'channel3-for-woocommerce' ) . '</p>';
			$html .= '<p><a href="' . esc_url( channel3_get_dashboard_url() ) . '" target="_blank" class="button button-primary">' . __( 'Go to Channel3', 'channel3-for-woocommerce' ) . '</a></p>';
			$html .= '</div>';
		}

		return $html;
	}

	/**
	 * Get privacy description.
	 *
	 * @return string
	 */
	private function get_privacy_description() {
		$html = '<p>' . __( 'When connected, Channel3 will have read-only access to your product catalog data, including:', 'channel3-for-woocommerce' ) . '</p>';
		$html .= '<ul style="list-style: disc; margin-left: 20px;">';
		$html .= '<li>' . __( 'Product names and descriptions', 'channel3-for-woocommerce' ) . '</li>';
		$html .= '<li>' . __( 'Product prices and inventory', 'channel3-for-woocommerce' ) . '</li>';
		$html .= '<li>' . __( 'Product images and categories', 'channel3-for-woocommerce' ) . '</li>';
		$html .= '</ul>';
		$html .= '<p>' . sprintf(
			/* translators: %s: Link to privacy policy */
			__( 'No personal customer data is shared. For more information, see the %s.', 'channel3-for-woocommerce' ),
			'<a href="https://trychannel3.com/privacy" target="_blank">' . __( 'Channel3 Privacy Policy', 'channel3-for-woocommerce' ) . '</a>'
		) . '</p>';

		return $html;
	}

	/**
	 * Get disconnect URL.
	 *
	 * @return string
	 */
	private function get_disconnect_url() {
		return wp_nonce_url(
			add_query_arg(
				array(
					'channel3_action' => 'disconnect',
				),
				admin_url( 'admin.php?page=wc-settings&tab=integration&section=channel3' )
			),
			'channel3_disconnect',
			'channel3_nonce'
		);
	}

	/**
	 * Handle disconnect action.
	 */
	public function handle_disconnect() {
		if ( ! isset( $_GET['channel3_action'] ) || 'disconnect' !== $_GET['channel3_action'] ) {
			return;
		}

		if ( ! isset( $_GET['channel3_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['channel3_nonce'] ) ), 'channel3_disconnect' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Notify Channel3 backend before disconnecting.
		$this->notify_backend_disconnect();

		// Revoke API keys.
		if ( class_exists( 'Channel3\API\Key_Manager' ) ) {
			\Channel3\API\Key_Manager::revoke_keys();
		}

		// Clear connection data.
		delete_option( 'channel3_connected' );
		delete_option( 'channel3_connected_at' );
		delete_option( 'channel3_api_key_id' );
		delete_option( 'channel3_store_id' );
		delete_option( 'channel3_merchant_id' );
		delete_option( 'channel3_webhook_secret' );

		// Log disconnection.
		$this->log( 'Store disconnected from Channel3.' );

		// Redirect back to settings.
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=integration&section=channel3&disconnected=1' ) );
		exit;
	}

	/**
	 * Notify Channel3 backend about disconnection.
	 *
	 * This webhook ensures the backend and frontend stay in sync when
	 * a merchant disconnects from the WooCommerce admin panel.
	 */
	private function notify_backend_disconnect() {
		$store_url = home_url();

		// Make webhook request to Channel3 backend.
		$webhook_url = add_query_arg(
			array( 'store_url' => rawurlencode( $store_url ) ),
			channel3_get_base_url() . '/v0/woocommerce/webhook/disconnect'
		);

		$response = wp_remote_post(
			$webhook_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log( 'Failed to notify backend of disconnect: ' . $response->get_error_message(), 'warning' );
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 === $response_code ) {
				$this->log( 'Successfully notified backend of disconnect' );
			} else {
				$this->log( 'Backend disconnect notification returned status: ' . $response_code, 'warning' );
			}
		}
	}

	/**
	 * Log a message using WC_Logger.
	 *
	 * @param string $message Message to log.
	 * @param string $level   Log level (debug, info, notice, warning, error, critical, alert, emergency).
	 */
	public function log( $message, $level = 'info' ) {
		if ( 'yes' !== $this->debug ) {
			return;
		}

		$logger = wc_get_logger();
		$logger->log( $level, $message, array( 'source' => 'channel3' ) );
	}
}
