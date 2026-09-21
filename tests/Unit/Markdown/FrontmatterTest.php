<?php
/**
 * Frontmatter unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use LightweightPlugins\SEO\Markdown\Frontmatter;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class FrontmatterTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function quote_provider(): array {
		return [
			'apostrophe stays literal' => [ "O'Brien", '"O\'Brien"' ],
			'double quote escaped'     => [ 'Say "hi"', '"Say \"hi\""' ],
			'backslash escaped'        => [ 'C:\path', '"C:\\\\path"' ],
			'entity decoded'           => [ 'It&#8217;s &amp; more', '"It’s & more"' ],
			'newline collapsed'        => [ "a\nb", '"a b"' ],
		];
	}

	/**
	 * @dataProvider quote_provider
	 *
	 * @param string $input    Raw value.
	 * @param string $expected YAML scalar.
	 */
	public function test_quote_produces_valid_double_quoted_yaml( string $input, string $expected ): void {
		$this->assertSame( $expected, Frontmatter::quote( $input ) );
	}

	public function test_build_renders_scalars_and_lists(): void {
		$yaml = Frontmatter::build(
			[
				'title' => 'Hello',
				'count' => 3,
				'draft' => false,
				'tags'  => [ 'a', "b'c" ],
				'none'  => null,
			]
		);

		$this->assertSame( "---\ntitle: \"Hello\"\ncount: 3\ndraft: false\ntags: [\"a\", \"b'c\"]\nnone: null\n---\n", $yaml );
	}
}
