<?php
/**
 * Redirects other sitemap index addresses to the LW SEO sitemap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

/**
 * Sends `/sitemap_index.xml` (used by other SEO plugins) and `/wp-sitemap.xml`
 * (WordPress core) to `/sitemap.xml` with a 301, so a sitemap address already
 * submitted to search engines keeps working after switching to LW SEO.
 */
final class LegacyAliases {

	/**
	 * Index file names that are redirected.
	 *
	 * @var array<int, string>
	 */
	private const ALIASES = [ 'sitemap_index.xml', 'wp-sitemap.xml' ];

	/**
	 * Hook the redirect before core's own sitemap handling.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'template_redirect', [ self::class, 'maybe_redirect' ], 0 );
	}

	/**
	 * Redirect when the request is one of the aliases.
	 *
	 * @return void
	 */
	public static function maybe_redirect(): void {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( ! self::is_alias( $uri, (string) wp_parse_url( home_url(), PHP_URL_PATH ) ) ) {
			return;
		}

		wp_safe_redirect( Sitemap::get_index_url(), 301, 'LW SEO' );
		exit;
	}

	/**
	 * Whether a request URI points at one of the aliases in the site root.
	 *
	 * @param string $uri       Request URI (query string allowed).
	 * @param string $home_path Path of the home URL ('' or '/hu').
	 * @return bool
	 */
	public static function is_alias( string $uri, string $home_path ): bool {
		$path = (string) strtok( $uri, '?#' );
		$base = rtrim( $home_path, '/' ) . '/';

		if ( ! str_starts_with( $path, $base ) ) {
			return false;
		}

		return in_array( substr( $path, strlen( $base ) ), self::ALIASES, true );
	}
}
