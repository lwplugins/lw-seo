<?php
/**
 * Robots.txt class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO;

use LightweightPlugins\SEO\Crawlers\Policy;
use LightweightPlugins\SEO\Crawlers\Registry;
use LightweightPlugins\SEO\Robots\Builder;
use LightweightPlugins\SEO\Sitemap\Sitemap;

/**
 * Adds sitemap, AI crawler rules and content signals to WordPress's virtual
 * robots.txt through the core `robots_txt` filter.
 */
final class RobotsTxt {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! Options::get( 'robots_txt_enabled' ) ) {
			return;
		}

		add_filter( 'robots_txt', [ $this, 'filter' ], 10, 2 );
	}

	/**
	 * Filter WordPress's robots.txt output.
	 *
	 * @param string $output The robots.txt output.
	 * @param bool   $public Whether the site is public.
	 * @return string
	 */
	public function filter( string $output, bool $public ): string {
		return Builder::build(
			$output,
			[
				'public'    => $public,
				'sitemap'   => Options::get( 'sitemap_enabled' ) ? Sitemap::get_index_url() : '',
				'llms'      => Options::get( 'llms_txt_enabled' ) ? home_url( '/llms.txt' ) : '',
				'llms_full' => Options::get( 'llms_txt_enabled' ) && Options::get( 'llms_full_txt_enabled' ) ? home_url( '/llms-full.txt' ) : '',
				'signal'    => ContentSignals::format_header( ContentSignals::global_signals() ),
				'blocked'   => Policy::blocked_agents( Registry::all(), Options::get_all() ),
			]
		);
	}

	/**
	 * Path of a physical robots.txt that shadows the virtual one.
	 *
	 * @return string '' when none.
	 */
	public static function physical_file(): string {
		$root = function_exists( 'get_home_path' ) ? get_home_path() : ABSPATH;
		$file = trailingslashit( $root ) . 'robots.txt';

		return is_file( $file ) ? $file : '';
	}

	/**
	 * Robots.txt as WordPress would serve it now (admin preview).
	 *
	 * Mirrors do_robots() without sending headers.
	 *
	 * @return string
	 */
	public static function preview(): string {
		$path   = (string) wp_parse_url( site_url(), PHP_URL_PATH );
		$output = "User-agent: *\nDisallow: {$path}/wp-admin/\nAllow: {$path}/wp-admin/admin-ajax.php\n";

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.
		return (string) apply_filters( 'robots_txt', $output, (bool) get_option( 'blog_public' ) );
	}
}
