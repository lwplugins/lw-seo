<?php
/**
 * llms.txt Document unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\LlmsTxt;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\LlmsTxt\Document;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class DocumentTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $s ): string => trim( strip_tags( $s ) ) );
	}

	public function test_renders_spec_structure_with_optional_section_last(): void {
		$expected = <<<'MD'
# Példa & Társa

> Kézműves pékség Budapesten

Rendelés online.

## Pages

- [Rólunk](https://x.test/rolunk/): Kik vagyunk

## Optional

- [XML Sitemap](https://x.test/sitemap.xml)

MD;

		$result = Document::render(
			[
				'title'    => 'Példa &amp; Társa',
				'summary'  => 'Kézműves pékség Budapesten',
				'intro'    => 'Rendelés online.',
				'sections' => [
					'Optional' => [ [ 'title' => 'XML Sitemap', 'url' => 'https://x.test/sitemap.xml' ] ],
					'Pages'    => [ [ 'title' => 'Rólunk', 'url' => 'https://x.test/rolunk/', 'description' => 'Kik vagyunk' ] ],
					'Empty'    => [],
				],
			]
		);

		$this->assertSame( $expected, $result );
	}

	public function test_omits_blockquote_when_summary_is_empty(): void {
		$this->assertSame( "# Site\n", Document::render( [ 'title' => 'Site', 'summary' => '' ] ) );
	}

	public function test_escapes_link_text_brackets_and_url_parentheses(): void {
		$result = Document::render(
			[
				'title'    => 'S',
				'sections' => [ 'Posts' => [ [ 'title' => 'Draft [v2] &#8211; beta', 'url' => 'https://x.test/a (b)/' ] ] ],
			]
		);

		$this->assertStringContainsString( '- [Draft \[v2\] – beta](https://x.test/a%20%28b%29/)', $result );
	}

	/**
	 * Author-controlled titles, SEO descriptions/excerpts and filtered
	 * permalinks that went live in CommonMark / markdown-it (html on).
	 *
	 * @return array<string, array{0: array{title: string, url: string, description?: string}, 1: string}>
	 */
	public static function hostile_link_provider(): array {
		$url = 'https://x.test/p/';

		return [
			'javascript: link in description'   => [ [ 'title' => 'T', 'url' => $url, 'description' => 'See [x](javascript:alert(document.cookie))' ], '- [T](https://x.test/p/): See \[x\](javascript:alert(document.cookie))' ],
			'entity-encoded tag in title'       => [ [ 'title' => '&lt;img src=x onerror=alert(1)&gt;', 'url' => $url ], '- [\<img src=x onerror=alert(1)\>](https://x.test/p/)' ],
			'entity-encoded tag in description' => [ [ 'title' => 'T', 'url' => $url, 'description' => '&lt;img src=x onerror=alert(1)&gt;' ], '- [T](https://x.test/p/): \<img src=x onerror=alert(1)\>' ],
			'trailing backslash in title'       => [ [ 'title' => 'x\\', 'url' => $url, 'description' => 'y' ], '- [x\\\\](https://x.test/p/): y' ],
			'javascript: autolink'              => [ [ 'title' => 'T', 'url' => $url, 'description' => '&lt;javascript:alert(1)&gt;' ], '- [T](https://x.test/p/): \<javascript:alert(1)\>' ],
			'code span around a tag in title'   => [ [ 'title' => '`x` &lt;img src=x onerror=alert(1)&gt;', 'url' => $url ], '- [\`x\` \<img src=x onerror=alert(1)\>](https://x.test/p/)' ],
			'code fence in description'         => [ [ 'title' => 'T', 'url' => $url, 'description' => '```html &lt;img src=x onerror=alert(1)&gt;' ], '- [T](https://x.test/p/): \`\`\`html \<img src=x onerror=alert(1)\>' ],
			'javascript: url'                   => [ [ 'title' => 'T', 'url' => 'javascript:alert(1)', 'description' => 'D' ], '- T: D' ],
			'entity-encoded javascript: url'    => [ [ 'title' => 'T', 'url' => 'javascript&colon;alert(1)', 'description' => 'D' ], '- T: D' ],
			'backtick and backslash in url'     => [ [ 'title' => 'T', 'url' => 'https://x.test/`a\\' ], '- [T](https://x.test/%60a%5C)' ],
		];
	}

	/**
	 * @dataProvider hostile_link_provider
	 *
	 * @param array{title: string, url: string, description?: string} $link     Link.
	 * @param string                                                   $expected Rendered list item.
	 */
	public function test_link_lines_leave_no_live_markup( array $link, string $expected ): void {
		$result = Document::render(
			[
				'title'    => 'S',
				'sections' => [ 'Posts' => [ $link ] ],
			]
		);

		$this->assertSame( "# S\n\n## Posts\n\n" . $expected . "\n", $result );
	}

	public function test_title_summary_and_section_heading_leave_no_live_markup(): void {
		$expected = <<<'MD'
# \<img src=x onerror=alert(1)\> \`x\`

> See \[x\](javascript:alert(1)) \<javascript:alert(1)\>

## \<b\>Events\</b\> \[x\]

- [T](https://x.test/p/)

MD;

		$result = Document::render(
			[
				'title'    => '&lt;img src=x onerror=alert(1)&gt; `x`',
				'summary'  => 'See [x](javascript:alert(1)) &lt;javascript:alert(1)&gt;',
				'sections' => [ '&lt;b&gt;Events&lt;/b&gt; [x]' => [ [ 'title' => 'T', 'url' => 'https://x.test/p/' ] ] ],
			]
		);

		$this->assertSame( $expected, $result );
	}
}
