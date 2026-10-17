<?php
/**
 * Sanitizer for the shipping and return policy settings.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin;

/**
 * Cleans the country, decimal and day values of the WooCommerce shipping
 * and return policy options.
 */
final class CommerceSanitizer {

	/**
	 * Highest number of days a day field accepts.
	 */
	public const MAX_DAYS = 365;

	/**
	 * Option key => kind of value.
	 */
	private const KEYS = [
		'woo_shipping_countries' => 'countries',
		'woo_return_country'     => 'country',
		'woo_shipping_rate'      => 'decimal',
		'woo_handling_min'       => 'days',
		'woo_handling_max'       => 'days',
		'woo_transit_min'        => 'days',
		'woo_transit_max'        => 'days',
		'woo_return_days'        => 'days',
	];

	/**
	 * Whether the key is sanitized here.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public static function handles( string $key ): bool {
		return isset( self::KEYS[ $key ] );
	}

	/**
	 * Sanitize a value of a key this class handles.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $value   Submitted value, null when absent.
	 * @param mixed  $default Option default.
	 * @return int|string
	 */
	public static function value( string $key, mixed $value, mixed $default ): int|string {
		$kind = self::KEYS[ $key ] ?? '';

		if ( null === $value ) {
			return 'days' === $kind ? (int) $default : (string) ( 'decimal' === $kind ? '' : $default );
		}

		return match ( $kind ) {
			'days'    => self::days( $value ),
			'decimal' => self::decimal( (string) $value ),
			'country' => explode( ',', self::countries( (string) $value ) )[0],
			default   => self::countries( (string) $value ),
		};
	}

	/**
	 * Two-letter country codes: uppercased, split on commas and spaces,
	 * deduplicated, comma-joined.
	 *
	 * @param string $value Raw list.
	 * @return string
	 */
	public static function countries( string $value ): string {
		$codes = preg_split( '/[\s,]+/', strtoupper( $value ), -1, PREG_SPLIT_NO_EMPTY );
		$codes = array_filter( false === $codes ? [] : $codes, static fn( string $code ): bool => 1 === preg_match( '/^[A-Z]{2}$/', $code ) );

		return implode( ',', array_unique( $codes ) );
	}

	/**
	 * A decimal with at most two places; a comma counts as the separator.
	 *
	 * @param string $value Raw number.
	 * @return string The number, or '' when it is not one.
	 */
	public static function decimal( string $value ): string {
		$value = str_replace( ',', '.', trim( $value ) );

		return 1 === preg_match( '/^\d+(\.\d{1,2})?$/', $value ) ? $value : '';
	}

	/**
	 * A whole number of days, 0 to 365.
	 *
	 * @param mixed $value Raw number.
	 * @return int
	 */
	public static function days( mixed $value ): int {
		return min( self::MAX_DAYS, abs( (int) $value ) );
	}
}
