<?php
/**
 * WooCommerce product Open Graph unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use LightweightPlugins\SEO\Integrations\WooCommerce\OpenGraph;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;

final class OpenGraphTest extends MonkeyTestCase {

	private function post( string $post_type ): \WP_Post {
		return new \WP_Post(
			[
				'ID'        => 9,
				'post_type' => $post_type,
			]
		);
	}

	public function test_products_are_og_type_product(): void {
		$this->assertSame( 'product', ( new OpenGraph() )->og_type( 'article', $this->post( 'product' ) ) );
	}

	public function test_other_post_types_keep_their_og_type(): void {
		$this->assertSame( 'article', ( new OpenGraph() )->og_type( 'article', $this->post( 'page' ) ) );
	}

	public function test_hooks_the_og_type_filter(): void {
		$open_graph = new OpenGraph();

		$this->assertSame( 10, has_filter( 'lw_seo_og_type', [ $open_graph, 'og_type' ] ) );
	}
}
