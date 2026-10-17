<?php
/**
 * Product brand lookup.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * The brand of a product: its "brand" attribute, then its product_brand
 * term, then the store-wide default brand.
 */
final class BrandResolver {

	/**
	 * The product's brand name, or an empty string when none is known.
	 *
	 * @param \WC_Product $product The product.
	 * @return string
	 */
	public static function for_product( \WC_Product $product ): string {
		$brand = trim( (string) $product->get_attribute( 'brand' ) );

		if ( '' !== $brand ) {
			return $brand;
		}

		$terms = get_the_terms( $product->get_id(), 'product_brand' );

		if ( is_array( $terms ) && ! empty( $terms ) && isset( $terms[0]->name ) ) {
			return (string) $terms[0]->name;
		}

		return trim( (string) Options::get( 'woo_default_brand', '' ) );
	}
}
