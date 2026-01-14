<?php
/**
 * Channel3 plugin autoloader.
 *
 * Lightweight PSR-4 style autoloader for the Channel3\ namespace.
 * We avoid requiring Composer vendor files at runtime so the plugin can be
 * distributed cleanly via WordPress.org.
 *
 * @package Channel3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Autoload Channel3 classes from the includes/ directory.
 *
 * @param string $class Fully-qualified class name.
 * @return void
 */
function channel3_autoload( $class ) {
	$prefix = 'Channel3\\';

	if ( 0 !== strpos( $class, $prefix ) ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$relative_path  = str_replace( '\\', '/', $relative_class ) . '.php';

	$file = CHANNEL3_PLUGIN_PATH . 'includes/' . $relative_path;

	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

spl_autoload_register( 'channel3_autoload' );

