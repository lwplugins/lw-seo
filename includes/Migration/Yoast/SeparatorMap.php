<?php
/**
 * Yoast separator token → LW SEO character mapper.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

use LightweightPlugins\SEO\Options;

/**
 * Converts a Yoast separator token (or literal char) into an LW-SEO-supported
 * separator character, defaulting to a dash.
 */
final class SeparatorMap {

	/**
	 * Map a Yoast separator token to a supported character.
	 *
	 * @param string $token Yoast token (e.g. 'sc-mdash') or literal char.
	 * @return string A separator character LW SEO supports.
	 */
	public static function to_char( string $token ): string {
		if ( isset( Mappings::SEPARATOR_TOKEN_MAP[ $token ] ) ) {
			return Mappings::SEPARATOR_TOKEN_MAP[ $token ];
		}

		$supported = Options::get_separators();
		if ( isset( $supported[ $token ] ) ) {
			return $token;
		}

		return '-';
	}
}
