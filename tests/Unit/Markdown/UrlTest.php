<?php
/**
 * Markdown Url unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Markdown\Url;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class UrlTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: bool, 2: string}>
	 */
	public static function url_provider(): array {
		return [
			'pretty with slash'    => [ 'https://x.test/hello/', true, 'https://x.test/hello/md/' ],
			'pretty without slash' => [ 'https://x.test/hello', true, 'https://x.test/hello/md/' ],
			'pretty with query'    => [ 'https://x.test/hello/?lang=hu', true, 'https://x.test/hello/md/?lang=hu' ],
			'front page'           => [ 'https://x.test/', true, 'https://x.test/md/' ],
			'plain permalinks'     => [ 'https://x.test/?p=5', false, 'https://x.test/?p=5&format=md' ],
		];
	}

	/**
	 * @dataProvider url_provider
	 *
	 * @param string $permalink Permalink.
	 * @param bool   $pretty    Pretty permalinks on.
	 * @param string $expected  Markdown URL.
	 */
	public function test_from_permalink( string $permalink, bool $pretty, string $expected ): void {
		Functions\when( 'add_query_arg' )->alias(
			static fn( string $key, string $value, string $url ): string => $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . $key . '=' . $value
		);

		$this->assertSame( $expected, Url::from_permalink( $permalink, $pretty ) );
	}
}
