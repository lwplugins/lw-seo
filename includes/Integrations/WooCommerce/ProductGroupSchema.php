<?php
/**
 * WooCommerce ProductGroup markup for variable products.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Turns a variable product's Product node into a ProductGroup whose
 * variants each carry their own price and availability.
 */
final class ProductGroupSchema {

	/**
	 * Whether the product's markup becomes a ProductGroup.
	 *
	 * @param \WC_Product $product The product.
	 * @return bool
	 */
	public static function applies( \WC_Product $product ): bool {
		return (bool) Options::get( 'woo_product_group' ) && $product->is_type( 'variable' );
	}

	/**
	 * Convert the Product node into a ProductGroup. Without a single
	 * offered variation the node comes back unchanged, keeping its
	 * group-level (aggregate) offer.
	 *
	 * @param array<string, mixed> $node    The Product node.
	 * @param \WC_Product          $product The variable product.
	 * @return array<string, mixed>
	 */
	public static function convert( array $node, \WC_Product $product ): array {
		if ( ! $product instanceof \WC_Product_Variable ) {
			return $node;
		}

		$sku      = (string) $product->get_sku();
		$group_id = '' !== $sku ? $sku : (string) $product->get_id();
		$variants = self::variants( [ 'productGroupID' => $group_id ] + $node, $product );

		if ( empty( $variants ) ) {
			return $node;
		}

		$group = $node;
		unset( $group['offers'], $group['sku'] );
		foreach ( array_keys( $group ) as $key ) {
			if ( 0 === strpos( (string) $key, 'gtin' ) ) {
				unset( $group[ $key ] );
			}
		}

		$group['@type']          = 'ProductGroup';
		$group['productGroupID'] = $group_id;

		$varies_by = VariesBy::for_attributes( array_map( 'strval', array_keys( $product->get_variation_attributes() ) ) );
		if ( ! empty( $varies_by ) ) {
			$group['variesBy'] = $varies_by;
		}

		$group['hasVariant'] = $variants;

		return $group;
	}

	/**
	 * The offered variations' Product nodes.
	 *
	 * @param array<string, mixed> $node    The Product node.
	 * @param \WC_Product_Variable $product The variable product.
	 * @return array<int, array<string, mixed>>
	 */
	private static function variants( array $node, \WC_Product_Variable $product ): array {
		$variants = [];
		$terms    = self::terms( $product );

		foreach ( $product->get_children() as $child_id ) {
			$variation = wc_get_product( $child_id );
			$variant   = $variation instanceof \WC_Product ? VariationOffer::variant( $variation, $product, $node, $terms ) : null;

			if ( null !== $variant ) {
				$variants[] = $variant;
			}
		}

		return $variants;
	}

	/**
	 * The names of the product's global attribute terms, loaded once per
	 * taxonomy (WordPress usually has them cached on the product page).
	 *
	 * @param \WC_Product_Variable $product The variable product.
	 * @return array<string, array<string, string>> Taxonomy => slug => name.
	 */
	private static function terms( \WC_Product_Variable $product ): array {
		$terms = [];

		foreach ( array_keys( $product->get_variation_attributes() ) as $taxonomy ) {
			$taxonomy = (string) $taxonomy;
			if ( 0 !== strpos( $taxonomy, 'pa_' ) ) {
				continue;
			}

			$found = get_the_terms( $product->get_id(), $taxonomy );
			foreach ( is_array( $found ) ? $found : [] as $term ) {
				$terms[ $taxonomy ][ $term->slug ] = $term->name;
			}
		}

		return $terms;
	}
}
