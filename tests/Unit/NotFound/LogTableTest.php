<?php
/**
 * LogTable unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\NotFound;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\NotFound\LogTable;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\NotFound\LogTable
 */
final class LogTableTest extends MonkeyTestCase {

	use NotFoundStoreTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->install_wpdb();
	}

	public function test_name_uses_the_site_prefix(): void {
		$this->db->prefix = 'abc_';

		$this->assertSame( 'abc_lw_seo_404_log', LogTable::name() );
	}

	public function test_install_runs_dbdelta_with_unique_hash(): void {
		$sql = '';
		Functions\when( 'dbDelta' )->alias(
			static function ( string $statement ) use ( &$sql ): array {
				$sql = $statement;
				return [];
			}
		);
		Functions\when( 'get_option' )->justReturn( false );
		Functions\expect( 'update_option' )->once()->with( 'lw_seo_404_table_version', '1' );

		LogTable::install();

		$this->assertStringContainsString( 'CREATE TABLE wp_lw_seo_404_log', $sql );
		$this->assertStringContainsString( 'url_hash char(40) NOT NULL', $sql );
		$this->assertStringContainsString( 'UNIQUE KEY url_hash (url_hash)', $sql );
		$this->assertStringContainsString( 'KEY last_seen (last_seen)', $sql );
		$this->assertStringContainsString( 'PRIMARY KEY  (id)', $sql );
		$this->assertStringContainsString( 'DEFAULT CHARACTER SET utf8mb4', $sql );
	}

	public function test_install_does_not_rewrite_an_unchanged_version(): void {
		Functions\when( 'dbDelta' )->justReturn( [] );
		Functions\when( 'get_option' )->justReturn( '1' );
		Functions\expect( 'update_option' )->never();

		LogTable::install();

		$this->addToAssertionCount( 1 );
	}

	public function test_drop_removes_table_and_version(): void {
		Functions\expect( 'delete_option' )->once()->with( 'lw_seo_404_table_version' );

		LogTable::drop();

		$this->assertSame( [ 'DROP TABLE IF EXISTS wp_lw_seo_404_log' ], $this->db->queries );
	}
}
