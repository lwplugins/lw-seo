<?php
/**
 * Activity unit tests (issue #25: no submissions from non-public or
 * non-production copies of a site).
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\IndexNow;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\IndexNow\Activity;
use LightweightPlugins\SEO\IndexNow\KeyFile;
use LightweightPlugins\SEO\IndexNow\Watcher;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class ActivityTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_active_on_a_public_production_site(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'blog_public' => '1' ] );

		$this->assertTrue( Activity::is_active() );
		$this->assertSame( '', Activity::reason() );
	}

	/**
	 * @dataProvider provide_inactive_sites
	 *
	 * @param bool   $enabled     indexnow_enabled.
	 * @param string $blog_public blog_public option.
	 * @param string $environment Environment type.
	 * @param string $reason      Expected reason.
	 */
	public function test_inactive_sites_report_the_reason( bool $enabled, string $blog_public, string $environment, string $reason ): void {
		$this->stub_options( [ 'indexnow_enabled' => $enabled ], [ 'blog_public' => $blog_public ] );
		Functions\when( 'wp_get_environment_type' )->justReturn( $environment );

		$this->assertFalse( Activity::is_active() );
		$this->assertSame( $reason, Activity::reason() );
		$this->assertNotSame( '', Activity::message( $reason ) );
	}

	/**
	 * @return array<string, array{0: bool, 1: string, 2: string, 3: string}>
	 */
	public static function provide_inactive_sites(): array {
		return [
			'switched off'              => [ false, '1', 'production', Activity::REASON_DISABLED ],
			'search engines discouraged' => [ true, '0', 'production', Activity::REASON_NOT_PUBLIC ],
			'staging'                   => [ true, '1', 'staging', Activity::REASON_ENVIRONMENT ],
			'local'                     => [ true, '1', 'local', Activity::REASON_ENVIRONMENT ],
		];
	}

	public function test_filter_can_allow_a_staging_site(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'blog_public' => '1' ] );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );
		Filters\expectApplied( 'lw_seo_indexnow_active' )->once()->with( false, Activity::REASON_ENVIRONMENT )->andReturn( true );

		$this->assertTrue( Activity::is_active() );
	}

	public function test_filter_can_stop_a_production_site(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'blog_public' => '1' ] );
		Filters\expectApplied( 'lw_seo_indexnow_active' )->once()->with( true, '' )->andReturn( false );

		$this->assertSame( Activity::REASON_FILTER, Activity::reason() );
	}

	public function test_filter_cannot_turn_on_a_switched_off_feature(): void {
		$this->stub_options( [ 'indexnow_enabled' => false ], [ 'blog_public' => '1' ] );
		Filters\expectApplied( 'lw_seo_indexnow_active' )->never();

		$this->assertSame( Activity::REASON_DISABLED, Activity::reason() );
	}

	public function test_key_file_is_not_served_while_search_engines_are_discouraged(): void {
		$key = str_repeat( 'a', 32 );
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'blog_public' => '0', 'lw_seo_indexnow_key' => $key ] );

		$this->assertNull( KeyFile::content_for( $key ) );
	}

	public function test_watcher_does_not_hook_on_a_staging_site(): void {
		$this->stub_options( [ 'indexnow_enabled' => true ], [ 'blog_public' => '1' ] );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );
		Functions\expect( 'add_action' )->never();

		new Watcher();
		$this->addToAssertionCount( 1 );
	}
}
