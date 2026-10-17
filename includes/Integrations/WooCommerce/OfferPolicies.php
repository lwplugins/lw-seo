<?php
/**
 * Shipping and return policy markup for product offers.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Adds shippingDetails and hasMerchantReturnPolicy, built from the shipping
 * and returns settings, to an Offer or AggregateOffer.
 */
final class OfferPolicies {

	private const SCHEMA = 'https://schema.org/';

	private const CATEGORIES = [
		'finite'        => 'MerchantReturnFiniteReturnWindow',
		'not_permitted' => 'MerchantReturnNotPermitted',
		'unlimited'     => 'MerchantReturnUnlimitedWindow',
	];

	private const METHODS = [
		'by_mail'  => 'ReturnByMail',
		'in_store' => 'ReturnInStore',
	];

	private const FEES = [
		'free'          => 'FreeReturn',
		'customer_pays' => 'ReturnFeesCustomerResponsibility',
	];

	/**
	 * Hook the policies onto the offers LW SEO and WooCommerce build.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'lw_seo_woo_offer', [ self::class, 'filter' ] );
		add_filter( 'woocommerce_structured_data_product_offer', [ self::class, 'filter' ] );
	}

	/**
	 * Filter callback: leaves anything but an offer array alone.
	 *
	 * @param mixed $offer The offer.
	 * @return mixed
	 */
	public static function filter( $offer ) {
		return is_array( $offer ) ? self::apply( $offer ) : $offer;
	}

	/**
	 * Add the policies to an offer; existing keys are never overwritten.
	 *
	 * @param array<string, mixed> $offer The Offer or AggregateOffer.
	 * @return array<string, mixed>
	 */
	public static function apply( array $offer ): array {
		return $offer + self::shipping() + self::returns();
	}

	/**
	 * The shippingDetails property.
	 *
	 * @return array<string, mixed>
	 */
	private static function shipping(): array {
		$countries = self::countries( (string) Options::get( 'woo_shipping_countries', '' ) );
		$free      = 'free' === Options::get( 'woo_shipping_rate_type', 'flat' );
		$rate      = (string) Options::get( 'woo_shipping_rate', '' );

		if ( ! Options::get( 'woo_shipping_enabled' ) || ! $countries || ( ! $free && '' === $rate ) ) {
			return [];
		}

		$details = [];
		foreach ( $countries as $country ) {
			$details[] = [
				'@type'               => 'OfferShippingDetails',
				'shippingRate'        => [
					'@type'    => 'MonetaryAmount',
					'value'    => $free ? 0 : (float) $rate,
					'currency' => get_woocommerce_currency(),
				],
				'shippingDestination' => [
					'@type'          => 'DefinedRegion',
					'addressCountry' => $country,
				],
				'deliveryTime'        => [
					'@type'        => 'ShippingDeliveryTime',
					'handlingTime' => self::days( 'woo_handling_min', 'woo_handling_max' ),
					'transitTime'  => self::days( 'woo_transit_min', 'woo_transit_max' ),
				],
			];
		}

		return [ 'shippingDetails' => $details ];
	}

	/**
	 * The hasMerchantReturnPolicy property.
	 *
	 * @return array<string, mixed>
	 */
	private static function returns(): array {
		$country = self::countries( (string) Options::get( 'woo_return_country', '' ) )[0] ?? '';

		if ( ! Options::get( 'woo_returns_enabled' ) || '' === $country ) {
			return [];
		}

		$category = self::choice( self::CATEGORIES, (string) Options::get( 'woo_return_category', 'finite' ) );
		$policy   = [
			'@type'                => 'MerchantReturnPolicy',
			'applicableCountry'    => $country,
			'returnPolicyCategory' => self::SCHEMA . $category,
		];

		if ( 'MerchantReturnFiniteReturnWindow' === $category ) {
			$policy['merchantReturnDays'] = (int) Options::get( 'woo_return_days', 14 );
		}

		if ( 'MerchantReturnNotPermitted' !== $category ) {
			$policy['returnMethod'] = self::SCHEMA . self::choice( self::METHODS, (string) Options::get( 'woo_return_method', 'by_mail' ) );
			$policy['returnFees']   = self::SCHEMA . self::choice( self::FEES, (string) Options::get( 'woo_return_fees', 'free' ) );
		}

		return [ 'hasMerchantReturnPolicy' => $policy ];
	}

	/**
	 * A QuantitativeValue in days; the bounds are swapped when reversed.
	 *
	 * @param string $min_key Option key of the lower bound.
	 * @param string $max_key Option key of the upper bound.
	 * @return array<string, mixed>
	 */
	private static function days( string $min_key, string $max_key ): array {
		$min = (int) Options::get( $min_key, 0 );
		$max = (int) Options::get( $max_key, 0 );

		return [
			'@type'    => 'QuantitativeValue',
			'minValue' => min( $min, $max ),
			'maxValue' => max( $min, $max ),
			'unitCode' => 'DAY',
		];
	}

	/**
	 * Country codes from the comma list.
	 *
	 * @param string $list Comma-separated codes.
	 * @return array<int, string>
	 */
	private static function countries( string $list ): array {
		return array_values( array_filter( explode( ',', $list ) ) );
	}

	/**
	 * Schema.org term for a stored choice, the first term for an unknown one.
	 *
	 * @param array<string, string> $terms  Choice => term.
	 * @param string                $choice Stored choice.
	 * @return string
	 */
	private static function choice( array $terms, string $choice ): string {
		return $terms[ $choice ] ?? reset( $terms );
	}
}
