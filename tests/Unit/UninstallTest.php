<?php
/**
 * uninstall.php unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit;

use Brain\Monkey\Functions;

final class UninstallTest extends MonkeyTestCase {

	private const OPTIONS = [
		'lw_seo_indexnow_key',
		'lw_seo_indexnow_queue',
		'lw_seo_indexnow_log',
		'lw_seo_html_sitemap_version',
	];

	/** @var array<int, string> "site:option" pairs. */
	private array $deleted = [];

	/** @var array<int, string> "site:hook" pairs. */
	private array $cleared = [];

	private int $site = 1;

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'lw-seo/lw-seo.php' );
		}

		$this->deleted   = [];
		$this->cleared   = [];
		$this->site      = 1;
		$GLOBALS['wpdb'] = new \wpdb();

		Functions\when( 'delete_option' )->alias(
			function ( string $name ): bool {
				$this->deleted[] = $this->site . ':' . $name;
				return true;
			}
		);
		Functions\when( 'wp_clear_scheduled_hook' )->alias(
			function ( string $hook ): int {
				$this->cleared[] = $this->site . ':' . $hook;
				return 1;
			}
		);
	}

	private function run_uninstall(): void {
		require dirname( __DIR__, 2 ) . '/uninstall.php';
	}

	public function test_single_site_drops_table_options_and_events(): void {
		Functions\when( 'is_multisite' )->justReturn( false );

		$this->run_uninstall();

		$this->assertSame( [ 'DROP TABLE IF EXISTS wp_lw_seo_404_log' ], $GLOBALS['wpdb']->queries );
		foreach ( self::OPTIONS as $option ) {
			$this->assertContains( '1:' . $option, $this->deleted );
		}
		$this->assertSame( [ '1:lw_seo_404_prune', '1:lw_seo_indexnow_flush' ], $this->cleared );
	}

	public function test_multisite_cleans_every_site(): void {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\expect( 'get_sites' )->once()->with( [ 'fields' => 'ids', 'number' => 0 ] )->andReturn( [ 1, 2 ] );
		Functions\when( 'switch_to_blog' )->alias(
			function ( int $id ): bool {
				$this->site               = $id;
				$GLOBALS['wpdb']->prefix = 1 === $id ? 'wp_' : 'wp_' . $id . '_';
				return true;
			}
		);
		Functions\when( 'restore_current_blog' )->alias(
			function (): bool {
				$this->site               = 1;
				$GLOBALS['wpdb']->prefix = 'wp_';
				return true;
			}
		);

		$this->run_uninstall();

		$this->assertSame(
			[ 'DROP TABLE IF EXISTS wp_lw_seo_404_log', 'DROP TABLE IF EXISTS wp_2_lw_seo_404_log' ],
			$GLOBALS['wpdb']->queries
		);
		foreach ( [ 1, 2 ] as $site ) {
			foreach ( self::OPTIONS as $option ) {
				$this->assertContains( $site . ':' . $option, $this->deleted );
			}
			$this->assertContains( $site . ':lw_seo_indexnow_flush', $this->cleared );
			$this->assertContains( $site . ':lw_seo_404_prune', $this->cleared );
		}
	}
}
