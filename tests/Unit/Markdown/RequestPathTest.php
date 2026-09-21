<?php
/**
 * RequestPath unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use LightweightPlugins\SEO\Markdown\RequestPath;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class RequestPathTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function relative_provider(): array {
		return [
			'root install'         => [ '/hello/md/', '', 'hello/md' ],
			'subdirectory install' => [ '/blog/hello/md', '/blog', 'hello/md' ],
			'subdirectory root'    => [ '/blog/', '/blog/', '' ],
			'prefix lookalike'     => [ '/blogger/x', '/blog', 'blogger/x' ],
		];
	}

	/**
	 * @dataProvider relative_provider
	 *
	 * @param string $request  Request path.
	 * @param string $home     Home path.
	 * @param string $expected Relative path.
	 */
	public function test_relative( string $request, string $home, string $expected ): void {
		$this->assertSame( $expected, RequestPath::relative( $request, $home ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool, 2: string}>
	 */
	public static function suffix_provider(): array {
		return [
			'post'          => [ 'hello/md', true, 'hello' ],
			'nested page'   => [ 'about/team/markdown', true, 'about/team' ],
			'front page'    => [ 'md', true, '' ],
			'no suffix'     => [ 'hello', false, 'hello' ],
			'slug ends md'  => [ 'cmd', false, 'cmd' ],
		];
	}

	/**
	 * @dataProvider suffix_provider
	 *
	 * @param string $path       Relative path.
	 * @param bool   $has_suffix Expected has_suffix().
	 * @param string $stripped   Expected strip().
	 */
	public function test_suffix_detection_and_strip( string $path, bool $has_suffix, string $stripped ): void {
		$this->assertSame( $has_suffix, RequestPath::has_suffix( $path ) );
		$this->assertSame( $stripped, RequestPath::strip( $path ) );
	}
}
