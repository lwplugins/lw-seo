<?php
/**
 * `wp lw-seo sitemap` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\Sitemap;

/**
 * Inspect and rebuild the LW SEO sitemap.
 */
final class SitemapCommand {

	/**
	 * Show sitemap status and index URL.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function info( array $args, array $assoc_args ): void {
		if ( ! Options::get( 'sitemap_enabled' ) ) {
			\WP_CLI::warning( 'Sitemap is disabled in LW SEO settings.' );
			return;
		}

		\WP_CLI::line( 'Sitemap index: ' . Sitemap::get_index_url() );
		\WP_CLI::success( 'Sitemap is enabled.' );
	}

	/**
	 * Flush rewrite rules so sitemap URLs resolve.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function flush( array $args, array $assoc_args ): void {
		Sitemap::activate();
		\WP_CLI::success( 'Sitemap rewrite rules flushed.' );
	}
}
