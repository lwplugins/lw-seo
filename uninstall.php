<?php
/**
 * Uninstall: remove the 404 log table and its scheduled events.
 *
 * Settings (lw_seo_options) and redirects are kept on purpose.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// The plugin is not loaded during uninstall, so the autoloader is not available.
require_once __DIR__ . '/includes/NotFound/LogTable.php';

\LightweightPlugins\SEO\NotFound\LogTable::drop();

wp_clear_scheduled_hook( 'lw_seo_404_prune' );
wp_clear_scheduled_hook( 'lw_seo_indexnow_flush' );
