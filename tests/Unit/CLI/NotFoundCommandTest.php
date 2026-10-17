<?php
/**
 * NotFoundCommand unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\CLI;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\CLI\NotFoundCommand;
use LightweightPlugins\SEO\NotFound\LogRepository;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;
use Mockery;

/**
 * @covers \LightweightPlugins\SEO\CLI\NotFoundCommand
 */
final class NotFoundCommandTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		\WP_CLI::$log = [];
	}

	public function test_list_outputs_rows(): void {
		$item       = [ 'id' => 3, 'url' => '/caf%C3%A9', 'hits' => 4, 'referrer' => '', 'user_agent' => 'x', 'first_seen' => 'a', 'last_seen' => '2026-10-17 10:00:00' ];
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'paginate' )->once()->with( '', 'hits', 'desc', 1, 100 )->andReturn( [ 'items' => [ $item ], 'total' => 1 ] );

		$captured = null;
		Functions\expect( 'WP_CLI\Utils\format_items' )->once()->andReturnUsing(
			static function ( string $format, array $rows, array $fields ) use ( &$captured ): void {
				$captured = [ $format, $rows, $fields ];
			}
		);

		( new NotFoundCommand( $repository ) )->list( [], [ 'orderby' => 'hits', 'limit' => '500', 'format' => 'json' ] );

		$this->assertSame( 'json', $captured[0] );
		$this->assertSame(
			[ [ 'id' => '3', 'url' => '/caf%C3%A9', 'hits' => '4', 'referrer' => '', 'last_seen' => '2026-10-17 10:00:00' ] ],
			$captured[1]
		);
		$this->assertSame( [ 'id', 'url', 'hits', 'referrer', 'last_seen' ], $captured[2] );
	}

	public function test_clear_calls_repository(): void {
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'clear' )->once();

		( new NotFoundCommand( $repository ) )->clear( [], [ 'yes' => true ] );

		$this->assertSame( [ 'confirm: Delete every logged 404 URL?', 'success: 404 log cleared.' ], \WP_CLI::$log );
	}

	public function test_prune_uses_saved_limits(): void {
		$this->stub_options( [ 'not_found_log_max_rows' => 500 ] );
		Functions\when( 'absint' )->alias( static fn( $value ) => abs( (int) $value ) );
		$repository = Mockery::mock( LogRepository::class );
		$repository->shouldReceive( 'prune' )->once()->with( 500, 30 )->andReturn( 5 );

		( new NotFoundCommand( $repository ) )->prune( [], [] );

		$this->assertSame( [ 'success: Pruned 5 row(s).' ], \WP_CLI::$log );
	}
}
