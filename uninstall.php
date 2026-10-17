<?php
/**
 * Uninstall: remove the 404 log table, the IndexNow and HTML sitemap state
 * and the scheduled events, on every site of a network.
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

$lw_seo_uninstall_site = static function (): void {
	\LightweightPlugins\SEO\NotFound\LogTable::drop();

	$options = [
		'lw_seo_indexnow_key',
		'lw_seo_indexnow_queue',
		'lw_seo_indexnow_log',
		'lw_seo_html_sitemap_version',
	];

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	wp_clear_scheduled_hook( 'lw_seo_404_prune' );
	wp_clear_scheduled_hook( 'lw_seo_indexnow_flush' );
};

if ( is_multisite() ) {
	foreach ( get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	) as $lw_seo_site_id ) {
		switch_to_blog( (int) $lw_seo_site_id );
		$lw_seo_uninstall_site();
		restore_current_blog();
	}
} else {
	$lw_seo_uninstall_site();
}
