<?php
/**
 * Tracking Script Handler
 *
 * Enqueues the Channel3 tracking script on the storefront
 * to send page view events for analytics.
 *
 * @package Channel3
 */

namespace Channel3\Tracking;

defined( 'ABSPATH' ) || exit;

/**
 * Handles enqueueing the tracking script on the storefront.
 */
class Tracking_Script {

	/**
	 * Initialize tracking script hooks.
	 */
	public static function init() {
		// Only run on frontend, not admin.
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_tracking_script' ) );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'track_checkout' ), 10, 1 );
	}

	/**
	 * Enqueue the tracking script and pass configuration data.
	 *
	 * Only loads on product pages to track product page views.
	 */
	public static function enqueue_tracking_script() {
		// Only track product pages.
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		// Only load if connected to Channel3.
		if ( ! function_exists( 'channel3_is_connected' ) || ! channel3_is_connected() ) {
			return;
		}

		// Get merchant ID - required for tracking.
		$merchant_id = get_option( 'channel3_merchant_id', '' );
		if ( empty( $merchant_id ) ) {
			return;
		}

		// Get product data.
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		// Enqueue the tracking script.
		wp_enqueue_script(
			'channel3-tracking',
			CHANNEL3_PLUGIN_URL . 'assets/js/tracking.js',
			array(),
			CHANNEL3_VERSION,
			true // Load in footer for better performance.
		);

		// Build configuration data to pass to JavaScript.
		$config = array(
			'accountId'  => $merchant_id,
			'endpoint'   => self::get_pixel_endpoint(),
			'productId'  => (string) $product->get_id(),
			'productSku' => $product->get_sku(),
			'currency'   => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : null,
		);

		// Pass configuration to JavaScript.
		wp_localize_script( 'channel3-tracking', 'channel3TrackingConfig', $config );
	}

	/**
	 * Track checkout completion on the thank-you page.
	 *
	 * Fires on the woocommerce_thankyou hook. Injects an inline script that
	 * sends checkout data (order ID, total, line items) to Channel3 for
	 * attribution. Uses the same client ID from localStorage to link the
	 * checkout back to earlier product page views.
	 *
	 * @param int $order_id The WooCommerce order ID.
	 */
	public static function track_checkout( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		// Only fire if connected.
		if ( ! function_exists( 'channel3_is_connected' ) || ! channel3_is_connected() ) {
			return;
		}

		$merchant_id = get_option( 'channel3_merchant_id', '' );
		if ( empty( $merchant_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Prevent duplicate tracking on page refresh.
		if ( $order->get_meta( '_channel3_checkout_tracked' ) ) {
			return;
		}

		// Build line items array.
		$line_items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$line_items[] = array(
				'productId' => $product ? (string) $product->get_id() : null,
				'variantId' => ( $product && $product->is_type( 'variation' ) ) ? (string) $product->get_id() : null,
				'title'     => $item->get_name(),
				'quantity'  => $item->get_quantity(),
				'price'     => (string) ( $item->get_total() / max( $item->get_quantity(), 1 ) ),
			);
		}

		$checkout_data = array(
			'accountId'    => $merchant_id,
			'endpoint'     => self::get_checkout_endpoint(),
			'orderId'      => (string) $order->get_id(),
			'totalPrice'   => (string) $order->get_total(),
			'currencyCode' => $order->get_currency(),
			'lineItems'    => $line_items,
		);

		// Mark as tracked to prevent duplicates on refresh.
		$order->update_meta_data( '_channel3_checkout_tracked', '1' );
		$order->save();

		// Output inline script — we don't enqueue because the thank-you page
		// may not have our tracking.js loaded (it's only on product pages).
		$json = wp_json_encode( $checkout_data );
		?>
		<script type="text/javascript">
		(function() {
			'use strict';

			var data = <?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-encoded server data. ?>;

			// Reuse the same client ID from product page views for attribution.
			var clientId = null;
			try {
				clientId = localStorage.getItem( 'channel3_client_id' );
			} catch ( e ) {
				// localStorage not available.
			}

			var payload = {
				event: 'checkout_completed',
				timestamp: new Date().toISOString(),
				accountId: data.accountId,
				clientId: clientId,
				orderId: data.orderId,
				totalPrice: data.totalPrice,
				currencyCode: data.currencyCode,
				lineItems: data.lineItems
			};

			fetch( data.endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( payload ),
				keepalive: true
			}).catch( function() {} );
		})();
		</script>
		<?php
	}

	/**
	 * Get the page view pixel endpoint URL.
	 *
	 * @return string
	 */
	private static function get_pixel_endpoint() {
		return self::get_base_api_url() . '/v0/woocommerce/pixel/page-view';
	}

	/**
	 * Get the checkout pixel endpoint URL.
	 *
	 * @return string
	 */
	private static function get_checkout_endpoint() {
		return self::get_base_api_url() . '/v0/woocommerce/pixel/checkout';
	}

	/**
	 * Get the Channel3 API base URL.
	 *
	 * @return string
	 */
	private static function get_base_api_url() {
		return function_exists( 'channel3_get_base_url' ) ? channel3_get_base_url() : 'https://api.trychannel3.com';
	}
}
