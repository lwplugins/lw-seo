<?php
/**
 * Variable Converter class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration;

use LightweightPlugins\SEO\Migration\RankMath\Mappings;

/**
 * Converts RankMath template variables to LW SEO format.
 *
 * RankMath: %title%, %sep%, %sitename%
 * LW SEO:  %%title%%, %%sep%%, %%sitename%%
 */
final class VariableConverter {

	/**
	 * Convert RankMath variables in a value to LW SEO format.
	 *
	 * @param mixed $value The value to convert.
	 * @return mixed
	 */
	public static function convert( mixed $value ): mixed {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		return preg_replace_callback(
			'/%([a-z_]+)%/',
			static function ( array $matches ): string {
				$var = $matches[1];
				$var = Mappings::VARIABLE_NAME_MAP[ $var ] ?? $var;
				return '%%' . $var . '%%';
			},
			$value
		);
	}
}
