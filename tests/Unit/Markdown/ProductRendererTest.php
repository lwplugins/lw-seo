<?php
/**
 * ProductRenderer unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Markdown;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Markdown\ProductRenderer;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class ProductRendererTest extends MonkeyTestCase {

	/**
	 * The product title is plain text in the heading.
	 */
	public function test_body_heading_escapes_title(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wc_get_product' )->justReturn( false );
		Functions\when( 'get_the_title' )->justReturn( '<b>Mug</b> [x](javascript:alert(1))' );
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( string $text ): string => trim( strip_tags( $text ) ) );

		$post = new \WP_Post(
			[
				'ID'           => 9,
				'post_excerpt' => '',
				'post_content' => '',
			]
		);

		$this->assertSame( "# Mug \\[x\\](javascript:alert(1))\n\n", ( new ProductRenderer( $post ) )->body() );
	}
}
