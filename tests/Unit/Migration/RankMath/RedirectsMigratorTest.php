<?php
/**
 * Tests for the Rank Math redirect importer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\RankMath;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\RankMath\RedirectsMigrator;
use LightweightPlugins\SEO\Tests\Unit\Migration\MigrationStoreTrait;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use Mockery;

/**
 * @covers \LightweightPlugins\SEO\Migration\RankMath\RedirectsMigrator
 */
final class RedirectsMigratorTest extends MonkeyTestCase {

	use MigrationStoreTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_store();
		if ( ! defined( 'ARRAY_A' ) ) {
			define( 'ARRAY_A', 'ARRAY_A' );
		}
		Functions\when( 'home_url' )->justReturn( 'https://example.test' );
		Functions\when( 'current_time' )->justReturn( '2026-09-27 10:00:00' );
		Functions\when( 'wp_generate_uuid4' )->justReturn( 'uuid' );
		Functions\when( 'maybe_unserialize' )->alias( static fn( string $value ) => unserialize( $value ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Test double of maybe_unserialize().

		$rows = [
			[
				'id'          => 1,
				'sources'     => serialize( [ [ 'pattern' => 'old-page', 'comparison' => 'exact' ], [ 'pattern' => 'shop', 'comparison' => 'start' ] ] ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Rank Math stores sources serialized.
				'url_to'      => 'https://example.test/new/',
				'header_code' => 301,
				'status'      => 'active',
			],
		];

		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'SHOW TABLES' );
		$wpdb->shouldReceive( 'get_var' )->andReturn( 'wp_rank_math_redirections' );
		$wpdb->shouldReceive( 'get_results' )->andReturn( $rows );

		$GLOBALS['wpdb'] = $wpdb;
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	public function test_imports_every_source_of_a_row(): void {
		$result = ( new RedirectsMigrator() )->migrate();

		$this->assertSame( 2, $result['migrated'] );
		$this->assertSame( [ '/old-page', '^shop' ], array_column( $this->options['lw_seo_redirects'], 'source' ) );
	}

	public function test_running_the_import_again_adds_no_duplicates(): void {
		( new RedirectsMigrator() )->migrate();

		$result = ( new RedirectsMigrator() )->migrate();

		$this->assertCount( 2, $this->options['lw_seo_redirects'] );
		$this->assertSame( [ 0, 2, 2 ], [ $result['migrated'], $result['skipped'], $result['skipped_already_present'] ] );
	}
}
