<?php
/**
 * Plugin Name: Channel3 for WooCommerce
 * Plugin URI: https://trychannel3.com/integrations/woocommerce
 * Description: Sync your WooCommerce product catalog to Channel3.
 * Version: 1.0.0
 * Author: Channel3
 * Author URI: https://trychannel3.com
 * Developer: Channel3
 * Developer URI: https://trychannel3.com
 * Text Domain: channel3-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Channel3
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CHANNEL3_PLUGIN_FILE' ) ) {
	define( 'CHANNEL3_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'CHANNEL3_PLUGIN_PATH' ) ) {
	define( 'CHANNEL3_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CHANNEL3_PLUGIN_URL' ) ) {
	define( 'CHANNEL3_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'CHANNEL3_VERSION' ) ) {
	define( 'CHANNEL3_VERSION', '1.0.0' );
}

// Autoload plugin classes (avoid requiring vendor/ at runtime so WP.org ZIP installs work).
require_once plugin_dir_path( __FILE__ ) . '/includes/autoload.php';

use Channel3\Admin\Setup;
use Channel3\Admin\Welcome_Note;

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 1.0.0
 */
function channel3_missing_wc_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Channel3 for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'channel3-for-woocommerce' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

/**
 * Activation hook.
 *
 * @since 1.0.0
 */
function channel3_activate() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'channel3_missing_wc_notice' );
		return;
	}

	// Add welcome note to inbox.
	if ( class_exists( 'Channel3\Admin\Welcome_Note' ) ) {
		Welcome_Note::possibly_add_note();
	}
}
register_activation_hook( __FILE__, 'channel3_activate' );

/**
 * Deactivation hook.
 *
 * @since 1.0.0
 */
function channel3_deactivate() {
	// Remove welcome note from inbox.
	if ( class_exists( 'Channel3\Admin\Welcome_Note' ) ) {
		Welcome_Note::possibly_delete_note();
	}

	// Unregister setup task.
	remove_filter( 'woocommerce_get_registered_extended_tasks', 'channel3_register_connect_task', 10 );
}
register_deactivation_hook( __FILE__, 'channel3_deactivate' );

