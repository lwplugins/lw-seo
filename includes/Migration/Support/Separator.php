<?php
/**
 * Title separator normalizer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

use LightweightPlugins\SEO\Options;

/**
 * Turns a stored separator (a character or its HTML entity) into one of the
 * separators LW SEO offers, or null when LW SEO has no such separator.
 */
final class Separator {

	/**
	 * Normalize a separator.
	 *
	 * @param mixed $raw Stored separator, e.g. "-", "&#45;" or "&raquo;".
	 * @return string|null Supported separator, null when unsupported or empty.
	 */
	public static function normalize( mixed $raw ): ?string {
		if ( ! is_string( $raw ) ) {
			return null;
		}

		$char = trim( html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $char ) {
			return null;
		}

		return isset( Options::get_separators()[ $char ] ) ? $char : null;
	}
}
