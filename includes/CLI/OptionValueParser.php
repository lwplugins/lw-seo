<?php
/**
 * CLI option value parser.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

/**
 * Turns a raw `wp lw-seo option set` CLI string into a typed value, matched
 * against an option's default. Pure — no WordPress or WP-CLI calls, so
 * OptionCommand still has to run the result through
 * Admin\SettingsSanitizer::sanitize() before Options::set().
 *
 * Invalid input is reported as an InvalidArgumentException; the caller
 * turns that into \WP_CLI::error().
 */
final class OptionValueParser {

	/**
	 * State used for a map entry missing from the stored option, one per
	 * dot-path-capable map option. Kept in one place so it isn't
	 * duplicated between the CLI and the consumers that apply the same
	 * fallback: Sitemap\ProviderRegistry::post_type_enabled()/
	 * taxonomy_enabled() and LlmsTxt\SectionCollector::post_types().
	 */
	private const MAP_ENTRY_DEFAULTS = [
		'sitemap_post_types'  => true,
		'sitemap_taxonomies'  => false,
		'llms_txt_post_types' => true,
	];

	/**
	 * Split a "sitemap_post_types.case_study" key into its map option and
	 * entry name.
	 *
	 * @param string $key CLI key argument.
	 * @return array{0: string, 1: string}|null Null when $key has no dot-path.
	 */
	public static function split_dot_path( string $key ): ?array {
		if ( ! str_contains( $key, '.' ) ) {
			return null;
		}

		[ $base, $entry ] = explode( '.', $key, 2 );

		return ( '' !== $base && '' !== $entry ) ? [ $base, $entry ] : null;
	}

	/**
	 * The state used for a name missing from a dot-path-capable map option.
	 *
	 * @param string $map_key Map option key (e.g. sitemap_post_types).
	 * @return bool
	 */
	public static function map_entry_default( string $map_key ): bool {
		return self::MAP_ENTRY_DEFAULTS[ $map_key ] ?? false;
	}

	/**
	 * Parse a value for a whole (non-dot-path) option, typed by its default.
	 *
	 * @param string                            $key     Option key.
	 * @param string                            $raw     Raw CLI value.
	 * @param mixed                             $default Option default; decides the target type.
	 * @param array<string, array<int, string>> $choices Allowed values per choice key (Admin\SettingsSanitizer::choices()).
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException When $raw cannot be parsed for the target type.
	 */
	public static function parse( string $key, string $raw, mixed $default, array $choices ): mixed {
		if ( is_bool( $default ) ) {
			return self::parse_bool( $raw );
		}

		if ( is_int( $default ) ) {
			return self::parse_int( $raw );
		}

		if ( is_array( $default ) ) {
			return self::parse_map( $raw );
		}

		if ( isset( $choices[ $key ] ) && ! in_array( $raw, $choices[ $key ], true ) ) {
			throw new \InvalidArgumentException(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI-only message; WP_CLI::error() prints it as plain text, never rendered as HTML.
				sprintf( 'Invalid value "%s" for %s. Allowed: %s', $raw, $key, self::describe_choices( $choices[ $key ] ) )
			);
		}

		return $raw;
	}

	/**
	 * Parse a boolean token (used for bool options and map dot-path values).
	 *
	 * @param string $raw Raw CLI value.
	 * @return bool
	 *
	 * @throws \InvalidArgumentException When $raw isn't a recognised boolean token.
	 */
	public static function parse_bool( string $raw ): bool {
		$normalized = strtolower( trim( $raw ) );

		if ( in_array( $normalized, [ 'true', '1', 'on', 'yes' ], true ) ) {
			return true;
		}

		if ( in_array( $normalized, [ 'false', '0', 'off', 'no' ], true ) ) {
			return false;
		}

		throw new \InvalidArgumentException(
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI-only message; WP_CLI::error() prints it as plain text, never rendered as HTML.
			sprintf( 'Invalid boolean value "%s". Use one of: true, false, 1, 0, on, off, yes, no.', $raw )
		);
	}

	/**
	 * Parse a numeric token.
	 *
	 * @param string $raw Raw CLI value.
	 * @return int
	 *
	 * @throws \InvalidArgumentException When $raw isn't numeric.
	 */
	public static function parse_int( string $raw ): int {
		if ( ! is_numeric( $raw ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI-only message; WP_CLI::error() prints it as plain text, never rendered as HTML.
			throw new \InvalidArgumentException( sprintf( 'Invalid number "%s".', $raw ) );
		}

		return (int) $raw;
	}

	/**
	 * Parse a JSON object into a name => value map. Replaces the whole map.
	 *
	 * @param string $raw Raw CLI value, expected to be a JSON object.
	 * @return array<string, mixed>
	 *
	 * @throws \InvalidArgumentException When $raw isn't a JSON object.
	 */
	public static function parse_map( string $raw ): array {
		$decoded = json_decode( $raw, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) || ( [] !== $decoded && array_is_list( $decoded ) ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI-only message; WP_CLI::error() prints it as plain text, never rendered as HTML.
			throw new \InvalidArgumentException( sprintf( 'Invalid JSON object: %s', $raw ) );
		}

		return $decoded;
	}

	/**
	 * Render a choice list for an error message, quoting the empty option.
	 *
	 * @param array<int, string> $choices Allowed values.
	 * @return string
	 */
	private static function describe_choices( array $choices ): string {
		return implode(
			', ',
			array_map( static fn( string $choice ): string => '' === $choice ? "''" : $choice, $choices )
		);
	}
}
