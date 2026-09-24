<?php
/**
 * Settings sanitizer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

use LightweightPlugins\SEO\Local\OpeningHours;

/**
 * Sanitizes the settings form input against the option defaults.
 *
 * The type of each default decides how its input is cleaned, so a new
 * option normally needs nothing more than a default in Options.
 */
final class SettingsSanitizer {

	/**
	 * Keys holding multi-line text.
	 */
	private const TEXTAREA_KEYS = [ 'llms_txt_intro', 'llms_txt_optional_links' ];

	/**
	 * Keys restricted to fixed values; the first value is the fallback.
	 */
	private const CHOICE_KEYS = [
		'content_signals_search'   => [ '', 'yes', 'no' ],
		'content_signals_ai_input' => [ '', 'yes', 'no' ],
		'content_signals_ai_train' => [ '', 'yes', 'no' ],
	];

	/**
	 * Key prefixes (or full keys) sanitized as URLs.
	 */
	private const URL_KEYS = [ 'social_', 'default_og_image', 'knowledge_logo' ];

	/**
	 * The fixed value lists for choice options, keyed by option key.
	 *
	 * Exposed so other callers (the WP-CLI `option set` command) can
	 * validate against the same list instead of duplicating it.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function choices(): array {
		return self::CHOICE_KEYS;
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * Every default key is sanitized from $input; a key missing from $input
	 * is treated as unset (false for a bool, '' for a URL, the default
	 * otherwise). Partial updates must merge onto the stored values first
	 * (SettingsStore::merge()).
	 *
	 * @param array<string, mixed> $input    Submitted values.
	 * @param array<string, mixed> $defaults Option defaults.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input, array $defaults ): array {
		$sanitized = [];

		foreach ( $defaults as $key => $default ) {
			$sanitized[ $key ] = self::value( (string) $key, $input[ $key ] ?? null, $default );
		}

		return $sanitized;
	}

	/**
	 * Sanitize one value according to its default's type.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $value   Submitted value, null when absent.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private static function value( string $key, mixed $value, mixed $default ): mixed {
		if ( is_bool( $default ) ) {
			return ! empty( $value );
		}

		if ( is_array( $default ) ) {
			return self::bool_map( $value );
		}

		if ( is_int( $default ) ) {
			return null === $value ? $default : absint( $value );
		}

		if ( isset( self::CHOICE_KEYS[ $key ] ) ) {
			return in_array( $value, self::CHOICE_KEYS[ $key ], true ) ? $value : self::CHOICE_KEYS[ $key ][0];
		}

		if ( OpeningHours::is_time_key( $key ) ) {
			return OpeningHours::sanitize_time( $value );
		}

		if ( self::is_url_key( $key ) ) {
			return null === $value ? '' : esc_url_raw( $value );
		}

		if ( null === $value ) {
			return $default;
		}

		return self::text( $key, (string) $value );
	}

	/**
	 * Sanitize a text option without damaging %%variables%%.
	 *
	 * WordPress sanitize_text_field() drops anything that looks like a percent-encoded
	 * octet, so "%%date%%" (%da) or "%%category%%" (%ca) in a title template
	 * lost characters on every save. The variables are swapped out for inert
	 * placeholders first and restored afterwards.
	 *
	 * @param string $key   Option key.
	 * @param string $value Submitted text.
	 * @return string
	 */
	public static function text( string $key, string $value ): string {
		$vars  = [];
		$value = (string) preg_replace_callback(
			'/%%[a-z_]+%%/i',
			static function ( array $match ) use ( &$vars ): string {
				$vars[] = $match[0];
				return 'LWSEOVAR' . ( count( $vars ) - 1 ) . 'X';
			},
			$value
		);

		$value = in_array( $key, self::TEXTAREA_KEYS, true ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );

		return (string) preg_replace_callback(
			'/LWSEOVAR(\d+)X/',
			static fn ( array $match ): string => $vars[ (int) $match[1] ] ?? '',
			$value
		);
	}

	/**
	 * Clean a name => bool map (per-type toggles).
	 *
	 * @param mixed $value Submitted value.
	 * @return array<string, bool>
	 */
	private static function bool_map( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$map = [];
		foreach ( $value as $name => $flag ) {
			$name = sanitize_key( (string) $name );
			if ( '' !== $name ) {
				$map[ $name ] = ! empty( $flag );
			}
		}

		return $map;
	}

	/**
	 * Whether a key is a URL field.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	private static function is_url_key( string $key ): bool {
		foreach ( self::URL_KEYS as $pattern ) {
			if ( $key === $pattern || str_starts_with( $key, $pattern ) ) {
				return true;
			}
		}

		return false;
	}
}
