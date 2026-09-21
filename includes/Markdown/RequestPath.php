<?php
/**
 * Markdown request path helpers.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Parses /md and /markdown suffixes on paths relative to the site home.
 */
final class RequestPath {

	/**
	 * Endpoint suffixes.
	 */
	private const SUFFIXES = [ '/md', '/markdown' ];

	/**
	 * Request path relative to the home path (subdirectory installs).
	 *
	 * @param string $request_path Request URI path.
	 * @param string $home_path    Path of home_url() ('' at the domain root).
	 * @return string
	 */
	public static function relative( string $request_path, string $home_path ): string {
		$request = '/' . trim( $request_path, '/' );
		$home    = '/' . trim( $home_path, '/' );

		if ( '/' !== $home && ( $request === $home || str_starts_with( $request, $home . '/' ) ) ) {
			$request = substr( $request, strlen( $home ) );
		}

		return trim( $request, '/' );
	}

	/**
	 * Whether the path ends in a Markdown suffix.
	 *
	 * @param string $path Relative path.
	 * @return bool
	 */
	public static function has_suffix( string $path ): bool {
		return null !== self::matching_suffix( $path );
	}

	/**
	 * Path without its Markdown suffix ('' = site root).
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	public static function strip( string $path ): string {
		$normalized = '/' . trim( $path, '/' );
		$suffix     = self::matching_suffix( $path );

		if ( null === $suffix ) {
			return trim( $path, '/' );
		}

		return trim( substr( $normalized, 0, -strlen( $suffix ) ), '/' );
	}

	/**
	 * The suffix the path ends with, if any.
	 *
	 * @param string $path Relative path.
	 * @return string|null
	 */
	private static function matching_suffix( string $path ): ?string {
		$normalized = '/' . trim( $path, '/' );

		foreach ( self::SUFFIXES as $suffix ) {
			if ( str_ends_with( $normalized, $suffix ) ) {
				return $suffix;
			}
		}

		return null;
	}
}
