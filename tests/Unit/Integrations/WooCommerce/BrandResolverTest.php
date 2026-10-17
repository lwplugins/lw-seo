<?php
/**
 * Product brand resolver unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\BrandResolver;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\BrandResolver
 */
final class BrandResolverTest extends MonkeyTestCase {

	use OptionsStubTrait;

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $props Product props.
	 */
	private function product( array $props = [] ): \WC_Product {
		return new \WC_Product( 5, 0, 0.0, $props );
	}

	public function test_attribute_first(): void {
		$this->stub_options( [ 'woo_default_brand' => 'Shop' ] );
		Functions\when( 'get_the_terms' )->justReturn( [ (object) [ 'name' => 'Term' ] ] );

		$this->assertSame( 'Attr', BrandResolver::for_product( $this->product( [ 'attribute_brand' => 'Attr' ] ) ) );
	}

	public function test_taxonomy_second(): void {
		$this->stub_options( [ 'woo_default_brand' => 'Shop' ] );
		Functions\when( 'get_the_terms' )->justReturn( [ (object) [ 'name' => 'Term' ] ] );

		$this->assertSame( 'Term', BrandResolver::for_product( $this->product() ) );
	}

	public function test_default_brand_fallback(): void {
		$this->stub_options( [ 'woo_default_brand' => 'Shop' ] );
		Functions\when( 'get_the_terms' )->justReturn( false );

		$this->assertSame( 'Shop', BrandResolver::for_product( $this->product() ) );
	}

	public function test_empty_when_nothing(): void {
		$this->stub_options();
		Functions\when( 'get_the_terms' )->justReturn( false );

		$this->assertSame( '', BrandResolver::for_product( $this->product() ) );
	}
}
