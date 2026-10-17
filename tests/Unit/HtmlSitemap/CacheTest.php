<?php
/**
 * HTML sitemap Cache unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\HtmlSitemap;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\HtmlSitemap\Cache;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\HtmlSitemap\Cache
 */
final class CacheTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'wp_set_current_user' )->justReturn( null );
	}

	public function test_remember_uses_transient(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )->once()->with( 'lw_seo_html_sitemap_abc', '<nav/>', 43200 );

		$this->assertSame( '<nav/>', Cache::remember( 'abc', static fn(): string => '<nav/>' ) );

		Functions\when( 'get_transient' )->justReturn( 'cached' );
		$this->assertSame( 'cached', Cache::remember( 'abc', static fn(): string => 'fresh' ) );
	}

	public function test_flush_increments_the_version_and_changes_the_key(): void {
		$stored = 3;
		Functions\when( 'get_option' )->alias( static function () use ( &$stored ) {
			return $stored;
		} );
		Functions\when( 'update_option' )->alias(
			static function ( string $name, $value, $autoload = null ) use ( &$stored ): bool {
				$stored = $value;
				return 'lw_seo_html_sitemap_version' === $name && true === $autoload;
			}
		);
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_locale' )->justReturn( 'en_US' );

		$before = Cache::key( [ 'max' => 5 ] );
		Cache::flush();

		$this->assertSame( 4, Cache::version() );
		$this->assertNotSame( $before, Cache::key( [ 'max' => 5 ] ) );
	}
}
