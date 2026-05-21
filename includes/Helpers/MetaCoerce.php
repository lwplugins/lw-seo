<?php
/**
 * Meta value coercion helpers.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers;

/**
 * Coerces meta values into safe shapes for read & write paths.
 *
 * RankMath occasionally stores cache-array values under keys that
 * superficially look like image-URL fields (`rank_math_og_content_image`
 * is `['check' => md5, 'images' => [int|string, ...]]`). v1.3.13's
 * migrator copied those arrays verbatim into `_lw_seo_og_image`, which
 * blew up `Plugin::get_og_image(): string` on every singular page.
 *
 * This class defends BOTH:
 *   - read paths: return '' (or a best-effort URL) instead of an array
 *   - write paths: only persist when the value is a usable string
 */
final class MetaCoerce {

	/**
	 * Coerce any meta value into a URL string, returning '' when not possible.
	 *
	 * Handles three known shapes:
	 *   1. plain string URL → returned as-is
	 *   2. ['url' => '...', 'id' => N] → returns 'url'
	 *   3. ['check' => md5, 'images' => [int|string, ...]] → returns first usable URL,
	 *      resolving int attachment IDs via wp_get_attachment_url()
	 *
	 * @param mixed $value Raw meta value.
	 * @return string URL or empty string.
	 */
	public static function as_url( mixed $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			return '';
		}

		if ( isset( $value['url'] ) && is_string( $value['url'] ) && '' !== $value['url'] ) {
			return $value['url'];
		}

		if ( isset( $value['images'] ) && is_array( $value['images'] ) ) {
			foreach ( $value['images'] as $candidate ) {
				if ( is_string( $candidate ) && '' !== $candidate ) {
					return $candidate;
				}
				if ( is_int( $candidate ) && $candidate > 0 ) {
					$url = wp_get_attachment_url( $candidate );
					if ( is_string( $url ) && '' !== $url ) {
						return $url;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Coerce any meta value into a plain string, '' when not coercible.
	 *
	 * @param mixed $value Raw meta value.
	 * @return string
	 */
	public static function as_string( mixed $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}
		return '';
	}

	/**
	 * Whether a value should be written to a string-typed meta target.
	 *
	 * Used by the RankMath migrator's field copy step so cache-array
	 * values can never land in `_lw_seo_*` slots that expect strings.
	 *
	 * @param mixed  $value Raw value from the source plugin.
	 * @param string $field LW SEO field name (without prefix).
	 * @return bool
	 */
	public static function is_writable_string( mixed $value, string $field ): bool {
		if ( 'og_image' === $field ) {
			return '' !== self::as_url( $value );
		}
		return is_string( $value ) || is_int( $value ) || is_float( $value );
	}
}
