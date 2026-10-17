<?php
/**
 * HTML sitemap Renderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\HtmlSitemap;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\HtmlSitemap\Renderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\HtmlSitemap\Renderer
 */
final class RendererTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'esc_html' )->alias( static fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_attr__' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->alias( static fn( $url ) => htmlspecialchars( (string) $url, ENT_QUOTES ) );
	}

	/**
	 * @param array<int, array{title: string, url: string, depth: int}> $items Items.
	 * @param string|null                                                $archive Archive URL.
	 * @return array<int, array<string, mixed>>
	 */
	private function sections( array $items, ?string $archive = null ): array {
		return [
			[
				'label'       => 'Pages',
				'type'        => 'post_type',
				'items'       => $items,
				'archive_url' => $archive,
			],
		];
	}

	public function test_escapes_titles_and_urls(): void {
		$html = Renderer::render(
			$this->sections( [ [ 'title' => '<script>x</script>', 'url' => 'https://e.com/?a=1&b="2"', 'depth' => 0 ] ] )
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
		$this->assertStringContainsString( 'a=1&amp;b=&quot;2&quot;', $html );
		$this->assertStringStartsWith( '<nav class="lw-html-sitemap"', $html );
		$this->assertStringContainsString( '<h2>Pages</h2>', $html );
	}

	public function test_nested_lists_for_depth(): void {
		$html = Renderer::render(
			$this->sections(
				[
					[ 'title' => 'A', 'url' => 'https://e.com/a', 'depth' => 0 ],
					[ 'title' => 'B', 'url' => 'https://e.com/b', 'depth' => 1 ],
					[ 'title' => 'C', 'url' => 'https://e.com/c', 'depth' => 2 ],
					[ 'title' => 'D', 'url' => 'https://e.com/d', 'depth' => 0 ],
				]
			)
		);

		$this->assertStringContainsString(
			'<ul><li><a href="https://e.com/a">A</a><ul><li><a href="https://e.com/b">B</a><ul><li><a href="https://e.com/c">C</a></li></ul></li></ul></li><li><a href="https://e.com/d">D</a></li></ul>',
			$html
		);
	}

	public function test_view_all_link_and_empty_sections(): void {
		$html = Renderer::render( $this->sections( [ [ 'title' => 'A', 'url' => 'https://e.com/a', 'depth' => 0 ] ], 'https://e.com/blog/' ) );
		$this->assertStringContainsString( 'href="https://e.com/blog/"', $html );

		$this->assertSame( '', Renderer::render( $this->sections( [] ) ) );
	}
}
