<?php
/**
 * ProductGroup variant with its Offer.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

/**
 * Builds one variation's Product node (hasVariant entry) and its Offer.
 */
final class VariationOffer {

	/**
	 * Stock status => schema.org availability.
	 *
	 * @var array<string, string>
	 */
	private const AVAILABILITY = [
		'instock'     => 'https://schema.org/InStock',
		'outofstock'  => 'https://schema.org/OutOfStock',
		'onbackorder' => 'https://schema.org/BackOrder',
	];

	/**
	 * The variation's Product node, or null when it is not purchasable,
	 * has no price, or is not published.
	 *
	 * @param \WC_Product          $variation The variation.
	 * @param \WC_Product          $parent    The variable product.
	 * @param array<string, mixed> $group     The group's markup (name, image).
	 * @return array<string, mixed>|null
	 */
	public static function variant( \WC_Product $variation, \WC_Product $parent, array $group ): ?array {
		if ( ! $variation instanceof \WC_Product_Variation || ! self::is_offered( $variation ) ) {
			return null;
		}

		$attributes = array_filter( array_map( 'strval', $variation->get_variation_attributes() ), static fn( string $value ): bool => '' !== $value );
		$values     = [];
		$variant    = [ '@type' => 'Product' ];

		foreach ( $attributes as $key => $value ) {
			$label    = self::attribute_label( (string) $key, $value );
			$values[] = $label;

			$property = VariesBy::property( (string) $key );
			if ( null !== $property ) {
				$variant[ $property ] = $label;
			}
		}

		$name            = isset( $group['name'] ) && is_string( $group['name'] ) ? $group['name'] : $parent->get_name();
		$variant['name'] = empty( $values ) ? $name : $name . ' - ' . implode( ', ', $values );

		$sku = (string) $variation->get_sku();
		if ( '' !== $sku ) {
			$variant['sku'] = $sku;
		}

		// WooCommerce 9.1+ only; the variation's own field, never the parent's.
		$gtin = is_callable( // @phpstan-ignore function.alreadyNarrowedType
			[ $variation, 'get_global_unique_id' ]
		) ? (string) $variation->get_global_unique_id() : '';
		if ( '' !== $gtin ) {
			$variant['gtin'] = $gtin;
		}

		$image = self::image( $variation, $group );
		if ( '' !== $image ) {
			$variant['image'] = $image;
		}

		$variant['offers'] = self::offer( $variation, $parent, $attributes );

		return $variant;
	}

	/**
	 * Whether the variation is published, purchasable and priced.
	 *
	 * @param \WC_Product $variation The variation.
	 * @return bool
	 */
	private static function is_offered( \WC_Product $variation ): bool {
		return 'publish' === $variation->get_status()
			&& $variation->is_purchasable()
			&& '' !== (string) $variation->get_price();
	}

	/**
	 * The variation's Offer, passed through `lw_seo_woo_offer`.
	 *
	 * @param \WC_Product           $variation  The variation.
	 * @param \WC_Product           $parent     The variable product.
	 * @param array<string, string> $attributes The variation's attribute_* => value pairs.
	 * @return array<string, mixed>
	 */
	private static function offer( \WC_Product $variation, \WC_Product $parent, array $attributes ): array {
		$offer = [
			'@type'         => 'Offer',
			'price'         => wc_get_price_to_display( $variation ),
			'priceCurrency' => get_woocommerce_currency(),
			'availability'  => self::AVAILABILITY[ (string) $variation->get_stock_status() ] ?? self::AVAILABILITY['instock'],
			'url'           => add_query_arg( array_map( 'rawurlencode', $attributes ), (string) get_permalink( $parent->get_id() ) ),
		];

		$sale_end = $variation->get_date_on_sale_to();
		if ( $sale_end instanceof \DateTimeInterface ) {
			$offer['priceValidUntil'] = $sale_end->format( 'Y-m-d' );
		}

		$seller = ProductSchema::get_seller();
		if ( $seller ) {
			$offer['seller'] = $seller;
		}

		/**
		 * Filters one Offer of LW SEO's product markup.
		 *
		 * @param mixed       $offer   The Offer; a non-array return is ignored.
		 * @param \WC_Product $product The product (here: the variation) it offers.
		 */
		$filtered = apply_filters( 'lw_seo_woo_offer', $offer, $variation );

		return is_array( $filtered ) ? $filtered : $offer;
	}

	/**
	 * The display value of a variation attribute: the term name for global
	 * (`pa_`) attributes, the stored value otherwise.
	 *
	 * @param string $key   The attribute_* key.
	 * @param string $value The stored value (term slug for global attributes).
	 * @return string
	 */
	private static function attribute_label( string $key, string $value ): string {
		$taxonomy = (string) preg_replace( '/^attribute_/', '', $key );

		if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
			$term = get_term_by( 'slug', $value, $taxonomy );
			if ( $term instanceof \WP_Term && '' !== $term->name ) {
				return $term->name;
			}
		}

		return $value;
	}

	/**
	 * The variation's image, else the group's first image.
	 *
	 * @param \WC_Product          $variation The variation.
	 * @param array<string, mixed> $group     The group's markup.
	 * @return string
	 */
	private static function image( \WC_Product $variation, array $group ): string {
		$image = wp_get_attachment_image_url( (int) $variation->get_image_id(), 'large' );
		if ( $image ) {
			return $image;
		}

		$fallback = $group['image'] ?? '';
		if ( is_array( $fallback ) ) {
			$fallback = reset( $fallback );
		}

		return is_string( $fallback ) ? $fallback : '';
	}
}
