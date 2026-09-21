<?php
/**
 * llms.txt Cache unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\LlmsTxt\Cache;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class CacheTest extends MonkeyTestCase {

	public function test_returns_cached_content_without_building(): void {
		Functions\when( 'get_transient' )->justReturn( 'cached' );
		$built = false;

		$result = Cache::remember(
			'index',
			static function () use ( &$built ): string {
				$built = true;
				return 'fresh';
			}
		);

		$this->assertSame( 'cached', $result );
		$this->assertFalse( $built );
	}

	public function test_builds_and_stores_on_miss(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )->once()->with( 'lw_seo_llms_index', 'fresh', \Mockery::type( 'int' ) );

		$this->assertSame( 'fresh', Cache::remember( 'index', static fn(): string => 'fresh' ) );
	}
}
