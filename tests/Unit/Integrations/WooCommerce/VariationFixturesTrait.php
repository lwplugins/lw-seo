<?php
/**
 * Variable product fixtures for unit tests.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Tests\Unit\Integrations\WooCommerce;

use Brain\Monkey\Functions;

/**
 * A variable product with two variations, and the WordPress functions they read.
 */
trait VariationFixturesTrait {

	private static string $permalink = 'https://shop.test/uzlet/afonyas-polo/';

	private function stub_variation_functions(): void {
		Functions\when( 'get_permalink' )->justReturn( self::$permalink );
		Functions\when( 'add_query_arg' )->alias( static fn( array $args, string $url ) => $url . '?' . http_build_query( $args ) );
		Functions\when( 'get_woocommerce_currency' )->justReturn( 'HUF' );
		Functions\when( 'get_bloginfo' )->justReturn( 'Shop' );
		Functions\when( 'home_url' )->justReturn( 'https://shop.test/' );
		Functions\when( 'wc_get_price_to_display' )->alias( static fn( \WC_Product $product ) => (float) $product->get_price() );
		Functions\when( 'wp_get_attachment_image_url' )->alias( static fn( $id ) => $id ? 'https://shop.test/img/' . $id . '.jpg' : false );
		Functions\expect( 'get_term_by' )->never();
		Functions\when( 'get_the_terms' )->alias(
			static fn( $post_id, $taxonomy ) => 'pa_szin' === $taxonomy
				? [ new \WP_Term( [ 'slug' => 'kek', 'name' => 'kék' ] ), new \WP_Term( [ 'slug' => 'piros', 'name' => 'Piros' ] ) ]
				: false
		);
		Functions\when( 'sanitize_title' )->alias(
			static fn( string $title ) => trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( $title ) ), '-' )
		);
	}

	/**
	 * @param array<string, mixed> $props Variation props.
	 */
	private function variation( int $id, array $props = [] ): \WC_Product_Variation {
		return new \WC_Product_Variation(
			$id,
			0,
			0.0,
			$props + [
				'price'                => '2490',
				'sku'                  => 'POLO-KEK-XL',
				'global_unique_id'     => '5999884034063',
				'variation_attributes' => [
					'attribute_pa_szin' => 'kek',
					'attribute_meret'   => 'XL',
				],
			]
		);
	}

	/**
	 * @param array<string, mixed> $props Parent props.
	 */
	private function parent( array $props = [] ): \WC_Product_Variable {
		return new \WC_Product_Variable(
			42,
			0,
			0.0,
			$props + [
				'name'                 => 'Áfonyás póló',
				'sku'                  => 'POLO',
				'children'             => [ 101, 102 ],
				'variation_attributes' => [
					'pa_szin' => [ 'kek', 'piros' ],
					'meret'   => [ 'M', 'XL' ],
				],
			]
		);
	}

	/**
	 * The parent's attribute terms: taxonomy => slug => name.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function terms(): array {
		return [ 'pa_szin' => [ 'kek' => 'kék', 'piros' => 'Piros' ] ];
	}

	/**
	 * WooCommerce's markup for the variable product.
	 *
	 * @return array<string, mixed>
	 */
	private function group(): array {
		return [
			'@type'  => 'Product',
			'@id'    => self::$permalink . '#product',
			'name'   => 'Áfonyás póló',
			'url'    => self::$permalink,
			'image'  => [ 'https://shop.test/img/parent.jpg' ],
			'brand'  => [ '@type' => 'Brand', 'name' => 'MézVitéz' ],
			'sku'    => 'POLO',
			'gtin'   => '5999884000000',
			'gtin13' => '5999884000000',
			'offers' => [
				[
					'@type'      => 'AggregateOffer',
					'lowPrice'   => '2490',
					'highPrice'  => '2990',
					'offerCount' => 2,
				],
			],
		];
	}

	/**
	 * Make wc_get_product() return the given variations by ID.
	 *
	 * @param array<int, \WC_Product> $variations Variations by ID.
	 */
	private function stub_variations( array $variations ): void {
		Functions\when( 'wc_get_product' )->alias( static fn( $id ) => $variations[ $id ] ?? false );
	}
}
