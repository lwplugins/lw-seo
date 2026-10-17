<?php
/**
 * Pruner unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\NotFound;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\NotFound\LogRepository;
use LightweightPlugins\SEO\NotFound\Pruner;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\NotFound\Pruner
 */
final class PrunerTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_schedules_daily_once(): void {
		Functions\expect( 'wp_next_scheduled' )->twice()->with( 'lw_seo_404_prune' )->andReturn( false, 1234 );
		Functions\expect( 'wp_schedule_event' )->once()->with( \Mockery::type( 'int' ), 'daily', 'lw_seo_404_prune' );

		Pruner::schedule();
		Pruner::schedule();

		$this->addToAssertionCount( 1 );
	}

	public function test_unschedule_clears_the_hook(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )->once()->with( 'lw_seo_404_prune' );

		Pruner::unschedule();

		$this->addToAssertionCount( 1 );
	}

	public function test_run_prunes_with_saved_limits(): void {
		$this->stub_options( [ 'not_found_log_max_rows' => 250, 'not_found_log_retention_days' => 7 ] );
		$repo = $this->createMock( LogRepository::class );
		$repo->expects( $this->once() )->method( 'prune' )->with( 250, 7 );

		( new Pruner( $repo ) )->run();
	}

	public function test_registers_on_the_cron_hook(): void {
		$pruner = new Pruner( new LogRepository() );
		$pruner->register();

		$this->assertNotFalse( has_action( 'lw_seo_404_prune', [ $pruner, 'run' ] ) );
	}
}
