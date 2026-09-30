<?php
/**
 * WooCommerce integration unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\Integration;
use LightweightPlugins\SEO\Integrations\WooCommerce\ProductRenderer;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

final class IntegrationTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_excludes_the_assigned_cart_checkout_and_account_pages(): void {
		$pages = [
			'cart'      => 20,
			'checkout'  => 21,
			'myaccount' => 22,
		];
		Functions\when( 'wc_get_page_id' )->alias( static fn( string $page ): int => $pages[ $page ] ?? -1 );

		$this->assertSame( [ 5, 20, 21, 22 ], ( new Integration() )->exclude_pages( [ 5 ] ) );
	}

	public function test_excludes_nothing_for_unassigned_pages(): void {
		Functions\when( 'wc_get_page_id' )->justReturn( -1 );

		$this->assertSame( [], ( new Integration() )->exclude_pages( [] ) );
	}

	public function test_renders_products_with_the_product_renderer(): void {
		$product = new \WP_Post(
			[
				'ID'        => 9,
				'post_type' => 'product',
			]
		);

		$this->assertInstanceOf( ProductRenderer::class, ( new Integration() )->markdown_renderer( null, $product ) );
	}

	public function test_leaves_other_post_types_alone(): void {
		$page = new \WP_Post(
			[
				'ID'        => 3,
				'post_type' => 'page',
			]
		);

		$this->assertNull( ( new Integration() )->markdown_renderer( null, $page ) );
	}

	/**
	 * User filters at the default priority must still receive the WooCommerce pages.
	 */
	public function test_hooks_the_exclusion_before_default_priority_filters(): void {
		$this->stub_options( [ 'woo_enabled' => false ] );
		$integration = new Integration();

		$integration->register();

		$this->assertSame( 5, has_filter( 'lw_seo_sitemap_excluded_ids', [ $integration, 'exclude_pages' ] ) );
	}

	public function test_keeps_exclusion_markdown_and_permalink_flush_when_woo_seo_is_off(): void {
		$this->stub_options( [ 'woo_enabled' => false ] );
		$integration = new Integration();

		$integration->register();

		$this->assertNotFalse( has_filter( 'lw_seo_markdown_renderer', [ $integration, 'markdown_renderer' ] ) );
		$this->assertNotFalse( has_action( 'update_option_lw_seo_options' ) );
		$this->assertFalse( has_action( 'wp_head' ) );
	}
}
