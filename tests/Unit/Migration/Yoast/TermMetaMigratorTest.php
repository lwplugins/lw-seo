<?php
/**
 * Tests for Yoast TermMetaMigrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\Yoast\TermMetaMigrator;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\TermMetaMigrator
 */
final class TermMetaMigratorTest extends MonkeyTestCase {

	/**
	 * Arrange the wpseo_taxonomy_meta option and empty LW term meta, capturing
	 * every update_term_meta write in a key → value map.
	 *
	 * @param array<string, mixed>  $option wpseo_taxonomy_meta contents.
	 * @param array<string, string> $writes Captured writes (by reference).
	 * @param bool                  $term_exists Whether get_term returns a term.
	 * @return void
	 */
	private function arrange( array $option, array &$writes, bool $term_exists = true ): void {
		Functions\when( 'get_option' )->justReturn( $option );
		Functions\when( 'get_term' )->justReturn( $term_exists ? (object) [ 'term_id' => 5 ] : null );
		Functions\when( 'get_term_meta' )->justReturn( '' );
		Functions\when( 'update_term_meta' )->alias(
			static function ( $id, $key, $value ) use ( &$writes ) {
				$writes[ $key ] = $value;
				return true;
			}
		);
	}

	/**
	 * Title and description are read from the option and written to LW meta.
	 *
	 * @return void
	 */
	public function test_migrates_title_and_description_from_option(): void {
		$writes = [];
		$this->arrange(
			[
				'category' => [
					5 => [
						'wpseo_title' => 'Cat Title',
						'wpseo_desc'  => 'Cat desc',
					],
				],
			],
			$writes
		);

		$counts = ( new TermMetaMigrator() )->migrate();

		$this->assertSame( 'Cat Title', $writes['_lw_seo_title'] );
		$this->assertSame( 'Cat desc', $writes['_lw_seo_description'] );
		$this->assertSame( 1, $counts['migrated'] );
	}

	/**
	 * The string wpseo_noindex value 'noindex' sets _lw_seo_noindex.
	 *
	 * @return void
	 */
	public function test_migrates_string_noindex_flag(): void {
		$writes = [];
		$this->arrange(
			[
				'category' => [
					5 => [ 'wpseo_noindex' => 'noindex' ],
				],
			],
			$writes
		);

		( new TermMetaMigrator() )->migrate();

		$this->assertSame( '1', $writes['_lw_seo_noindex'] );
	}

	/**
	 * A 'default' noindex value is not treated as noindex.
	 *
	 * @return void
	 */
	public function test_default_noindex_is_ignored(): void {
		$writes = [];
		$this->arrange(
			[
				'category' => [
					5 => [ 'wpseo_noindex' => 'default' ],
				],
			],
			$writes
		);

		( new TermMetaMigrator() )->migrate();

		$this->assertArrayNotHasKey( '_lw_seo_noindex', $writes );
	}

	/**
	 * Terms that no longer exist are skipped.
	 *
	 * @return void
	 */
	public function test_skips_missing_term(): void {
		$writes = [];
		$this->arrange(
			[
				'category' => [
					5 => [ 'wpseo_title' => 'Cat Title' ],
				],
			],
			$writes,
			false
		);

		$counts = ( new TermMetaMigrator() )->migrate();

		$this->assertSame( [], $writes );
		$this->assertSame( 0, $counts['migrated'] );
	}
}
