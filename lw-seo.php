<?php
/**
 * Plugin Name:       Lightweight SEO
 * Plugin URI:        https://github.com/lwplugins/lw-seo
 * Description:       Lightweight SEO plugin for WordPress - minimal footprint, maximum impact.
 * Version:           1.1.9
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            LW Plugins
 * Author URI:        https://lwplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lw-seo
 * Domain Path:       /languages
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'LW_SEO_VERSION', '1.1.9' );
define( 'LW_SEO_FILE', __FILE__ );
define( 'LW_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'LW_SEO_URL', plugin_dir_url( __FILE__ ) );

// Autoloader (required for PSR-4 class loading).
if ( file_exists( LW_SEO_PATH . 'vendor/autoload.php' ) ) {
	require_once LW_SEO_PATH . 'vendor/autoload.php';
}

/**
 * Returns the main plugin instance.
 *
 * @return Plugin
 */
function lw_seo(): Plugin {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Plugin();
	}

	return $instance;
}

// Initialize the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\lw_seo' );
