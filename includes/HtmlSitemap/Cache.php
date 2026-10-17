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
 * Transient cache for the rendered markup, one entry per argument set.
 * The keys are remembered in an option so a flush can drop them all.
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
	 * Option holding the keys in use.
	 */
	private const KEYS_OPTION = 'lw_seo_html_sitemap_keys';

	/**
	 * Get cached markup, or build (as a logged-out visitor) and store it.
	 *
	 * @param string   $key   Cache key (md5 of the arguments and locale).
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
		self::remember_key( $key );

		return $html;
	}

	/**
	 * Drop every cached copy.
	 *
	 * @return void
	 */
	public static function flush(): void {
		foreach ( self::keys() as $key ) {
			delete_transient( self::PREFIX . $key );
		}

		delete_option( self::KEYS_OPTION );
	}

	/**
	 * Record a key for flush().
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	private static function remember_key( string $key ): void {
		$keys = self::keys();
		if ( ! in_array( $key, $keys, true ) ) {
			$keys[] = $key;
			update_option( self::KEYS_OPTION, $keys, false );
		}
	}

	/**
	 * Keys in use.
	 *
	 * @return array<int, string>
	 */
	private static function keys(): array {
		$keys = get_option( self::KEYS_OPTION, [] );

		return is_array( $keys ) ? array_values( array_filter( $keys, 'is_string' ) ) : [];
	}
}
