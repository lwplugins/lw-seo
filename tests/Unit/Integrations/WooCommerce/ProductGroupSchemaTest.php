<?php
/**
 * ProductGroup conversion unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\ProductGroupSchema;
use LightweightPlugins\SEO\Integrations\WooCommerce\Schema;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\ProductGroupSchema
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\Schema
 */
final class ProductGroupSchemaTest extends MonkeyTestCase {

	use OptionsStubTrait;
	use VariationFixturesTrait;

	protected function setUp(): void {
		parent::setUp();
		$this->stub_options( [ 'woo_product_group' => true ] );
		$this->stub_variation_functions();
		$this->stub_variations(
			[
				101 => $this->variation( 101 ),
				102 => $this->variation( 102, [ 'price' => '2990', 'sku' => 'POLO-PIROS-M', 'variation_attributes' => [ 'attribute_pa_szin' => 'piros', 'attribute_meret' => 'M' ] ] ),
			]
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_off_by_default_keeps_aggregate_offer(): void {
		$this->stub_options( [ 'schema_enabled' => true ] );
		Functions\when( 'get_post' )->justReturn( null );
		Functions\when( 'get_comments' )->justReturn( [] );

		$markup = ( new Schema() )->enrich_woocommerce_markup( $this->group(), $this->parent() );

		$this->assertFalse( ProductGroupSchema::applies( $this->parent() ) );
		$this->assertSame( 'Product', $markup['@type'] );
		$this->assertSame( 'AggregateOffer', $markup['offers'][0]['@type'] );
		$this->assertSame( '2990', $markup['offers'][0]['highPrice'] );
		$this->assertArrayNotHasKey( 'hasVariant', $markup );
	}

	public function test_applies_to_variable_products_only(): void {
		$this->assertTrue( ProductGroupSchema::applies( $this->parent() ) );
		$this->assertFalse( ProductGroupSchema::applies( new \WC_Product( 7 ) ) );
	}

	public function test_converts_to_product_group(): void {
		$group = ProductGroupSchema::convert( $this->group(), $this->parent() );

		$this->assertSame( 'ProductGroup', $group['@type'] );
		$this->assertSame( self::$permalink . '#product', $group['@id'] );
		$this->assertSame( 'MézVitéz', $group['brand']['name'] );
		$this->assertSame( [ 'https://schema.org/color', 'https://schema.org/size' ], $group['variesBy'] );
		$this->assertSame( [ 'POLO-KEK-XL', 'POLO-PIROS-M' ], array_column( $group['hasVariant'], 'sku' ) );
		$this->assertSame( 'Product', $group['hasVariant'][1]['@type'] );
		$this->assertSame( 2990.0, $group['hasVariant'][1]['offers']['price'] );
		$this->assertSame( 'kék', $group['hasVariant'][0]['color'] );
		$this->assertSame( 'Piros', $group['hasVariant'][1]['color'] );
		$this->assertSame( [ 'POLO', 'POLO' ], array_column( $group['hasVariant'], 'inProductGroupWithID' ) );
	}

	public function test_group_has_no_offers_and_has_group_id(): void {
		$group = ProductGroupSchema::convert( $this->group(), $this->parent() );

		$this->assertArrayNotHasKey( 'offers', $group );
		$this->assertArrayNotHasKey( 'sku', $group );
		$this->assertArrayNotHasKey( 'gtin', $group );
		$this->assertArrayNotHasKey( 'gtin13', $group );
		$this->assertSame( 'POLO', $group['productGroupID'] );
		$this->assertSame( '42', ProductGroupSchema::convert( $this->group(), $this->parent( [ 'sku' => '' ] ) )['productGroupID'] );
	}

	public function test_skips_unpurchasable_variations(): void {
		$this->stub_variations(
			[
				101 => $this->variation( 101, [ 'purchasable' => false ] ),
				102 => $this->variation( 102, [ 'sku' => 'POLO-PIROS-M' ] ),
			]
		);

		$group = ProductGroupSchema::convert( $this->group(), $this->parent( [ 'children' => [ 101, 102, 103 ] ] ) );

		$this->assertSame( [ 'POLO-PIROS-M' ], array_column( $group['hasVariant'], 'sku' ) );
	}

	public function test_returns_node_unchanged_when_no_variants(): void {
		$this->stub_variations(
			[
				101 => $this->variation( 101, [ 'price' => '' ] ),
				102 => $this->variation( 102, [ 'status' => 'private' ] ),
			]
		);

		$this->assertSame( $this->group(), ProductGroupSchema::convert( $this->group(), $this->parent() ) );
	}
}
