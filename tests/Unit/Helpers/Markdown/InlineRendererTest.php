<?php
/**
 * InlineRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Helpers\Markdown;

use LightweightPlugins\SEO\Helpers\Markdown\InlineRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class InlineRendererTest extends MonkeyTestCase {

	/**
	 * An invalid UTF-8 byte in the subject used to make the scheme-probe
	 * regex fail (with the /u modifier) and return null, which cast to ''
	 * skipped the scheme check entirely and let the raw URL through.
	 */
	public function test_url_rejects_unsafe_scheme_even_with_invalid_utf8_trailing_byte(): void {
		$this->assertSame( '', InlineRenderer::url( "javascript:alert(1)//\xff" ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public static function append_provider(): array {
		return [
			'fence after fence is separated'                 => [ '`a`', '`b`', '`a` `b`' ],
			'fence after a multi-backtick fence is separated' => [ '``` `` ```', '`b`', '``` `` ``` `b`' ],
			'fence after even backslashes then a backtick'   => [ '\\\\`', '`b`', '\\\\` `b`' ],
			'fence after an escaped text backtick is joined' => [ 'see \\`', '`b`', 'see \\``b`' ],
			'fence after an escaped backslash and backtick'  => [ '\\\\\\`', '`b`', '\\\\\\``b`' ],
			'unpaired trailing backslash is separated'       => [ 'a\\', '\\<b', 'a\\ \\<b' ],
			'unpaired backslash before a fence is separated' => [ 'a\\', '`b`', 'a\\ `b`' ],
			'escaped trailing backslash is joined'           => [ 'a\\\\', '`b`', 'a\\\\`b`' ],
			'text after a fence is joined'                   => [ '`a`', 'b', '`a`b' ],
			'empty left'                                     => [ '', '`b`', '`b`' ],
			'empty right'                                    => [ 'a\\', '', 'a\\' ],
		];
	}

	/**
	 * Joining two rendered fragments must not let the boundary change how
	 * either one parses, without adding a space where nothing can fuse.
	 *
	 * @dataProvider append_provider
	 *
	 * @param string $left     Left fragment.
	 * @param string $right    Right fragment.
	 * @param string $expected Joined Markdown.
	 */
	public function test_append_separates_only_fusing_boundaries( string $left, string $right, string $expected ): void {
		$this->assertSame( $expected, InlineRenderer::append( $left, $right ) );
	}
}
