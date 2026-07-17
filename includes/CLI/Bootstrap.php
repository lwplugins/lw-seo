<?php
/**
 * WP-CLI command registration.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

/**
 * Registers all `wp lw-seo …` commands. Only loaded under WP-CLI.
 */
final class Bootstrap {

	/**
	 * Register command groups.
	 *
	 * @return void
	 */
	public static function register(): void {
		\WP_CLI::add_command( 'lw-seo migrate', MigrateCommand::class );
		\WP_CLI::add_command( 'lw-seo redirect', RedirectCommand::class );
		\WP_CLI::add_command( 'lw-seo sitemap', SitemapCommand::class );
		\WP_CLI::add_command( 'lw-seo option', OptionCommand::class );
	}
}