if ( ! class_exists( 'Channel3' ) ) :
	/**
	 * The Channel3 class.
	 */
	class Channel3 {
		/**
		 * This class instance.
		 *
		 * @var \Channel3 single instance of this class.
		 */
		private static $instance;

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = CHANNEL3_VERSION;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->init_hooks();

			if ( is_admin() ) {
				new Setup();
			}
		}

		/**
		 * Initialize hooks.
		 */
		private function init_hooks() {
			// Register integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

		// Register WC API callback handler.
		add_action( 'woocommerce_api_channel3-connect', array( $this, 'handle_oauth_callback' ) );

		// Register WC API disconnect webhook handler.
		add_action( 'woocommerce_api_channel3-disconnect', array( $this, 'handle_disconnect_webhook' ) );

			// Register setup task.
			add_action( 'init', array( $this, 'register_setup_task' ) );

			// Add privacy policy content.
			add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		}

		/**
		 * Add Channel3 integration to WooCommerce.
		 *
		 * @param array $integrations Array of integrations.
		 * @return array
		 */
		public function add_integration( $integrations ) {
			if ( class_exists( 'Channel3\Integration\Channel3_Integration' ) ) {
				$integrations[] = 'Channel3\Integration\Channel3_Integration';
			}
			return $integrations;
		}

	/**
	 * Handle OAuth callback from Channel3.
	 */
	public function handle_oauth_callback() {
		if ( class_exists( 'Channel3\OAuth\Connection_Handler' ) ) {
			$handler = new \Channel3\OAuth\Connection_Handler();
			$handler->handle_request();
		}
	}

	/**
	 * Handle disconnect webhook from Channel3.
	 *
	 * Called when a merchant disconnects from the Channel3 dashboard.
	 */
	public function handle_disconnect_webhook() {
		if ( class_exists( 'Channel3\OAuth\Disconnect_Handler' ) ) {
			$handler = new \Channel3\OAuth\Disconnect_Handler();
			$handler->handle_request();
		}
	}

		/**
		 * Register setup task.
		 */
		public function register_setup_task() {
			if ( ! class_exists( 'Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists' ) ) {
				return;
			}

			if ( class_exists( 'Channel3\Admin\Connect_Task' ) ) {
				$task_lists = \Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists::instance();
				$extended_list = $task_lists::get_list( 'extended' );

				if ( $extended_list ) {
					$task_lists::add_task(
						'extended',
						new \Channel3\Admin\Connect_Task( $extended_list )
					);
				}
			}
		}

		/**
		 * Add privacy policy content.
		 */
		public function add_privacy_policy_content() {
			if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
				return;
			}

			$content = sprintf(
				/* translators: %s: Link to Channel3 privacy policy */
				__( 'When you connect your store to Channel3, product catalog data (including product names, descriptions, prices, and images) is shared with Channel3 to enable product synchronization. No personal customer data is shared. For more information, please see the %s.', 'channel3-for-woocommerce' ),
				'<a href="https://trychannel3.com/privacy" target="_blank">' . __( 'Channel3 Privacy Policy', 'channel3-for-woocommerce' ) . '</a>'
			);

			wp_add_privacy_policy_content(
				__( 'Channel3 for WooCommerce', 'channel3-for-woocommerce' ),
				wp_kses_post( wpautop( $content, false ) )
			);
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'channel3-for-woocommerce' ), $this->version );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'channel3-for-woocommerce' ), $this->version );
		}

		/**
		 * Gets the main instance.
		 *
		 * Ensures only one instance can be loaded.
		 *
		 * @return \Channel3
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}
endif;

add_action( 'plugins_loaded', 'channel3_init', 10 );

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function channel3_init() {
	load_plugin_textdomain( 'channel3-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'channel3_missing_wc_notice' );
		return;
	}

	Channel3::instance();
}

/**
 * Helper function to check if store is connected to Channel3.
 *
 * @return bool
 */
function channel3_is_connected() {
	return 'yes' === get_option( 'channel3_connected', 'no' );
}

/**
 * Helper function to get Channel3 connection data.
 *
 * @return array
 */
function channel3_get_connection_data() {
	return array(
		'connected'       => channel3_is_connected(),
		'connected_at'    => get_option( 'channel3_connected_at', '' ),
		'api_key_id'      => get_option( 'channel3_api_key_id', '' ),
	);
}

/**
 * Get the Channel3 base URL.
 *
 * Auto-detects local development environment or uses CHANNEL3_BASE_URL constant if defined.
 *
 * For local development:
 * - If the site is running on localhost/127.0.0.1, uses https://channel3.ngrok.dev
 * - You can override by defining CHANNEL3_BASE_URL constant in wp-config.php
 *
 * @return string
 */
function channel3_get_base_url() {
	// Allow override via constant (highest priority).
	if ( defined( 'CHANNEL3_BASE_URL' ) ) {
		return CHANNEL3_BASE_URL;
	}

	// Auto-detect local development environment.
	$site_url = home_url();
	if ( strpos( $site_url, 'localhost' ) !== false || strpos( $site_url, '127.0.0.1' ) !== false ) {
		// Local development - use ngrok backend.
		return 'https://channel3.ngrok.dev';
	}

	// Production environment.
	return 'https://trychannel3.com';
}

/**
 * Get the Channel3 dashboard URL for integrations.
 *
 * @return string
 */
function channel3_get_dashboard_url() {
	/**
	 * Filter the Channel3 dashboard URL used for "Go to Channel3" links.
	 *
	 * Useful if your deployment needs a different path/tenant routing.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Default dashboard URL.
	 */
	return apply_filters( 'channel3_dashboard_url', channel3_get_base_url() . '/brands/xxxx/dashboard/integrations' );
}
