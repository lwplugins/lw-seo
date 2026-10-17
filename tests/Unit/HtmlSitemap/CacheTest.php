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
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\expect( 'set_transient' )->once()->with( 'lw_seo_html_sitemap_abc', '<nav/>', 43200 );

		$this->assertSame( '<nav/>', Cache::remember( 'abc', static fn(): string => '<nav/>' ) );

		Functions\when( 'get_transient' )->justReturn( 'cached' );
		$this->assertSame( 'cached', Cache::remember( 'abc', static fn(): string => 'fresh' ) );
	}

	public function test_flush_deletes_known_keys(): void {
		Functions\when( 'get_option' )->justReturn( [ 'abc', 'def' ] );
		$deleted = [];
		Functions\when( 'delete_transient' )->alias(
			static function ( string $key ) use ( &$deleted ): bool {
				$deleted[] = $key;
				return true;
			}
		);
		Functions\expect( 'delete_option' )->once();

		Cache::flush();

		$this->assertSame( [ 'lw_seo_html_sitemap_abc', 'lw_seo_html_sitemap_def' ], $deleted );
	}
}
