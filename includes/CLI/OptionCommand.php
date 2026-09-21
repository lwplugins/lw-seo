<?php
/**
 * `wp lw-seo option` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Admin\SettingsSanitizer;
use LightweightPlugins\SEO\Options;

/**
 * Read and write LW SEO options, validated the same way the settings page
 * validates them.
 */
final class OptionCommand {

	/**
	 * Get a single option value, or one entry of a map option via dot-path
	 * (e.g. sitemap_post_types.case_study).
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Option key, optionally dot-path'd into a map entry.
	 *
	 * @param array<int, string>    $args       [key].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function get( array $args, array $assoc_args ): void {
		$defaults = Options::get_defaults();
		$dot      = OptionValueParser::split_dot_path( $args[0] );

		if ( null !== $dot ) {
			$this->get_map_entry( $dot[0], $dot[1], $defaults );
			return;
		}

		if ( ! array_key_exists( $args[0], $defaults ) ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s', $args[0] ) );
			return;
		}

		\WP_CLI::line( $this->stringify( Options::get( $args[0] ) ) );
	}

	/**
	 * Set an option value, validated and sanitized the same way the
	 * settings page validates it. A dot-path key (e.g.
	 * sitemap_post_types.case_study) updates one entry of a map option and
	 * keeps the rest.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Option key, optionally dot-path'd into a map entry.
	 *
	 * <value>
	 * : New value. Boolean options accept true/false/1/0/on/off/yes/no.
	 * Map options accept a JSON object, e.g. '{"case_study":false}'.
	 *
	 * @param array<int, string>    $args       [key, value].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function set( array $args, array $assoc_args ): void {
		$defaults = Options::get_defaults();
		$raw      = $args[1] ?? '';
		$dot      = OptionValueParser::split_dot_path( $args[0] );

		if ( null !== $dot ) {
			$this->set_map_entry( $dot[0], $dot[1], $raw, $defaults );
			return;
		}

		if ( ! array_key_exists( $args[0], $defaults ) ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s', $args[0] ) );
			return;
		}

		try {
			$parsed = OptionValueParser::parse( $args[0], $raw, $defaults[ $args[0] ], SettingsSanitizer::choices() );
		} catch ( \InvalidArgumentException $e ) {
			\WP_CLI::error( $e->getMessage() );
			return;
		}

		$sanitized = SettingsSanitizer::sanitize( [ $args[0] => $parsed ], [ $args[0] => $defaults[ $args[0] ] ] )[ $args[0] ];
		Options::set( $args[0], $sanitized );
		\WP_CLI::success( sprintf( 'Set %s.', $args[0] ) );
	}

	/**
	 * List all options.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. Options: table, json, yaml, csv. Default: table.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function list( array $args, array $assoc_args ): void {
		$rows = [];
		foreach ( Options::get_all() as $key => $value ) {
			$rows[] = [
				'key'   => $key,
				'value' => $this->stringify( $value ),
			];
		}

		\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, [ 'key', 'value' ] );
	}

	/**
	 * Reset all options to defaults.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation.
	 *
	 * @param array<int, string>    $args       Positional args (unused).
	 * @param array<string, string> $assoc_args Associative args.
	 * @return void
	 */
	public function reset( array $args, array $assoc_args ): void {
		\WP_CLI::confirm( 'Reset ALL LW SEO options to defaults?', $assoc_args );
		Options::reset();
		\WP_CLI::success( 'Options reset to defaults.' );
	}

	/**
	 * Print one entry of a map option, falling back to the default state a
	 * missing entry resolves to.
	 *
	 * @param string               $base     Map option key.
	 * @param string               $entry    Entry name.
	 * @param array<string, mixed> $defaults Option defaults.
	 * @return void
	 */
	private function get_map_entry( string $base, string $entry, array $defaults ): void {
		if ( ! array_key_exists( $base, $defaults ) || ! is_array( $defaults[ $base ] ) ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s.%s', $base, $entry ) );
			return;
		}

		$map   = Options::get( $base );
		$map   = is_array( $map ) ? $map : [];
		$value = array_key_exists( $entry, $map ) ? (bool) $map[ $entry ] : OptionValueParser::map_entry_default( $base );

		\WP_CLI::line( $this->stringify( $value ) );
	}

	/**
	 * Update one entry of a map option, keeping the rest.
	 *
	 * @param string               $base     Map option key.
	 * @param string               $entry    Entry name.
	 * @param string               $raw      Raw CLI value.
	 * @param array<string, mixed> $defaults Option defaults.
	 * @return void
	 */
	private function set_map_entry( string $base, string $entry, string $raw, array $defaults ): void {
		if ( ! array_key_exists( $base, $defaults ) || ! is_array( $defaults[ $base ] ) ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s.%s', $base, $entry ) );
			return;
		}

		try {
			$value = OptionValueParser::parse_bool( $raw );
		} catch ( \InvalidArgumentException $e ) {
			\WP_CLI::error( $e->getMessage() );
			return;
		}

		$map           = Options::get( $base );
		$map           = is_array( $map ) ? $map : [];
		$map[ $entry ] = $value;

		$sanitized = SettingsSanitizer::sanitize( [ $base => $map ], [ $base => $defaults[ $base ] ] )[ $base ];
		Options::set( $base, $sanitized );
		\WP_CLI::success( sprintf( 'Set %s.%s.', $base, $entry ) );
	}

	/**
	 * Render an option value as a display string.
	 *
	 * @param mixed $value Option value.
	 * @return string
	 */
	private function stringify( mixed $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return is_scalar( $value ) ? (string) $value : (string) wp_json_encode( $value );
	}
}
