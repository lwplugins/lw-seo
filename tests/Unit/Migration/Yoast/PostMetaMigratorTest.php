<?php
/**
 * Tests for Yoast PostMetaMigrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\Yoast\PostMetaMigrator;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use Mockery;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\PostMetaMigrator
 */
final class PostMetaMigratorTest extends MonkeyTestCase {

	/**
	 * Point the global $wpdb at a mock returning a single post id.
	 *
	 * @return void
	 */
	private function stub_wpdb(): void {
		$wpdb           = Mockery::mock();
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->shouldReceive( 'get_col' )->andReturn( [ '7' ] );
		$GLOBALS['wpdb'] = $wpdb;
	}

	/**
	 * Mapped Yoast post fields are written to LW SEO meta.
	 *
	 * @return void
	 */
	public function test_migrates_mapped_fields(): void {
		$this->stub_wpdb();
		Functions\when( 'get_post' )->justReturn( (object) [ 'ID' => 7 ] );

		$source = [
			'_yoast_wpseo_title'    => 'Yoast Title',
			'_yoast_wpseo_metadesc' => 'Yoast description',
		];
		Functions\when( 'get_post_meta' )->alias(
			static function ( $id, $key ) use ( $source ) {
				return $source[ $key ] ?? '';
			}
		);

		$writes = [];
		Functions\when( 'update_post_meta' )->alias(
			static function ( $id, $key, $value ) use ( &$writes ) {
				$writes[ $key ] = $value;
				return true;
			}
		);

		$counts = ( new PostMetaMigrator() )->migrate();

		$this->assertSame( 'Yoast Title', $writes['_lw_seo_title'] );
		$this->assertSame( 'Yoast description', $writes['_lw_seo_description'] );
		$this->assertSame( 1, $counts['migrated'] );
	}

	/**
	 * A filled LW target is never overwritten and counts as already-present.
	 *
	 * @return void
	 */
	public function test_does_not_overwrite_existing_target(): void {
		$this->stub_wpdb();
		Functions\when( 'get_post' )->justReturn( (object) [ 'ID' => 7 ] );

		Functions\when( 'get_post_meta' )->alias(
			static function ( $id, $key ) {
				return match ( $key ) {
					'_yoast_wpseo_title' => 'Yoast Title',
					'_lw_seo_title'      => 'Existing LW Title',
					default              => '',
				};
			}
		);
		Functions\expect( 'update_post_meta' )->never();

		$counts = ( new PostMetaMigrator() )->migrate();

		$this->assertSame( 1, $counts['skipped_already_present'] );
		$this->assertSame( 0, $counts['migrated'] );
	}
}
