<?php
/**
 * Text sanitizing for URL and path input.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Helpers;

/**
 * Like sanitize_text_field(), but keeps percent-encoded octets.
 *
 * Core's text sanitizer deletes every `%XX` sequence, which turns an encoded
 * non-ASCII path such as `/tag/caf%C3%A9/` into `/tag/caf/`.
 */
final class UrlField {

	/**
	 * Placeholder for `%` while core's sanitizer runs (a private-use character).
	 *
	 * @var string
	 */
	private const MARKER = "\u{E000}";

	/**
	 * Sanitize a URL, path or pattern typed or sent as text.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function clean( string $value ): string {
		$value     = str_replace( self::MARKER, '', $value );
		$protected = (string) preg_replace( '/%([0-9a-fA-F]{2})/', self::MARKER . '$1', $value );

		return str_replace( self::MARKER, '%', sanitize_text_field( $protected ) );
	}
}
