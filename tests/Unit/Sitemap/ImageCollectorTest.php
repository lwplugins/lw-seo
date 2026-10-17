<?php
/**
 * ImageCollector unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Sitemap;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Sitemap\ImageCollector;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Sitemap\ImageCollector
 */
final class ImageCollectorTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.com' . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( $url, $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
		Functions\when( 'has_blocks' )->justReturn( false );
		Functions\when( 'strip_shortcodes' )->returnArg();
	}

	private function post( string $content ): \WP_Post {
		return new \WP_Post(
			[
				'ID'           => 5,
				'post_content' => $content,
			]
		);
	}

	public function test_featured_image_first(): void {
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.com/feat.jpg' );

		$urls = ImageCollector::for_post( $this->post( '<img src="https://example.com/inline.jpg">' ) );

		$this->assertSame( [ 'https://example.com/feat.jpg', 'https://example.com/inline.jpg' ], $urls );
	}

	public function test_content_images_same_host_only(): void {
		$urls = ImageCollector::for_post(
			$this->post( '<img src="https://cdn.other.com/a.jpg"><img class="x" src=\'https://example.com/b.jpg\'>' )
		);

		$this->assertSame( [ 'https://example.com/b.jpg' ], $urls );
	}

	public function test_relative_src_made_absolute(): void {
		$urls = ImageCollector::for_post(
			$this->post( '<img src="/wp-content/uploads/a.jpg"><img src="//example.com/b.jpg">' )
		);

		$this->assertSame( [ 'https://example.com/wp-content/uploads/a.jpg', 'https://example.com/b.jpg' ], $urls );
	}

	public function test_dedupes(): void {
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.com/a.jpg' );

		$urls = ImageCollector::for_post( $this->post( '<img src="https://example.com/a.jpg"><img src="https://example.com/a.jpg">' ) );

		$this->assertSame( [ 'https://example.com/a.jpg' ], $urls );
	}

	public function test_filter_applied(): void {
		$post = $this->post( '' );
		Filters\expectApplied( 'lw_seo_sitemap_images' )
			->once()
			->with( [], $post )
			->andReturn( [ 'https://example.com/extra.jpg' ] );

		$this->assertSame( [ 'https://example.com/extra.jpg' ], ImageCollector::for_post( $post ) );
	}

	public function test_shortcode_enclosed_image_not_listed(): void {
		Functions\when( 'strip_shortcodes' )->alias( static fn( $c ) => preg_replace( '#\[member\].*?\[/member\]#s', '', $c ) );

		$urls = ImageCollector::for_post( $this->post( '[member]<img src="https://example.com/secret.jpg">[/member]<img src="https://example.com/open.jpg">' ) );

		$this->assertSame( [ 'https://example.com/open.jpg' ], $urls );
	}

	public function test_image_in_non_allowlisted_block_not_listed(): void {
		Functions\when( 'has_blocks' )->justReturn( true );
		Functions\when( 'parse_blocks' )->justReturn(
			[
				[ 'blockName' => 'acme/members-only', 'innerHTML' => '<img src="https://example.com/secret.jpg">', 'innerBlocks' => [] ],
				[ 'blockName' => 'core/group', 'innerHTML' => '', 'innerBlocks' => [
					[ 'blockName' => 'core/image', 'innerHTML' => '<img src="https://example.com/nested.jpg">', 'innerBlocks' => [] ],
				] ],
			]
		);

		$this->assertSame( [], ImageCollector::for_post( $this->post( '<!-- wp:x -->' ) ) );
	}

	public function test_image_in_core_image_block_listed(): void {
		Functions\when( 'has_blocks' )->justReturn( true );
		Functions\when( 'parse_blocks' )->justReturn(
			[
				[ 'blockName' => 'core/image', 'innerHTML' => '<img src="https://example.com/a.jpg">', 'innerBlocks' => [] ],
				[ 'blockName' => null, 'innerHTML' => '<img src="https://example.com/classic.jpg">', 'innerBlocks' => [] ],
			]
		);

		$this->assertSame(
			[ 'https://example.com/a.jpg', 'https://example.com/classic.jpg' ],
			ImageCollector::for_post( $this->post( '<!-- wp:x -->' ) )
		);
	}

	public function test_content_filter_false_lists_featured_only(): void {
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.com/feat.jpg' );
		Filters\expectApplied( 'lw_seo_sitemap_content_images' )->once()->andReturn( false );

		$urls = ImageCollector::for_post( $this->post( '<img src="https://example.com/inline.jpg">' ) );

		$this->assertSame( [ 'https://example.com/feat.jpg' ], $urls );
	}
}
