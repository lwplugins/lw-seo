<?php
/**
 * Sitemap routing unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Sitemap;

use LightweightPlugins\SEO\Sitemap\Sitemap;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class SitemapTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: int, 2: array{0: string, 1: int}|null}>
	 */
	public static function target_provider(): array {
		return [
			'unpaged'                    => [ 'post', 0, [ 'post', 1 ] ],
			'paged'                      => [ 'post', 2, [ 'post', 2 ] ],
			'hyphenated type'            => [ 'case-study', 0, [ 'case-study', 1 ] ],
			'hyphenated type paged'      => [ 'case-study', 3, [ 'case-study', 3 ] ],
			'type ending in -<digits>'   => [ 'top', 10, [ 'top-10', 1 ] ],
			'unknown'                    => [ 'nope', 0, null ],
		];
	}

	/**
	 * @dataProvider target_provider
	 *
	 * @param string                       $name     Matched name.
	 * @param int                          $page     Matched page (0 = none).
	 * @param array{0: string, 1: int}|null $expected Resolved target.
	 */
	public function test_resolve_target( string $name, int $page, ?array $expected ): void {
		$this->assertSame( $expected, Sitemap::resolve_target( $name, $page, [ 'post', 'case-study', 'top-10' ] ) );
	}
}
