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
		$this->assertContains( '^sitemap-([a-z_]+)\.xml$', $this->rules, 'sitemap sub-rule missing' );
		$this->assertContains( '^robots\.txt$', $this->rules, 'robots.txt rule missing' );
		$this->assertContains( '^llms\.txt$', $this->rules, 'llms.txt rule missing' );
		$this->assertSame( [ 'md', 'markdown' ], $this->endpoints );
		$this->assertSame( 1, $this->flushes, 'rewrite rules must be flushed exactly once' );
	}

	public function test_activate_skips_rules_of_disabled_features(): void {
		Functions\when( 'get_option' )->justReturn(
			[
				'sitemap_enabled'    => false,
				'robots_txt_enabled' => false,
			]
		);
		Options::clear_cache();

		Activator::activate();

		$this->assertNotContains( '^sitemap\.xml$', $this->rules );
		$this->assertNotContains( '^robots\.txt$', $this->rules );
		$this->assertContains( '^llms\.txt$', $this->rules );
		$this->assertSame( 1, $this->flushes );
	}

	public function test_deactivate_drops_stored_rules_instead_of_regenerating_them(): void {
		Functions\expect( 'delete_option' )->once()->with( 'rewrite_rules' );

		Activator::deactivate();

		$this->assertSame( [], $this->rules, 'deactivation must not register rules' );
		$this->assertSame( 0, $this->flushes, 'a flush would re-add the rules registered on init' );
	}
}
