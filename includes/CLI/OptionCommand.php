<?php
/**
 * `wp lw-seo option` command.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\CLI;

use LightweightPlugins\SEO\Options;

/**
 * Read and write LW SEO options.
 */
final class OptionCommand {

	/**
	 * Get a single option value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Option key (e.g. title_home).
	 *
	 * @param array<int, string>    $args       [key].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function get( array $args, array $assoc_args ): void {
		if ( ! array_key_exists( $args[0], Options::get_defaults() ) ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s', $args[0] ) );
		}

		\WP_CLI::line( $this->stringify( Options::get( $args[0] ) ) );
	}

	/**
	 * Set an option value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Option key.
	 *
	 * <value>
	 * : New value. true/false/1/0/on/yes are cast to bool for boolean options.
	 *
	 * @param array<int, string>    $args       [key, value].
	 * @param array<string, string> $assoc_args Associative args (unused).
	 * @return void
	 */
	public function set( array $args, array $assoc_args ): void {
		$defaults = Options::get_defaults();
		if ( ! array_key_exists( $args[0], $defaults ) ) {
			\WP_CLI::error( sprintf( 'Unknown option: %s', $args[0] ) );
		}

		$value = $args[1];
		if ( is_bool( $defaults[ $args[0] ] ) ) {
			$value = in_array( strtolower( $args[1] ), [ 'true', '1', 'on', 'yes' ], true );
		}

		Options::set( $args[0], $value );
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
