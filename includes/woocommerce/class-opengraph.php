<?php
/**
 * WooCommerce OpenGraph class.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Handles WooCommerce-specific OpenGraph meta tags.
 *
 * Adds product-specific OG tags like price, availability, brand, condition.
 */
final class OpenGraph {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_product_opengraph' ], 30 );
	}

	/**
	 * Output product-specific OpenGraph tags.
	 *
	 * @return void
	 */
	public function output_product_opengraph(): void {
		if ( ! Options::get( 'opengraph_enabled' ) ) {
			return;
		}

		if ( ! WooCommerce::is_product() ) {
			return;
		}

		$product = WooCommerce::get_product();

		if ( ! $product ) {
			return;
		}

		echo "\n<!-- LW SEO WooCommerce OpenGraph -->\n";

		// Product type.
		echo '<meta property="og:type" content="product" />' . "\n";

		// Price.
		$this->output_price_tags( $product );

		// Availability.
		$this->output_availability_tag( $product );

		// Condition (default to new).
		echo '<meta property="product:condition" content="new" />' . "\n";

		// Brand (if available).
		$brand = $this->get_product_brand( $product );
		if ( $brand ) {
			printf( '<meta property="product:brand" content="%s" />' . "\n", esc_attr( $brand ) );
		}

		// SKU.
		$sku = $product->get_sku();
		if ( $sku ) {
			printf( '<meta property="product:retailer_item_id" content="%s" />' . "\n", esc_attr( $sku ) );
		}

		// Product images.
		$this->output_product_images( $product );

		echo "<!-- /LW SEO WooCommerce OpenGraph -->\n";
	}

	/**
	 * Output price-related OpenGraph tags.
	 *
	 * @param \WC_Product $product The product.
	 * @return void
	 */
	private function output_price_tags( \WC_Product $product ): void {
		$price = $product->get_price();

		if ( empty( $price ) ) {
			return;
		}

		printf( '<meta property="product:price:amount" content="%s" />' . "\n", esc_attr( $price ) );
		printf( '<meta property="product:price:currency" content="%s" />' . "\n", esc_attr( get_woocommerce_currency() ) );

		// Sale price.
		if ( $product->is_on_sale() ) {
			$regular_price = $product->get_regular_price();
			$sale_price    = $product->get_sale_price();

			if ( $regular_price && $sale_price ) {
				printf(
					'<meta property="product:original_price:amount" content="%s" />' . "\n",
					esc_attr( $regular_price )
				);
				printf(
					'<meta property="product:original_price:currency" content="%s" />' . "\n",
					esc_attr( get_woocommerce_currency() )
				);
				printf(
					'<meta property="product:sale_price:amount" content="%s" />' . "\n",
					esc_attr( $sale_price )
				);
				printf(
					'<meta property="product:sale_price:currency" content="%s" />' . "\n",
					esc_attr( get_woocommerce_currency() )
				);
			}
		}
	}

	/**
	 * Output availability OpenGraph tag.
	 *
	 * @param \WC_Product $product The product.
	 * @return void
	 */
	private function output_availability_tag( \WC_Product $product ): void {
		$availability = 'instock';

		if ( ! $product->is_in_stock() ) {
			$availability = 'outofstock';
		} elseif ( $product->is_on_backorder() ) {
			$availability = 'pending';
		}

		printf( '<meta property="product:availability" content="%s" />' . "\n", esc_attr( $availability ) );
	}

	/**
	 * Get product brand from attributes or taxonomy.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_product_brand( \WC_Product $product ): string {
		// Try product attribute.
		$brand = $product->get_attribute( 'brand' );

		if ( $brand ) {
			return $brand;
		}

		// Try taxonomy (common brand plugins use product_brand).
		$brand_terms = get_the_terms( $product->get_id(), 'product_brand' );

		if ( is_array( $brand_terms ) && ! empty( $brand_terms ) ) {
			return $brand_terms[0]->name;
		}

		// Fallback to site name.
		return '';
	}

	/**
	 * Output product gallery images as OpenGraph images.
	 *
	 * @param \WC_Product $product The product.
	 * @return void
	 */
	private function output_product_images( \WC_Product $product ): void {
		$image_ids = $product->get_gallery_image_ids();

		// Limit to 4 additional images.
		$image_ids = array_slice( $image_ids, 0, 4 );

		foreach ( $image_ids as $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'large' );

			if ( $image_url ) {
				printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image_url ) );
			}
		}
	}
}
