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
		wp_localize_script( 'channel3-tracking', 'c3TrackingConfig', $config );
	}

	/**
	 * Get the pixel endpoint URL.
	 *
	 * @return string
	 */
	private static function get_pixel_endpoint() {
		$base_url = function_exists( 'channel3_get_base_url' ) ? channel3_get_base_url() : 'https://api.trychannel3.com';

		return $base_url . '/v0/woocommerce/pixel/page-view';
	}
}
