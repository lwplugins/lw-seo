<?php
/**
 * HtmlToMarkdown unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Helpers;

use LightweightPlugins\SEO\Helpers\HtmlToMarkdown;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class HtmlToMarkdownTest extends MonkeyTestCase {

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function fixture_provider(): array {
		return [
			'empty' => [ '', '' ],
			'heading with link, inline emphasis' => [
				'<h2>Title <a href="https://x.test/">link</a></h2><p>Hello <strong>bold</strong> and <em>it</em>.</p>',
				"## Title [link](https://x.test/)\n\nHello **bold** and *it*.\n",
			],
			'gutenberg image figure' => [
				'<figure class="wp-block-image"><img src="https://x.test/a.jpg" alt="Alt"/><figcaption>Caption here</figcaption></figure>',
				"![Alt](https://x.test/a.jpg)\n\n*Caption here*\n",
			],
			'gutenberg table figure' => [
				'<figure class="wp-block-table"><table><thead><tr><th>A</th><th>B</th></tr></thead><tbody><tr><td>1</td><td>x|y</td></tr></tbody></table></figure>',
				"| A | B |\n| --- | --- |\n| 1 | x\\|y |\n",
			],
			'nested list' => [
				'<ul><li>One<ul><li>Nested A</li><li>Nested B</li></ul></li><li>Two</li></ul>',
				"- One\n    - Nested A\n    - Nested B\n- Two\n",
			],
			'ordered list with start' => [
				'<ol start="3"><li>a</li><li>b</li></ol>',
				"3. a\n4. b\n",
			],
			'gutenberg quote' => [
				'<blockquote class="wp-block-quote"><p>Quote p1</p><p>Quote p2</p><cite>Author</cite></blockquote>',
				"> Quote p1\n>\n> Quote p2\n>\n> Author\n",
			],
			'code block with language and backticks' => [
				'<pre class="wp-block-code"><code class="language-php">echo "```";</code></pre>',
				"````php\necho \"```\";\n````\n",
			],
			'inline code containing a backtick' => [
				'<p>Use <code>a`b</code> here</p>',
				"Use ``a`b`` here\n",
			],
			'hard break and rule' => [
				'<p>Line 1<br>Line 2</p><hr><p>After</p>',
				"Line 1\\\nLine 2\n\n---\n\nAfter\n",
			],
			'trailing break dropped' => [
				'<p>End<br></p>',
				"End\n",
			],
			'noise removed' => [
				'<nav>Menu</nav><p>Body</p><form><input type="email"><button>Send</button></form><script>x()</script><!-- note -->',
				"Body\n",
			],
			'lazy-loaded image' => [
				'<p><img src="data:image/gif;base64,AAA" data-src="https://x.test/real.jpg" alt="Lazy"></p>',
				"![Lazy](https://x.test/real.jpg)\n",
			],
			'bare inline text' => [
				'Plain <b>text</b>',
				"Plain **text**\n",
			],
			'unicode preserved' => [
				'<p>Árvíztűrő tükörfúrógép</p>',
				"Árvíztűrő tükörfúrógép\n",
			],
			'javascript link dropped'   => [ '<p><a href="javascript:alert(1)">click</a></p>', "click\n" ],
			'obfuscated scheme dropped' => [ "<p><a href=\"java\tscript:alert(1)\">x</a></p>", "x\n" ],
			'data uri iframe dropped'   => [ '<iframe src="data:text/html;base64,AAA"></iframe>', '' ],
			'angle-wrapped scheme dropped'       => [ '<p><a href="<javascript:alert(1)>">click</a></p>', "click\n" ],
			'numeric-entity-encoded scheme dropped' => [ '<p><a href="javascript&amp;#58;alert(1)">click</a></p>', "click\n" ],
			'named-entity-encoded scheme dropped' => [ '<p><a href="javascript&amp;colon;alert(1)">click</a></p>', "click\n" ],
			'backslash-escaped scheme dropped'   => [ '<p><a href="javascript\:alert(1)">click</a></p>', "click\n" ],
			'encoded data uri image dropped'     => [ '<p><img src="data&amp;#58;text/html;base64,AAA" alt="X"></p>', '' ],
			'mixed-case vbscript href dropped'   => [ '<p><a href="VBScript:alert(1)">y</a></p>', "y\n" ],
			'control-char prefixed scheme dropped' => [ "<p><a href=\"\x01javascript:alert(1)\">z</a></p>", "z\n" ],
			'text node fake link syntax escaped' => [
				'<p>[click](javascript:alert(1))</p>',
				"\\[click\\](javascript:alert(1))\n",
			],
			'anchor text cannot inject a second link' => [
				'<p><a href="https://x.test/">a](javascript:alert(1))[b</a></p>',
				"[a\\](javascript:alert(1))\\[b](https://x.test/)\n",
			],
			'iframe title cannot inject a second link' => [
				'<iframe src="https://x.test/embed" title="a](javascript:alert(1))[b"></iframe>',
				"[a\\](javascript:alert(1))\\[b](https://x.test/embed)\n",
			],
			'decoded angle brackets around a scheme are escaped' => [
				'<p>&lt;javascript:alert(1)&gt;</p>',
				"\\<javascript:alert(1)\\>\n",
			],
			'decoded angle-bracket html payload is escaped' => [
				'<p>&lt;img src=x onerror=alert(1)&gt;</p>',
				"\\<img src=x onerror=alert(1)\\>\n",
			],
			'image alt strips backslash and angle brackets' => [
				'<p><img src="https://x.test/a.jpg" alt="A\\<b>C"></p>',
				"![AbC](https://x.test/a.jpg)\n",
			],
			'whitespace inside strong stays outside the markers' => [
				'<p><strong>Note: </strong>text</p>',
				"**Note:** text\n",
			],
			'whitespace inside em stays outside the markers' => [
				'<p>a<em> b </em>c</p>',
				"a *b* c\n",
			],
			'newline-injected reference definition dropped from anchor href' => [
				'<p><a href="https://x.test/&#10;&#10;[x]&#10;&#10;[x]:javascript:alert%281%29&#10;.">click</a></p>',
				"[click](https://x.test/%0A%0A[x]%0A%0A[x]:javascript:alert%281%29%0A.)\n",
			],
			'newline-injected reference definition dropped from img src' => [
				'<p><img src="https://x.test/&#10;&#10;[x]&#10;&#10;[x]:javascript:alert%281%29&#10;." alt="X"></p>',
				"![X](https://x.test/%0A%0A[x]%0A%0A[x]:javascript:alert%281%29%0A.)\n",
			],
			'newline-injected reference definition dropped from iframe src' => [
				'<iframe src="https://x.test/&#10;&#10;[x]&#10;&#10;[x]:javascript:alert%281%29&#10;." title="Y"></iframe>',
				"[Y](https://x.test/%0A%0A[x]%0A%0A[x]:javascript:alert%281%29%0A.)\n",
			],
			'double backtick inside inline code stays contained' => [
				'<p><code>a`` &lt;img src=x onerror=alert(1)&gt; ``b</code></p>',
				"```a`` <img src=x onerror=alert(1)> ``b```\n",
			],
			'double backtick with no surrounding spaces stays contained' => [
				'<p><code>a``b</code></p>',
				"```a``b```\n",
			],
			'inline code special characters stay unescaped' => [
				'<p><code>[a]&lt;b&gt;\c</code></p>',
				"`[a]<b>\\c`\n",
			],
			'fenced code block special characters stay unescaped' => [
				'<pre>[a]&lt;b&gt;\c</pre>',
				"```\n[a]<b>\\c\n```\n",
			],
			'text backtick before a code span is escaped and separated' => [
				'<p>see `<code>&lt;img src=x onerror=alert(1)&gt;</code> here</p>',
				"see \\` `<img src=x onerror=alert(1)>` here\n",
			],
			'text backtick after a code span is escaped' => [
				'<p><code>&lt;img src=x onerror=alert(1)&gt;</code>` see</p>',
				"`<img src=x onerror=alert(1)>`\\` see\n",
			],
			'adjacent code spans stay separated' => [
				'<p><code>``</code><code>&lt;img src=x onerror=alert(1)&gt;</code></p>',
				"``` `` ``` `<img src=x onerror=alert(1)>`\n",
			],
			'plain prose backtick is escaped' => [
				'<p>a ` b</p>',
				"a \\` b\n",
			],
		];
	}

	/**
	 * @dataProvider fixture_provider
	 *
	 * @param string $html     Input HTML.
	 * @param string $expected Expected Markdown.
	 */
	public function test_converts_html_to_markdown( string $html, string $expected ): void {
		$this->assertSame( $expected, HtmlToMarkdown::convert( $html ) );
	}
}
