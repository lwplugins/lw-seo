<?php
/**
 * Activator unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Activator;
use LightweightPlugins\SEO\Options;

final class ActivatorTest extends MonkeyTestCase {

	/** @var array<int, string> */
	private array $rules = [];

	/** @var array<int, string> */
	private array $endpoints = [];

	private int $flushes = 0;

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'EP_ALL' ) ) {
			define( 'EP_ALL', 8191 );
		}

		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}

		$this->rules     = [];
		$this->endpoints = [];
		$this->flushes   = 0;

		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ) => array_merge( $defaults, $args ) );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'add_rewrite_rule' )->alias( function ( string $regex ): void {
			$this->rules[] = $regex;
		} );
		Functions\when( 'add_rewrite_endpoint' )->alias( function ( string $name ): void {
			$this->endpoints[] = $name;
		} );
		$GLOBALS['wpdb'] = new \wpdb();
		Functions\when( 'dbDelta' )->justReturn( [] );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'wp_next_scheduled' )->justReturn( 1234 );
		Functions\when( 'flush_rewrite_rules' )->alias( function (): void {
			++$this->flushes;
		} );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_activate_registers_every_enabled_endpoint_and_flushes_once(): void {
		Functions\when( 'get_option' )->justReturn( [] ); // All defaults → everything enabled.
		Options::clear_cache();

		Activator::activate();

		$this->assertContains( '^sitemap\.xml$', $this->rules, 'sitemap index rule missing' );
		$this->assertContains( '^sitemap-([a-z0-9_-]+)\.xml$', $this->rules, 'sitemap sub-rule missing' );
		$this->assertContains( '^sitemap-([a-z0-9_-]+?)-(\d+)\.xml$', $this->rules, 'paged sitemap rule missing' );
		$this->assertContains( '^llms\.txt$', $this->rules, 'llms.txt rule missing' );
		$this->assertNotContains( '^llms-full\.txt$', $this->rules, 'llms-full.txt is opt-in' );
		$this->assertSame( [ 'md', 'markdown' ], $this->endpoints );
		$this->assertSame( 1, $this->flushes, 'rewrite rules must be flushed exactly once' );
	}

	public function test_activate_skips_rules_of_disabled_features(): void {
		Functions\when( 'get_option' )->justReturn(
			[
				'sitemap_enabled' => false,
			]
		);
		Options::clear_cache();

		Activator::activate();

		$this->assertNotContains( '^sitemap\.xml$', $this->rules );
		$this->assertContains( '^llms\.txt$', $this->rules );
		$this->assertSame( 1, $this->flushes );
	}

	public function test_activate_installs_the_404_table_and_schedules_the_prune(): void {
		Functions\when( 'get_option' )->justReturn( [] );
		Options::clear_cache();
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\expect( 'wp_schedule_event' )->once()->with( \Mockery::type( 'int' ), 'daily', 'lw_seo_404_prune' );

		Activator::activate();

		$this->assertSame( 1, $this->flushes );
	}

	public function test_deactivate_clears_the_prune_and_indexnow_events(): void {
		Functions\when( 'delete_option' )->justReturn( true );
		$cleared = [];
		Functions\when( 'wp_clear_scheduled_hook' )->alias(
			static function ( string $hook ) use ( &$cleared ): int {
				$cleared[] = $hook;
				return 1;
			}
		);

		Activator::deactivate();

		$this->assertSame( [ 'lw_seo_404_prune', 'lw_seo_indexnow_flush' ], $cleared );
	}

	public function test_deactivate_drops_stored_rules_instead_of_regenerating_them(): void {
		Functions\expect( 'delete_option' )->once()->with( 'rewrite_rules' );
		Functions\when( 'wp_clear_scheduled_hook' )->justReturn( 1 );

		Activator::deactivate();

		$this->assertSame( [], $this->rules, 'deactivation must not register rules' );
		$this->assertSame( 0, $this->flushes, 'a flush would re-add the rules registered on init' );
	}
}
