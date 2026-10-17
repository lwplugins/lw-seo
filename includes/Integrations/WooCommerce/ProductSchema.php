<?php
/**
 * WooCommerce Product schema data.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Content\PostDescription;
use LightweightPlugins\SEO\Options;

/**
 * Builds the Product Schema.org node, or adds LW SEO's data to WooCommerce's.
 */
final class ProductSchema {

	/**
	 * Add LW SEO's product data to WooCommerce's own Product markup, so the
	 * page carries one Product node: WooCommerce keeps the offers (variation
	 * price ranges, sale prices, tax), LW SEO adds what it knows on top.
	 *
	 * @param array<string, mixed> $markup  WooCommerce's Product markup.
	 * @param \WC_Product          $product The product.
	 * @return array<string, mixed>
	 */
	public function enrich( array $markup, \WC_Product $product ): array {
		$description = $this->get_product_description( $product );
		if ( '' !== $description ) {
			$markup['description'] = $description;
		}

		$images = $this->get_product_images( $product );
		if ( ! empty( $images ) ) {
			$markup['image'] = $images;
		}

		// WooCommerce Brands sets brand from the product_brand taxonomy; keep it.
		$brand = empty( $markup['brand'] ) ? BrandResolver::for_product( $product ) : '';
		if ( '' !== $brand ) {
			$markup['brand'] = [
				'@type' => 'Brand',
				'name'  => $brand,
			];
		}

		$gtin = empty( $markup['gtin'] ) ? $this->get_gtin( $product ) : '';
		if ( '' !== $gtin ) {
			$markup['gtin'] = $gtin;
		}

		// LW SEO's reviews never print without their aggregate (see ReviewSchema).
		unset( $markup['aggregateRating'], $markup['review'] );
		$markup += ( new ReviewSchema() )->properties( $product );

		$seller = $this->get_seller();
		if ( $seller && ! empty( $markup['offers'] ) && is_array( $markup['offers'] ) ) {
			foreach ( $markup['offers'] as $key => $offer ) {
				if ( is_array( $offer ) ) {
					$markup['offers'][ $key ]['seller'] = $seller;
				}
			}
		}

		return $markup;
	}

	/**
	 * Build product Schema.org data.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string, mixed>
	 */
	public function build( \WC_Product $product ): array {
		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'@id'         => get_permalink( $product->get_id() ) . '#product',
			'name'        => $product->get_name(),
			'description' => $this->get_product_description( $product ),
			'url'         => get_permalink( $product->get_id() ),
		];

		// SKU.
		$sku = $product->get_sku();
		if ( $sku ) {
			$schema['sku'] = $sku;
		}

		// GTIN/EAN/UPC.
		$gtin = $this->get_gtin( $product );
		if ( $gtin ) {
			$schema['gtin'] = $gtin;
		}

		// Images.
		$images = $this->get_product_images( $product );
		if ( ! empty( $images ) ) {
			$schema['image'] = $images;
		}

		// Brand.
		$brand = BrandResolver::for_product( $product );
		if ( $brand ) {
			$schema['brand'] = [
				'@type' => 'Brand',
				'name'  => $brand,
			];
		}

		// Rating and reviews.
		$schema += ( new ReviewSchema() )->properties( $product );

		// Offer(s).
		$schema['offers'] = $this->get_product_offers( $product );

		return $schema;
	}

	/**
	 * Get product description: the short description, falling back to the
	 * product content, read through get_the_excerpt() so content
	 * restriction plugins can mask it.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_product_description( \WC_Product $product ): string {
		$post = get_post( $product->get_id() );

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$description = wp_trim_words( PostDescription::generated( $post, 50 ), 50, '...' );

		return PostDescription::filter( $description, $post, 'schema' );
	}

	/**
	 * Get product images.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string>
	 */
	private function get_product_images( \WC_Product $product ): array {
		$images = [];

		// Main image.
		$main_image = wp_get_attachment_image_url( (int) $product->get_image_id(), 'large' );
		if ( $main_image ) {
			$images[] = $main_image;
		}

		// Gallery images.
		$gallery_ids = $product->get_gallery_image_ids();
		foreach ( $gallery_ids as $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'large' );
			if ( $image_url ) {
				$images[] = $image_url;
			}
		}

		return $images;
	}

	/**
	 * Get product offers.
	 *
	 * @param \WC_Product $product The product.
	 * @return array<string, mixed>
	 */
	private function get_product_offers( \WC_Product $product ): array {
		$offer = [
			'@type'           => 'Offer',
			'url'             => get_permalink( $product->get_id() ),
			'priceCurrency'   => get_woocommerce_currency(),
			'price'           => $product->get_price(),
			'availability'    => $this->get_availability_url( $product ),
			'itemCondition'   => 'https://schema.org/NewCondition',
			'priceValidUntil' => gmdate( 'Y-12-31' ),
		];

		$seller = $this->get_seller();
		if ( $seller ) {
			$offer['seller'] = $seller;
		}

		return $offer;
	}

	/**
	 * Get Schema.org availability URL.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_availability_url( \WC_Product $product ): string {
		if ( ! $product->is_in_stock() ) {
			return 'https://schema.org/OutOfStock';
		}

		if ( $product->is_on_backorder() ) {
			return 'https://schema.org/BackOrder';
		}

		return 'https://schema.org/InStock';
	}

	/**
	 * Get the product GTIN: WooCommerce's GTIN/UPC/EAN/ISBN field, or the
	 * older _gtin meta.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	private function get_gtin( \WC_Product $product ): string {
		// WooCommerce 9.1+ only; the stubs are newer than the oldest supported WooCommerce.
		$gtin = is_callable( // @phpstan-ignore function.alreadyNarrowedType
			[ $product, 'get_global_unique_id' ]
		) ? (string) $product->get_global_unique_id() : '';

		if ( '' === $gtin ) {
			$gtin = (string) get_post_meta( $product->get_id(), '_gtin', true );
		}

		return $gtin;
	}

	/**
	 * The seller: the site's Knowledge Graph entity, when one is set.
	 *
	 * @return array<string, string>|null
	 */
	private function get_seller(): ?array {
		$knowledge_type = Options::get( 'knowledge_type' );

		if ( ! $knowledge_type ) {
			return null;
		}

		$knowledge_name = Options::get( 'knowledge_name' );

		return [
			'@type' => ucfirst( $knowledge_type ),
			'name'  => ! empty( $knowledge_name ) ? $knowledge_name : get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		];
	}
}
