<?php
/**
 * Settings store for the admin API.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

use LightweightPlugins\SEO\Options;

/**
 * Reads the settings as typed values and applies partial updates.
 *
 * A partial update merges the submitted keys onto the stored options, so a
 * key the client did not send keeps its stored value (never "absent bool =
 * false"). Unknown keys are dropped.
 */
final class SettingsStore {

	/**
	 * Current settings, every default key typed like its default.
	 *
	 * @return array<string, mixed>
	 */
	public static function current(): array {
		Options::clear_cache();

		return self::typed( Options::get_all(), Options::get_defaults() );
	}

	/**
	 * Apply a partial update and return the new settings.
	 *
	 * Saved with update_option(), so the update_option_lw_seo_options
	 * side effects (rewrite flush, llms.txt cache) still run.
	 *
	 * @param array<string, mixed> $body Submitted option keys.
	 * @return array<string, mixed>
	 */
	public static function save( array $body ): array {
		$merged = self::merge( $body, get_option( Options::OPTION_NAME, [] ), Options::get_defaults() );

		update_option( Options::OPTION_NAME, $merged );

		return self::current();
	}

	/**
	 * Merge submitted keys onto the stored options. Only the submitted keys
	 * are sanitized; stored values are kept as they are, so an unrelated save
	 * can never re-process (and damage) a value the user did not touch.
	 *
	 * @param array<string, mixed> $body     Submitted option keys.
	 * @param mixed                $stored   Stored option value.
	 * @param array<string, mixed> $defaults Option defaults.
	 * @return array<string, mixed>
	 */
	public static function merge( array $body, mixed $stored, array $defaults ): array {
		$stored    = is_array( $stored ) ? $stored : [];
		$submitted = array_intersect_key( $body, $defaults );
		$merged    = array_merge( $defaults, array_intersect_key( $stored, $defaults ), $submitted );
		$sanitized = SettingsSanitizer::sanitize( $merged, $defaults );

		return array_merge( $defaults, array_intersect_key( $stored, $defaults ), array_intersect_key( $sanitized, $submitted ) );
	}

	/**
	 * Cast every default key to its default's type for the JSON response.
	 *
	 * Maps (array defaults) are returned as objects so an empty map is
	 * `{}`, not `[]`.
	 *
	 * @param array<string, mixed> $values   Option values.
	 * @param array<string, mixed> $defaults Option defaults.
	 * @return array<string, mixed>
	 */
	public static function typed( array $values, array $defaults ): array {
		$typed = [];

		foreach ( $defaults as $key => $default ) {
			$value = array_key_exists( $key, $values ) ? $values[ $key ] : $default;

			if ( is_bool( $default ) ) {
				$typed[ $key ] = (bool) $value;
			} elseif ( is_int( $default ) ) {
				$typed[ $key ] = is_numeric( $value ) ? (int) $value : $default;
			} elseif ( is_array( $default ) ) {
				$typed[ $key ] = (object) ( is_array( $value ) ? $value : [] );
			} else {
				$typed[ $key ] = is_scalar( $value ) ? (string) $value : (string) $default;
			}
		}

		return $typed;
	}
}
