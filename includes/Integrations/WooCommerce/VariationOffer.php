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
	 * @param \WC_Product                          $variation The variation.
	 * @param \WC_Product                          $parent    The variable product.
	 * @param array<string, mixed>                 $group     The group's markup (name, image, productGroupID).
	 * @param array<string, array<string, string>> $terms     Attribute term names: taxonomy => slug => name.
	 * @return array<string, mixed>|null
	 */
	public static function variant( \WC_Product $variation, \WC_Product $parent, array $group, array $terms = [] ): ?array {
		if ( ! $variation instanceof \WC_Product_Variation || ! self::is_offered( $variation ) ) {
			return null;
		}

		$attributes = array_filter( array_map( 'strval', $variation->get_variation_attributes() ), static fn( string $value ): bool => '' !== $value );
		$labels     = self::attribute_labels( $attributes, $terms );
		$variant    = [ '@type' => 'Product' ];

		if ( isset( $group['productGroupID'] ) && is_string( $group['productGroupID'] ) ) {
			$variant['inProductGroupWithID'] = $group['productGroupID'];
		}

		foreach ( $labels as $key => $label ) {
			$property = VariesBy::property( (string) $key );
			if ( null !== $property ) {
				$variant[ $property ] = $label;
			}
		}

		$values          = array_values( $labels );
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

		$sale_end = $variation->is_on_sale() ? $variation->get_date_on_sale_to() : null;
		if ( $sale_end instanceof \DateTimeInterface ) {
			$offer['priceValidUntil'] = gmdate( 'Y-m-d', $sale_end->getTimestamp() );
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
	 * The display values of the variation's attributes: the term name for
	 * global (`pa_`) attributes, the stored value otherwise.
	 *
	 * @param array<string, string>                $attributes The attribute_* => stored value (term slug for global attributes).
	 * @param array<string, array<string, string>> $terms      Attribute term names: taxonomy => slug => name.
	 * @return array<string, string>
	 */
	private static function attribute_labels( array $attributes, array $terms ): array {
		$labels = [];

		foreach ( $attributes as $key => $value ) {
			$taxonomy       = (string) preg_replace( '/^attribute_/', '', (string) $key );
			$labels[ $key ] = $terms[ $taxonomy ][ $value ] ?? $value;
		}

		return $labels;
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
