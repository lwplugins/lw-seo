<?php
/**
 * Start date of product offers.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

/**
 * Adds validFrom to an Offer: the start of the sale for a sale price,
 * otherwise the date the product was published.
 */
final class OfferValidity {

	/**
	 * Hook onto the offers LW SEO and WooCommerce build.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'lw_seo_woo_offer', [ self::class, 'filter' ], 10, 2 );
		add_filter( 'woocommerce_structured_data_product_offer', [ self::class, 'filter' ], 10, 2 );
	}

	/**
	 * Filter callback: an existing validFrom is never overwritten.
	 *
	 * @param mixed $offer   The offer.
	 * @param mixed $product The product it offers.
	 * @return mixed
	 */
	public static function filter( $offer, $product = null ) {
		if ( ! is_array( $offer ) || isset( $offer['validFrom'] ) || ! $product instanceof \WC_Product ) {
			return $offer;
		}

		$date = $product->is_on_sale() ? $product->get_date_on_sale_from() : null;
		$date = $date instanceof \DateTimeInterface ? $date : $product->get_date_created();

		if ( $date instanceof \DateTimeInterface ) {
			$offer['validFrom'] = $date->format( 'c' );
		}

		return $offer;
	}
}
