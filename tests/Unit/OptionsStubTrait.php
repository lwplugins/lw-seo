<?php
/**
 * Options stub for unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;

/**
 * Makes Options::get() read the given saved values (defaults fill the rest).
 */
trait OptionsStubTrait {

	/**
	 * @param array<string, mixed> $saved Saved lw_seo_options values.
	 * @param array<string, mixed> $other Other WordPress options by name.
	 */
	private function stub_options( array $saved = [], array $other = [] ): void {
		Options::clear_cache();
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, (array) $args ) );
		Functions\when( 'get_option' )->alias(
			static function ( string $name, $fallback = false ) use ( $saved, $other ) {
				if ( Options::OPTION_NAME === $name ) {
					return $saved;
				}
				return array_key_exists( $name, $other ) ? $other[ $name ] : $fallback;
			}
		);
	}
}
