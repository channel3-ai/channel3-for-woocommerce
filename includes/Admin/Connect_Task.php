<?php
/**
 * Connect Task for WooCommerce Onboarding
 *
 * @package Channel3
 */

namespace Channel3\Admin;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Channel3 Connect Task Class
 *
 * Adds a setup task to the "Things to do next" list.
 */
class Connect_Task extends Task {

	/**
	 * Get the task ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'channel3-connect';
	}

	/**
	 * Get the task title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Connect to Channel3', 'channel3-for-woocommerce' );
	}

	/**
	 * Get the task content/description.
	 *
	 * @return string
	 */
	public function get_content() {
		return __( 'Sync your products to Channel3 to reach more customers.', 'channel3-for-woocommerce' );
	}

	/**
	 * Get the estimated time to complete.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'channel3-for-woocommerce' );
	}

	/**
	 * Check if the task is complete.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return channel3_is_connected();
	}

	/**
	 * Get the task action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=integration&section=channel3' );
	}

	/**
	 * Check if the task can be viewed.
	 *
	 * @return bool
	 */
	public function can_view() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get additional data for the task.
	 *
	 * @return array
	 */
	public function get_additional_data() {
		return array(
			'channel3Url' => channel3_get_dashboard_url(),
		);
	}
}
