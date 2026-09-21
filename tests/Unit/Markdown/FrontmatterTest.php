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
			'decoded tag cannot become raw html' => [ '&lt;img src=x onerror=alert(1)&gt;', '"\u003Cimg src=x onerror=alert(1)\u003E"' ],
			'raw tag cannot become raw html'     => [ '<img src=x onerror=alert(1)>', '"\u003Cimg src=x onerror=alert(1)\u003E"' ],
			'link syntax cannot become a link'   => [ '[x](javascript:alert(1))', '"\u005Bx\u005D(javascript:alert(1))"' ],
			'autolink cannot become a link'      => [ '<javascript:alert(1)>', '"\u003Cjavascript:alert(1)\u003E"' ],
			'backtick cannot open a code span'   => [ 'a`b', '"a\u0060b"' ],
			'escapes combine with yaml escapes'  => [ '<a> \\ "q"', '"\u003Ca\u003E \\\\ \\"q\\""' ],
			'c1 control becomes a space'         => [ "a\u{80}b", '"a b"' ],
			'c1 range end becomes a space'       => [ "a\u{9F}\u{9A}b", '"a b"' ],
			'nel becomes a space'                => [ "a\u{85}b", '"a b"' ],
			'yaml noncharacters become a space'  => [ "a\u{FFFE}\u{FFFF}b", '"a b"' ],
			'printable non-ascii is kept'        => [ "\u{A0}ü\u{2028}😀", "\"\u{A0}ü\u{2028}😀\"" ],
			'invalid utf-8 is substituted'       => [ "a\xffb<", "\"a\u{FFFD}b\\u003C\"" ],
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

	/**
	 * List items are quoted the same way, so a hostile tag or category
	 * can't turn the flow sequence's brackets or its items into Markdown.
	 */
	public function test_build_escapes_markdown_in_list_items(): void {
		$yaml = Frontmatter::build( [ 'tags' => [ '[t](javascript:alert(1))', '<b>' ] ] );

		$this->assertSame( "---\ntags: [\"\\u005Bt\\u005D(javascript:alert(1))\", \"\\u003Cb\\u003E\"]\n---\n", $yaml );
	}
}
