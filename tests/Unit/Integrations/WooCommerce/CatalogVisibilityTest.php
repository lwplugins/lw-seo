<?php
/**
 * Hidden and out-of-stock product handling unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\CatalogVisibility;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\CatalogVisibility
 */
final class CatalogVisibilityTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @param array<int, string> $terms        product_visibility terms the product has.
	 * @param string             $stock_status _stock_status meta.
	 */
	private function product( array $terms, string $stock_status = 'instock' ): \WP_Post {
		Functions\when( 'has_term' )->alias(
			static fn( $term, $taxonomy, $post ) => 'product_visibility' === $taxonomy && in_array( $term, $terms, true )
		);
		Functions\when( 'get_post_meta' )->justReturn( $stock_status );

		return new \WP_Post( [ 'ID' => 5, 'post_type' => 'product' ] );
	}

	public function test_hidden_excluded(): void {
		$this->stub_options();
		$post = $this->product( [ 'exclude-from-catalog', 'exclude-from-search' ] );

		$this->assertFalse( ( new CatalogVisibility() )->filter_eligible( true, $post ) );
	}

	public function test_hidden_kept_when_option_off(): void {
		$this->stub_options( [ 'woo_sitemap_exclude_hidden' => false ] );
		$post = $this->product( [ 'exclude-from-catalog', 'exclude-from-search' ] );

		$this->assertTrue( ( new CatalogVisibility() )->filter_eligible( true, $post ) );
	}

	public function test_search_only_kept(): void {
		$this->stub_options();

		$this->assertTrue( ( new CatalogVisibility() )->filter_eligible( true, $this->product( [ 'exclude-from-catalog' ] ) ) );
		$this->assertTrue( ( new CatalogVisibility() )->filter_eligible( true, $this->product( [ 'exclude-from-search' ] ) ) );
	}

	public function test_outofstock_only_when_enabled(): void {
		$this->stub_options();
		$post = $this->product( [], 'outofstock' );
		$this->assertTrue( ( new CatalogVisibility() )->filter_eligible( true, $post ) );

		$this->stub_options( [ 'woo_sitemap_exclude_outofstock' => true ] );
		$this->assertFalse( ( new CatalogVisibility() )->filter_eligible( true, $post ) );
	}

	public function test_other_post_types_and_earlier_false_untouched(): void {
		$this->stub_options( [ 'woo_sitemap_exclude_outofstock' => true ] );
		$page = new \WP_Post( [ 'ID' => 6, 'post_type' => 'page' ] );

		$this->assertTrue( ( new CatalogVisibility() )->filter_eligible( true, $page ) );
		$this->assertFalse( ( new CatalogVisibility() )->filter_eligible( false, $this->product( [] ) ) );
	}

	public function test_noindex_for_hidden(): void {
		$this->stub_options();
		$hidden = $this->product( [ 'exclude-from-catalog', 'exclude-from-search' ] );
		$this->assertTrue( ( new CatalogVisibility() )->filter_noindex( false, $hidden ) );

		$visible = $this->product( [ 'exclude-from-search' ] );
		$this->assertFalse( ( new CatalogVisibility() )->filter_noindex( false, $visible ) );

		$this->stub_options( [ 'woo_noindex_hidden' => false ] );
		$this->assertFalse( ( new CatalogVisibility() )->filter_noindex( false, $hidden ) );
	}
}
