<?php
/**
 * Redirect Repository unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Redirects;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Redirects\Manager;
use LightweightPlugins\SEO\Redirects\Repository;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class RepositoryTest extends MonkeyTestCase {

	/**
	 * In-memory wp_options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	protected function setUp(): void {
		parent::setUp();
		$this->options = [];

		Functions\when( 'get_option' )->alias( fn( string $name, $fallback = false ) => $this->options[ $name ] ?? $fallback );
		Functions\when( 'update_option' )->alias(
			function ( string $name, $value ): bool {
				$changed                = ( $this->options[ $name ] ?? null ) !== $value;
				$this->options[ $name ] = $value;
				return $changed;
			}
		);
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'current_time' )->justReturn( '2026-09-24 10:00:00' );

		$counter = 0;
		Functions\when( 'wp_generate_uuid4' )->alias(
			static function () use ( &$counter ): string {
				++$counter;
				return 'uuid-' . $counter;
			}
		);
	}

	/**
	 * A stored item without an id (as saved before ids existed).
	 *
	 * @param string $source Source.
	 * @return array<string, mixed>
	 */
	private function legacy_item( string $source ): array {
		return [
			'source'        => $source,
			'destination'   => '/new',
			'type'          => 301,
			'regex'         => false,
			'hits'          => 3,
			'last_accessed' => '',
		];
	}

	public function test_all_assigns_and_persists_missing_ids(): void {
		$this->options[ Manager::OPTION_NAME ] = [ $this->legacy_item( '/a' ), $this->legacy_item( '/b' ) ];

		$items = Repository::all();

		$this->assertSame( [ 'uuid-1', 'uuid-2' ], array_column( $items, 'id' ) );
		$this->assertSame( [ 'uuid-1', 'uuid-2' ], array_column( $this->options[ Manager::OPTION_NAME ], 'id' ) );
	}

	public function test_ids_are_stable_across_reads(): void {
		$this->options[ Manager::OPTION_NAME ] = [ $this->legacy_item( '/a' ) ];

		Repository::all();
		$items = Repository::all();

		$this->assertSame( 'uuid-1', $items[0]['id'] );
	}

	public function test_format_returns_the_typed_api_shape(): void {
		$item = Repository::format( [ 'id' => 'x', 'source' => '/a', 'type' => '302', 'regex' => 1, 'hits' => '4' ] );

		$this->assertSame(
			[
				'id'            => 'x',
				'source'        => '/a',
				'destination'   => '',
				'type'          => 302,
				'regex'         => true,
				'hits'          => 4,
				'last_accessed' => '',
				'created'       => '',
			],
			$item
		);
	}

	public function test_create_returns_the_new_item_with_an_id(): void {
		$item = Repository::create( 'old-page/', '/new-page', 301, false );

		$this->assertSame( [ 'uuid-1', '/old-page', 0 ], [ $item['id'] ?? null, $item['source'] ?? null, $item['hits'] ?? null ] );
	}

	public function test_update_with_unchanged_values_succeeds(): void {
		$this->options[ Manager::OPTION_NAME ] = [ [ 'id' => 'keep' ] + $this->legacy_item( '/a' ) ];

		$item = Repository::update( 'keep', '/a', '/new', 301, false );

		$this->assertSame( '/a', $item['source'] ?? null );
	}

	public function test_update_changes_the_item_addressed_by_id(): void {
		$this->options[ Manager::OPTION_NAME ] = [
			[ 'id' => 'first' ] + $this->legacy_item( '/a' ),
			[ 'id' => 'second' ] + $this->legacy_item( '/b' ),
		];

		Repository::update( 'second', '/b', '/elsewhere', 302, false );

		$this->assertSame(
			[ '/new', '/elsewhere' ],
			array_column( $this->options[ Manager::OPTION_NAME ], 'destination' )
		);
	}

	public function test_update_returns_null_for_an_unknown_id(): void {
		$this->options[ Manager::OPTION_NAME ] = [ [ 'id' => 'keep' ] + $this->legacy_item( '/a' ) ];

		$this->assertNull( Repository::update( 'missing', '/a', '/new', 301, false ) );
	}

	public function test_delete_removes_only_the_addressed_item(): void {
		$this->options[ Manager::OPTION_NAME ] = [
			[ 'id' => 'first' ] + $this->legacy_item( '/a' ),
			[ 'id' => 'second' ] + $this->legacy_item( '/b' ),
		];

		Repository::delete( 'first' );

		$this->assertSame( [ 'second' ], array_column( $this->options[ Manager::OPTION_NAME ], 'id' ) );
	}

	public function test_delete_returns_false_for_an_unknown_id(): void {
		$this->options[ Manager::OPTION_NAME ] = [ [ 'id' => 'keep' ] + $this->legacy_item( '/a' ) ];

		$this->assertFalse( Repository::delete( 'missing' ) );
	}

	public function test_frontend_matching_still_works_after_ids_are_added(): void {
		$this->options[ Manager::OPTION_NAME ] = [ $this->legacy_item( '/a' ), $this->legacy_item( '/b' ) ];
		Repository::all();

		$match = Manager::find_match( '/b/' );

		$this->assertSame( 1, $match['id'] ?? null );
	}
}
