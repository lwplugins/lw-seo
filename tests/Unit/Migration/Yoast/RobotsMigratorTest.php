<?php
/**
 * Tests for Yoast RobotsMigrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\Yoast\RobotsMigrator;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\RobotsMigrator
 */
final class RobotsMigratorTest extends MonkeyTestCase {

	/**
	 * Stub get_post_meta from a key → value map.
	 *
	 * @param array<string, string> $map Meta key → value.
	 * @return void
	 */
	private function stub_post_meta( array $map ): void {
		Functions\when( 'get_post_meta' )->alias(
			static function ( $id, $key ) use ( $map ) {
				return $map[ $key ] ?? '';
			}
		);
	}

	/**
	 * Yoast noindex value '1' writes _lw_seo_noindex.
	 *
	 * @return void
	 */
	public function test_post_noindex_value_one_sets_lw_noindex(): void {
		$this->stub_post_meta(
			[
				'_yoast_wpseo_meta-robots-noindex'  => '1',
				'_yoast_wpseo_meta-robots-nofollow' => '',
			]
		);
		Functions\expect( 'update_post_meta' )->once()->with( 7, '_lw_seo_noindex', '1' );

		$result = ( new RobotsMigrator() )->migrate_post( 7 );

		$this->assertTrue( $result['migrated'] );
	}

	/**
	 * Yoast noindex value '2' means index — nothing is written.
	 *
	 * @return void
	 */
	public function test_post_noindex_value_two_is_index_and_writes_nothing(): void {
		$this->stub_post_meta(
			[
				'_yoast_wpseo_meta-robots-noindex'  => '2',
				'_yoast_wpseo_meta-robots-nofollow' => '',
			]
		);
		Functions\expect( 'update_post_meta' )->never();

		$result = ( new RobotsMigrator() )->migrate_post( 7 );

		$this->assertFalse( $result['migrated'] );
	}

	/**
	 * An existing LW noindex value is never overwritten.
	 *
	 * @return void
	 */
	public function test_does_not_overwrite_existing_lw_noindex(): void {
		$this->stub_post_meta(
			[
				'_yoast_wpseo_meta-robots-noindex'  => '1',
				'_yoast_wpseo_meta-robots-nofollow' => '',
				'_lw_seo_noindex'                   => '1',
			]
		);
		Functions\expect( 'update_post_meta' )->never();

		$result = ( new RobotsMigrator() )->migrate_post( 7 );

		$this->assertFalse( $result['migrated'] );
		$this->assertTrue( $result['target_full'] );
	}

	/**
	 * A dry run computes flags but writes nothing.
	 *
	 * @return void
	 */
	public function test_dry_run_writes_nothing(): void {
		$this->stub_post_meta(
			[
				'_yoast_wpseo_meta-robots-noindex'  => '1',
				'_yoast_wpseo_meta-robots-nofollow' => '1',
			]
		);
		Functions\expect( 'update_post_meta' )->never();

		$result = ( new RobotsMigrator( true ) )->migrate_post( 7 );

		$this->assertTrue( $result['migrated'] );
	}
}
