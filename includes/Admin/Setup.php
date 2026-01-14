<?php
/**
 * Admin Setup Class
 *
 * @package Channel3
 */

namespace Channel3\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Channel3 Admin Setup Class
 */
class Setup {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Load all necessary dependencies.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		if ( ! method_exists( 'Automattic\WooCommerce\Admin\PageController', 'is_admin_or_embed_page' ) ||
		! \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page()
		) {
			return;
		}

		$script_path       = '/build/index.js';
		$script_asset_path = dirname( CHANNEL3_PLUGIN_FILE ) . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => filemtime( dirname( CHANNEL3_PLUGIN_FILE ) . $script_path ),
		);
		$script_url        = plugins_url( $script_path, CHANNEL3_PLUGIN_FILE );

		wp_register_script(
			'channel3-admin',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_register_style(
			'channel3-admin',
			plugins_url( '/build/index.css', CHANNEL3_PLUGIN_FILE ),
			// Add any dependencies styles may have, such as wp-components.
			array( 'wp-components' ),
			filemtime( dirname( CHANNEL3_PLUGIN_FILE ) . '/build/index.css' )
		);

		// Pass connection data to JavaScript.
		wp_localize_script(
			'channel3-admin',
			'channel3Data',
			array(
				'isConnected'    => channel3_is_connected(),
				'connectionData' => channel3_get_connection_data(),
				'settingsUrl'    => admin_url( 'admin.php?page=wc-settings&tab=integration&section=channel3' ),
				'channel3Url'    => channel3_get_dashboard_url(),
				'nonce'          => wp_create_nonce( 'channel3_admin' ),
			)
		);

		wp_enqueue_script( 'channel3-admin' );
		wp_enqueue_style( 'channel3-admin' );
	}

	/**
	 * Register page in wc-admin.
	 *
	 * @since 1.0.0
	 */
	public function register_page() {
		if ( ! function_exists( 'wc_admin_register_page' ) ) {
			return;
		}

		wc_admin_register_page(
			array(
				'id'     => 'channel3-settings',
				'title'  => __( 'Channel3', 'channel3-for-woocommerce' ),
				'parent' => 'woocommerce',
				'path'   => '/channel3',
			)
		);
	}
}
