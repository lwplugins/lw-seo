<?php
/**
 * ProductGroup variant unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Filters;
use LightweightPlugins\SEO\Integrations\WooCommerce\VariationOffer;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\VariationOffer
 */
final class VariationOfferTest extends MonkeyTestCase {

	use OptionsStubTrait;
	use VariationFixturesTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_options();
		$this->stub_variation_functions();
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_url_has_attribute_args(): void {
		$variant = VariationOffer::variant( $this->variation( 101 ), $this->parent(), $this->group() );

		$this->assertSame( self::PERMALINK . '?attribute_pa_szin=kek&attribute_meret=XL', $variant['offers']['url'] );
		$this->assertSame( 'Áfonyás póló - kék, XL', $variant['name'] );
		$this->assertSame( 'kék', $variant['color'] );
		$this->assertSame( 'XL', $variant['size'] );
		$this->assertSame( 'POLO-KEK-XL', $variant['sku'] );
		$this->assertSame( '5999884034063', $variant['gtin'] );
		$this->assertSame( 2490.0, $variant['offers']['price'] );
		$this->assertSame( 'HUF', $variant['offers']['priceCurrency'] );
	}

	public function test_price_valid_until_only_with_sale_end(): void {
		$plain = VariationOffer::variant( $this->variation( 101 ), $this->parent(), $this->group() );
		$sale  = VariationOffer::variant(
			$this->variation( 102, [ 'date_on_sale_to' => new \WC_DateTime( '2026-12-24 23:59:59' ) ] ),
			$this->parent(),
			$this->group()
		);

		$this->assertArrayNotHasKey( 'priceValidUntil', $plain['offers'] );
		$this->assertSame( '2026-12-24', $sale['offers']['priceValidUntil'] );
	}

	public function test_image_falls_back_to_parent(): void {
		$own      = VariationOffer::variant( $this->variation( 101, [ 'image_id' => 7 ] ), $this->parent(), $this->group() );
		$fallback = VariationOffer::variant( $this->variation( 102 ), $this->parent(), $this->group() );

		$this->assertSame( 'https://shop.test/img/7.jpg', $own['image'] );
		$this->assertSame( 'https://shop.test/img/parent.jpg', $fallback['image'] );
	}

	public function test_availability_mapping(): void {
		$expected = [
			'instock'     => 'https://schema.org/InStock',
			'outofstock'  => 'https://schema.org/OutOfStock',
			'onbackorder' => 'https://schema.org/BackOrder',
		];

		foreach ( $expected as $status => $url ) {
			$variant = VariationOffer::variant( $this->variation( 101, [ 'stock_status' => $status ] ), $this->parent(), $this->group() );
			$this->assertSame( $url, $variant['offers']['availability'], $status );
		}
	}

	public function test_offer_passes_through_filter_with_seller(): void {
		$this->stub_options( [ 'knowledge_type' => 'organization', 'knowledge_name' => 'MézVitéz Kft.' ] );
		Filters\expectApplied( 'lw_seo_woo_offer' )->once()->andReturnUsing(
			static fn( array $offer ) => $offer + [ 'shippingDetails' => [] ]
		);

		$variant = VariationOffer::variant( $this->variation( 101 ), $this->parent(), $this->group() );

		$this->assertSame( 'MézVitéz Kft.', $variant['offers']['seller']['name'] );
		$this->assertArrayHasKey( 'shippingDetails', $variant['offers'] );
	}

	public function test_null_when_not_purchasable_priceless_or_private(): void {
		foreach ( [ [ 'purchasable' => false ], [ 'price' => '' ], [ 'status' => 'private' ] ] as $props ) {
			$this->assertNull( VariationOffer::variant( $this->variation( 101, $props ), $this->parent(), $this->group() ) );
		}
	}
}
