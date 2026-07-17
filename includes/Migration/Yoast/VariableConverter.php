<?php
/**
 * Yoast template-variable converter.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

/**
 * Normalizes Yoast %%variable%% names to LW SEO names.
 *
 * Yoast already uses the %%name%% syntax, so this only renames diverging
 * variables (name → author, primary_category → category) and strips variables
 * LW SEO has no equivalent for (mapped to '' in VARIABLE_NAME_MAP).
 */
final class VariableConverter {

	/**
	 * Convert Yoast variables in a value to LW SEO format.
	 *
	 * @param mixed $value The value to convert.
	 * @return mixed
	 */
	public static function convert( mixed $value ): mixed {
		if ( ! is_string( $value ) || '' === $value ) {
			return $value;
		}

		return preg_replace_callback(
			'/%%([a-z_]+)%%/',
			static function ( array $matches ): string {
				$var = $matches[1];
				if ( ! array_key_exists( $var, Mappings::VARIABLE_NAME_MAP ) ) {
					return '%%' . $var . '%%';
				}
				$mapped = Mappings::VARIABLE_NAME_MAP[ $var ];
				return '' === $mapped ? '' : '%%' . $mapped . '%%';
			},
			$value
		);
	}
}
