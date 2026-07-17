<?php
/**
 * Tests for Yoast OptionsMigrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Migration\Yoast;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Migration\Yoast\OptionsMigrator;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Migration\Yoast\OptionsMigrator
 */
final class OptionsMigratorTest extends MonkeyTestCase {

	/**
	 * Stub get_option for the three source blobs, and capture the saved LW
	 * options via a reference the test can assert against.
	 *
	 * @param array<string, mixed> $titles     wpseo_titles.
	 * @param array<string, mixed> $social     wpseo_social.
	 * @param array<string, mixed> $lw_current Current LW options.
	 * @param array<string, mixed>|null $saved  Captured saved options (by reference).
	 * @return void
	 */
	private function arrange( array $titles, array $social, array $lw_current, ?array &$saved ): void {
		Functions\when( 'get_option' )->alias(
			static function ( $key, $fallback = false ) use ( $titles, $social, $lw_current ) {
				return match ( $key ) {
					'lw_seo_options' => $lw_current,
					'wpseo_titles'   => $titles,
					'wpseo_social'   => $social,
					default          => $fallback,
				};
			}
		);
		Functions\when( 'update_option' )->alias(
			static function ( $key, $value ) use ( &$saved ) {
				$saved = $value;
				return true;
			}
		);
	}

	/**
	 * A Yoast separator token is converted to its character.
	 *
	 * @return void
	 */
	public function test_converts_separator_token(): void {
		$saved = null;
		$this->arrange( [ 'separator' => 'sc-mdash' ], [], [], $saved );

		( new OptionsMigrator() )->migrate();

		$this->assertSame( '—', $saved['separator'] );
	}

	/**
	 * The home title template is copied with variables normalized.
	 *
	 * @return void
	 */
	public function test_migrates_home_title_and_normalizes_variables(): void {
		$saved = null;
		$this->arrange( [ 'title-home-wpseo' => '%%sitename%% %%name%%' ], [], [], $saved );

		( new OptionsMigrator() )->migrate();

		$this->assertSame( '%%sitename%% %%author%%', $saved['title_home'] );
	}

	/**
	 * Per-type noindex flags are cast to booleans.
	 *
	 * @return void
	 */
	public function test_casts_noindex_flag_to_bool(): void {
		$saved = null;
		$this->arrange( [ 'noindex-post' => '1' ], [], [], $saved );

		( new OptionsMigrator() )->migrate();

		$this->assertTrue( $saved['noindex_post'] );
	}

	/**
	 * An already-set LW option is never overwritten.
	 *
	 * @return void
	 */
	public function test_does_not_overwrite_existing_option(): void {
		$saved = null;
		$this->arrange( [ 'separator' => 'sc-mdash' ], [], [ 'separator' => '|' ], $saved );

		( new OptionsMigrator() )->migrate();

		$this->assertSame( '|', $saved['separator'] );
	}

	/**
	 * A dry run never writes options.
	 *
	 * @return void
	 */
	public function test_dry_run_does_not_save(): void {
		Functions\when( 'get_option' )->alias(
			static function ( $key, $fallback = false ) {
				return 'wpseo_titles' === $key ? [ 'separator' => 'sc-mdash' ] : ( 'lw_seo_options' === $key ? [] : $fallback );
			}
		);
		Functions\expect( 'update_option' )->never();

		$result = ( new OptionsMigrator( true ) )->migrate();

		$this->assertGreaterThan( 0, $result['count'] );
	}
}
