<?php
/**
 * UrlRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Sitemap;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\UrlRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Sitemap\UrlRenderer
 */
final class UrlRendererTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_options( [ 'sitemap_stylesheet' => false ] );
		Functions\when( 'esc_url' )->alias( static fn( $url ) => str_replace( '&', '&amp;', (string) $url ) );
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.com' . $path );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_urlset_has_image_namespace_only_with_images(): void {
		$items = [ [ 'loc' => 'https://example.com/a/' ] ];

		$this->assertStringContainsString( 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"', UrlRenderer::urlset( $items, true ) );
		$this->assertStringNotContainsString( 'xmlns:image', UrlRenderer::urlset( $items, false ) );
	}

	public function test_image_nodes_rendered_and_escaped(): void {
		$items = [
			[
				'loc'    => 'https://example.com/a/',
				'images' => [ 'https://example.com/a.jpg?x=1&y=2' ],
			],
		];

		$xml = UrlRenderer::urlset( $items, true );

		$this->assertStringContainsString( '<image:image><image:loc>https://example.com/a.jpg?x=1&amp;y=2</image:loc></image:image>', $xml );
	}

	public function test_images_ignored_when_disabled(): void {
		$items = [
			[
				'loc'    => 'https://example.com/a/',
				'images' => [ 'https://example.com/a.jpg' ],
			],
		];

		$this->assertStringNotContainsString( 'image:image', UrlRenderer::urlset( $items, false ) );
	}

	public function test_stylesheet_pi_present_when_enabled(): void {
		$this->stub_options( [ 'sitemap_stylesheet' => true ] );

		$this->assertSame(
			'<?xml-stylesheet type="text/xsl" href="https://example.com/sitemap.xsl"?>',
			UrlRenderer::stylesheet_pi()
		);
		$this->assertStringContainsString( '<?xml-stylesheet', UrlRenderer::index( [ [ 'loc' => 'https://example.com/sitemap-post.xml' ] ] ) );
	}

	public function test_no_stylesheet_pi_when_disabled(): void {
		$this->stub_options( [ 'sitemap_stylesheet' => false ] );

		$this->assertSame( '', UrlRenderer::stylesheet_pi() );
		$this->assertStringNotContainsString( 'xml-stylesheet', UrlRenderer::urlset( [], false ) );
	}
}
