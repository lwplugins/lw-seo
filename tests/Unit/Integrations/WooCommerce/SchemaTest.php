<?php
/**
 * WooCommerce product schema unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\SEO\Integrations\WooCommerce\Schema;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Tests\Unit\MonkeyTestCase;
use LightweightPlugins\SEO\Tests\Unit\OptionsStubTrait;

/**
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\Schema
 * @covers \LightweightPlugins\SEO\Integrations\WooCommerce\ProductSchema
 */
final class SchemaTest extends MonkeyTestCase {

	use OptionsStubTrait;

	private const PERMALINK = 'https://shop.test/uzlet/afonyas-alkonyat/';

	/**
	 * WooCommerce's structured data, as WC()->structured_data.
	 */
	private \WC_Structured_Data $structured_data;

	protected function setUp(): void {
		parent::setUp();

		$this->stub_options( [ 'schema_enabled' => true ] );
		$this->structured_data = new \WC_Structured_Data();

		$wc                  = new \stdClass();
		$wc->structured_data = $this->structured_data;
		Functions\when( 'WC' )->justReturn( $wc );
		Functions\when( 'get_permalink' )->justReturn( self::PERMALINK );
		Functions\when( 'get_post' )->justReturn( null );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_terms' )->justReturn( false );
		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'HUF' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Shop' );
		Functions\when( 'home_url' )->justReturn( 'https://shop.test/' );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data, $flags = 0 ) => json_encode( $data, $flags ) );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $props Product properties.
	 */
	private function product( array $props = [] ): \WC_Product {
		return new \WC_Product( 42, 0, 0.0, $props + [ 'name' => 'Áfonyás alkonyat', 'price' => '950' ] );
	}

	/**
	 * WooCommerce's Product markup for a variable product.
	 *
	 * @return array<string, mixed>
	 */
	private function woocommerce_markup(): array {
		return [
			'@type'  => 'Product',
			'@id'    => self::PERMALINK . '#product',
			'name'   => 'Áfonyás alkonyat',
			'offers' => [
				[
					'@type'     => 'AggregateOffer',
					'lowPrice'  => '950',
					'highPrice' => '3200',
					'seller'    => [ '@type' => 'Organization', 'name' => 'Shop' ],
				],
			],
		];
	}

	private function printed( \WC_Product $product ): string {
		Functions\when( 'has_action' )->justReturn( false );
		ob_start();
		( new Schema() )->ensure_product_node( $product );

		return (string) ob_get_clean();
	}

	/**
	 * The JSON-LD nodes in printed output.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function nodes( string $output ): array {
		preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $output, $matches );

		return array_map( static fn( string $json ) => json_decode( $json, true ), $matches[1] );
	}

	public function test_prints_no_second_product_when_woocommerce_has_one(): void {
		Functions\when( 'has_action' )->justReturn( 10 );
		$this->structured_data->test_data = [ $this->woocommerce_markup() ];

		ob_start();
		( new Schema() )->ensure_product_node( $this->product() );

		$this->assertSame( '', ob_get_clean() );
		$this->assertSame( [], $this->structured_data->test_generated );
	}

	public function test_has_woocommerce_build_the_product_when_the_template_skipped_it(): void {
		Functions\when( 'has_action' )->justReturn( 10 );
		$product = $this->product();

		ob_start();
		( new Schema() )->ensure_product_node( $product );

		$this->assertSame( '', ob_get_clean() );
		$this->assertSame( [ $product ], $this->structured_data->test_generated );
	}

	public function test_prints_its_own_product_when_woocommerce_does_not_print(): void {
		$nodes = $this->nodes( $this->printed( $this->product() ) );

		$this->assertSame( [ 'Product' ], array_column( $nodes, '@type' ) );
	}

	public function test_keeps_woocommerce_offers(): void {
		$markup = ( new Schema() )->enrich_woocommerce_markup( $this->woocommerce_markup(), $this->product() );

		$this->assertSame( 'AggregateOffer', $markup['offers'][0]['@type'] );
		$this->assertSame( '3200', $markup['offers'][0]['highPrice'] );
	}

	public function test_adds_the_brand_attribute_when_woocommerce_has_no_brand(): void {
		$markup = ( new Schema() )->enrich_woocommerce_markup( $this->woocommerce_markup(), $this->product( [ 'attribute_brand' => 'MézVitéz' ] ) );

		$this->assertSame( [ '@type' => 'Brand', 'name' => 'MézVitéz' ], $markup['brand'] );
	}

	public function test_keeps_the_woocommerce_brands_brand(): void {
		$brand  = [ '@type' => 'Brand', 'name' => 'From taxonomy' ];
		$markup = ( new Schema() )->enrich_woocommerce_markup( [ 'brand' => $brand ] + $this->woocommerce_markup(), $this->product( [ 'attribute_brand' => 'Attribute' ] ) );

		$this->assertSame( $brand, $markup['brand'] );
	}

	public function test_own_product_reads_the_woocommerce_gtin_field(): void {
		$nodes = $this->nodes( $this->printed( $this->product( [ 'global_unique_id' => '5999884034063' ] ) ) );

		$this->assertSame( '5999884034063', $nodes[0]['gtin'] ?? null );
	}

	public function test_offers_carry_the_knowledge_graph_seller(): void {
		$this->stub_options(
			[
				'schema_enabled' => true,
				'knowledge_type' => 'organization',
				'knowledge_name' => 'MézVitéz Kft.',
			]
		);

		$markup = ( new Schema() )->enrich_woocommerce_markup( $this->woocommerce_markup(), $this->product() );

		$this->assertSame( 'MézVitéz Kft.', $markup['offers'][0]['seller']['name'] );
	}

	public function test_sites_can_extend_the_markup(): void {
		Filters\expectApplied( 'lw_seo_woo_product_schema' )->once()->andReturnUsing(
			static fn( array $markup ) => $markup + [ 'hasMerchantReturnPolicy' => [ '@type' => 'MerchantReturnPolicy' ] ]
		);

		$markup = ( new Schema() )->enrich_woocommerce_markup( $this->woocommerce_markup(), $this->product() );

		$this->assertArrayHasKey( 'hasMerchantReturnPolicy', $markup );
	}

	public function test_leaves_woocommerce_markup_alone_when_product_schema_is_off(): void {
		$this->stub_options( [ 'schema_enabled' => true, 'woo_schema_enabled' => false ] );

		$this->assertSame( $this->woocommerce_markup(), ( new Schema() )->enrich_woocommerce_markup( $this->woocommerce_markup(), $this->product() ) );
	}
}
