<?php
/**
 * SitemapImages unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\SitemapImages;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\SitemapImages
 */
final class SitemapImagesTest extends MonkeyTestCase {

	public function test_appends_gallery_for_products(): void {
		Functions\when( 'wc_get_product' )->justReturn( new \WC_Product( 7, 0, 0.0, [ 'gallery_image_ids' => [ 11, 12 ] ] ) );
		Functions\when( 'wp_get_attachment_image_url' )->alias( static fn( $id ) => 'https://example.com/g' . $id . '.jpg' );

		$post = new \WP_Post( [ 'ID' => 7, 'post_type' => 'product' ] );
		$out  = ( new SitemapImages() )->append_gallery( [ 'https://example.com/g11.jpg', 'https://example.com/f.jpg' ], $post );

		$this->assertSame( [ 'https://example.com/g11.jpg', 'https://example.com/f.jpg', 'https://example.com/g12.jpg' ], $out );
	}

	public function test_ignores_non_products(): void {
		Functions\expect( 'wc_get_product' )->never();

		$post = new \WP_Post( [ 'ID' => 8, 'post_type' => 'post' ] );

		$this->assertSame( [ 'https://example.com/a.jpg' ], ( new SitemapImages() )->append_gallery( [ 'https://example.com/a.jpg' ], $post ) );
	}
}
