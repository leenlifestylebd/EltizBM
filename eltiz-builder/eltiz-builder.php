<?php
/**
 * Plugin Name: Eltiz Builder
 * Plugin URI:  https://eltizbuilder.com
 * Description: Advanced drag-and-drop visual website builder for WordPress — modular, performance-first, developer-friendly.
 * Version:     1.0.0
 * Author:      Eltiz Builder Team
 * Author URI:  https://eltizbuilder.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: eltiz-builder
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP:  8.2
 *
 * @package EltizBuilder
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'ELTIZ_VERSION', '1.0.0' );
define( 'ELTIZ_FILE', __FILE__ );
define( 'ELTIZ_PATH', plugin_dir_path( __FILE__ ) );
define( 'ELTIZ_URL', plugin_dir_url( __FILE__ ) );
define( 'ELTIZ_ASSETS_URL', ELTIZ_URL . 'assets/' );
define( 'ELTIZ_MODULES_PATH', ELTIZ_PATH . 'modules/' );
define( 'ELTIZ_WIDGETS_PATH', ELTIZ_PATH . 'widgets/' );
define( 'ELTIZ_TEMPLATES_PATH', ELTIZ_PATH . 'templates/' );
define( 'ELTIZ_MIN_PHP', '8.2' );
define( 'ELTIZ_MIN_WP', '6.3' );

// Autoloader.
require_once ELTIZ_PATH . 'core/Autoloader.php';

\EltizBuilder\Core\Autoloader::register();

/**
 * Returns the main plugin instance.
 */
function eltiz_builder(): \EltizBuilder\Core\Plugin {
	return \EltizBuilder\Core\Plugin::instance();
}

// Boot.
add_action( 'plugins_loaded', function () {
	// System requirements check.
	if ( version_compare( PHP_VERSION, ELTIZ_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' .
				sprintf(
					/* translators: 1: required version 2: current version */
					esc_html__( 'Eltiz Builder requires PHP %1$s or higher. Your server is running PHP %2$s.', 'eltiz-builder' ),
					ELTIZ_MIN_PHP,
					PHP_VERSION
				) .
				'</p></div>';
		} );
		return;
	}

	eltiz_builder();
}, 0 );

// Activation / deactivation / uninstall hooks.
register_activation_hook( __FILE__, [ '\EltizBuilder\Core\Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\EltizBuilder\Core\Installer', 'deactivate' ] );
register_uninstall_hook( __FILE__, [ '\EltizBuilder\Core\Installer', 'uninstall' ] );
