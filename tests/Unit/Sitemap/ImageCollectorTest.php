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
}
