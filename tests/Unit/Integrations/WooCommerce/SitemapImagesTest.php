<?php
/**
 * SitemapImages unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\SitemapImages;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\SitemapImages
 */
final class SitemapImagesTest extends MonkeyTestCase {

	public function test_appends_gallery_for_products(): void {
		Functions\when( 'get_post_meta' )->justReturn( '11,12' );
		Functions\when( 'wp_get_attachment_image_url' )->alias( static fn( $id ) => 'https://example.com/g' . $id . '.jpg' );

		$post = new \WP_Post( [ 'ID' => 7, 'post_type' => 'product' ] );
		$out  = ( new SitemapImages() )->append_gallery( [ 'https://example.com/g11.jpg', 'https://example.com/f.jpg' ], $post );

		$this->assertSame( [ 'https://example.com/g11.jpg', 'https://example.com/f.jpg', 'https://example.com/g12.jpg' ], $out );
	}

	public function test_ignores_non_products(): void {
		Functions\expect( 'get_post_meta' )->never();

		$post = new \WP_Post( [ 'ID' => 8, 'post_type' => 'post' ] );

		$this->assertSame( [ 'https://example.com/a.jpg' ], ( new SitemapImages() )->append_gallery( [ 'https://example.com/a.jpg' ], $post ) );
	}

	public function test_primes_all_gallery_attachments_once_per_page(): void {
		Functions\when( 'get_post_meta' )->alias( static fn( $id ) => 1 === $id ? '11,12' : '12,13' );
		Functions\expect( '_prime_post_caches' )->once()->with( [ 11, 12, 13 ], false, true );

		$posts = [
			new \WP_Post( [ 'ID' => 1, 'post_type' => 'product' ] ),
			new \WP_Post( [ 'ID' => 2, 'post_type' => 'product' ] ),
			new \WP_Post( [ 'ID' => 3, 'post_type' => 'post' ] ),
		];

		( new SitemapImages() )->prime( $posts );

		$this->addToAssertionCount( 1 ); // The once() expectation is verified on teardown.
	}

	public function test_no_gallery_when_content_images_filter_is_false(): void {
		Filters\expectApplied( 'lw_seo_sitemap_content_images' )->once()->andReturn( false );
		Functions\expect( 'get_post_meta' )->never();

		$post = new \WP_Post( [ 'ID' => 7, 'post_type' => 'product' ] );

		$this->assertSame( [ 'https://example.com/f.jpg' ], ( new SitemapImages() )->append_gallery( [ 'https://example.com/f.jpg' ], $post ) );
	}
}
