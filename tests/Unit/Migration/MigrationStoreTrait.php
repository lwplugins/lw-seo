<?php
/**
 * In-memory WordPress meta/option store and fixture loader for importer tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use Mockery;

/**
 * Stubs get/update_{post,term}_meta and get/update_option on arrays, records
 * every write, and loads the JSON fixtures exported from real SEOPress /
 * All in One SEO data (tests/fixtures/migration/).
 */
trait MigrationStoreTrait {

	/**
	 * Meta store: "post:7" => [ key => value ].
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $meta = [];

	/**
	 * Option store.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	/**
	 * Every write: "post:7:_lw_seo_title", "option:lw_seo_options".
	 *
	 * @var array<string>
	 */
	private array $writes = [];

	/**
	 * Load a fixture.
	 *
	 * @param string $name Fixture base name.
	 * @return array<string, mixed>
	 */
	private function fixture( string $name ): array {
		$json = file_get_contents( dirname( __DIR__, 2 ) . '/fixtures/migration/' . $name . '.json' );
		return json_decode( (string) $json, true );
	}

	/**
	 * Install the in-memory store.
	 *
	 * @return void
	 */
	private function stub_store(): void {
		Options::clear_cache();
		Functions\stubTranslationFunctions();
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, (array) $args ) );

		foreach ( [ 'post', 'term' ] as $type ) {
			Functions\when( "get_{$type}_meta" )->alias(
				fn( $id, $key = '', $single = false ) => $this->meta[ "$type:$id" ][ $key ] ?? ''
			);
			Functions\when( "update_{$type}_meta" )->alias(
				function ( $id, $key, $value ) use ( $type ): bool {
					$this->meta[ "$type:$id" ][ $key ] = $value;
					$this->writes[]                    = "$type:$id:$key";
					return true;
				}
			);
		}

		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$this->options[ $name ] = $value;
				$this->writes[]         = "option:$name";
				return true;
			}
		);
	}

	/**
	 * A $wpdb double that answers reads and fails the test on any write.
	 *
	 * @return \Mockery\MockInterface
	 */
	private function read_only_wpdb(): \Mockery\MockInterface {
		$wpdb           = Mockery::mock();
		$wpdb->prefix   = 'wp_';
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->termmeta = 'wp_termmeta';
		$wpdb->posts    = 'wp_posts';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( static fn( string $sql ) => $sql );
		$wpdb->shouldReceive( 'esc_like' )->andReturnUsing( static fn( string $text ) => $text );
		foreach ( [ 'query', 'insert', 'update', 'delete', 'replace' ] as $write ) {
			$wpdb->shouldReceive( $write )->never();
		}
		$GLOBALS['wpdb'] = $wpdb;

		return $wpdb;
	}

	/**
	 * Keys written outside LW SEO's own meta and options.
	 *
	 * @return array<string>
	 */
	private function foreign_writes(): array {
		return array_values(
			array_filter(
				$this->writes,
				static fn( string $write ): bool => ! str_contains( $write, ':_lw_seo_' ) && ! in_array( $write, [ 'option:lw_seo_options', 'option:lw_seo_redirects' ], true )
			)
		);
	}
}
