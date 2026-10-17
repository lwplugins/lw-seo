<?php
/**
 * HTML sitemap cache.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\HtmlSitemap;

use LightweightPlugins\SEO\LlmsTxt\Cache as VisitorCache;

/**
 * Transient cache for the rendered markup, one entry per argument set and
 * cache generation.
 */
final class Cache {

	/**
	 * Transient key prefix.
	 */
	private const PREFIX = 'lw_seo_html_sitemap_';

	/**
	 * Cache lifetime in seconds (12 hours).
	 */
	private const TTL = 43200;

	/**
	 * Option holding the cache generation.
	 */
	private const VERSION_OPTION = 'lw_seo_html_sitemap_version';

	/**
	 * Cache key for a set of resolved arguments. It includes the locale and
	 * the generation, so a flush changes every key at once.
	 *
	 * @param array<string, mixed> $args Resolved arguments.
	 * @return string
	 */
	public static function key( array $args ): string {
		return md5( (string) wp_json_encode( $args ) . get_locale() . self::version() );
	}

	/**
	 * Get cached markup, or build (as a logged-out visitor) and store it.
	 *
	 * @param string   $key   Cache key from key().
	 * @param callable $build Builder returning the markup.
	 * @return string
	 */
	public static function remember( string $key, callable $build ): string {
		$cached = get_transient( self::PREFIX . $key );
		if ( is_string( $cached ) ) {
			return $cached;
		}

		$html = VisitorCache::build_as_visitor( $build );
		set_transient( self::PREFIX . $key, $html, self::TTL );

		return $html;
	}

	/**
	 * Invalidate every cached copy: the generation moves on and the old
	 * transients expire by their TTL.
	 *
	 * @return void
	 */
	public static function flush(): void {
		update_option( self::VERSION_OPTION, self::version() + 1, true );
	}

	/**
	 * Current cache generation.
	 *
	 * @return int
	 */
	public static function version(): int {
		return max( 1, (int) get_option( self::VERSION_OPTION, 1 ) );
	}
}
