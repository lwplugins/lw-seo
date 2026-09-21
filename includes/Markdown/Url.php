<?php
/**
 * Markdown URL builder.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Builds the URL of an object's Markdown version (/md endpoint).
 */
final class Url {

	/**
	 * Markdown URL of a post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public static function for_post( \WP_Post $post ): string {
		return self::from_permalink( (string) get_permalink( $post ), '' !== (string) get_option( 'permalink_structure' ) );
	}

	/**
	 * Markdown URL for a permalink.
	 *
	 * @param string $permalink Canonical URL.
	 * @param bool   $pretty    Whether pretty permalinks are enabled.
	 * @return string
	 */
	public static function from_permalink( string $permalink, bool $pretty ): string {
		if ( ! $pretty ) {
			return add_query_arg( 'format', 'md', $permalink );
		}

		$parts = explode( '?', $permalink, 2 );
		$url   = rtrim( $parts[0], '/' ) . '/md/';

		return isset( $parts[1] ) ? $url . '?' . $parts[1] : $url;
	}
}
