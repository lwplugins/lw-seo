<?php
/**
 * ProductGroup variesBy mapping.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

/**
 * Maps WooCommerce attribute slugs to the schema.org properties a variant varies by.
 */
final class VariesBy {

	/**
	 * Attribute slug (sanitized, without `pa_`) => schema.org property name.
	 *
	 * @var array<string, string>
	 */
	private const MAP = [
		'color'    => 'color',
		'colour'   => 'color',
		'szin'     => 'color',
		'size'     => 'size',
		'meret'    => 'size',
		'material' => 'material',
		'anyag'    => 'material',
		'pattern'  => 'pattern',
		'minta'    => 'pattern',
		'age'      => 'suggestedAge',
		'gender'   => 'suggestedGender',
		'nem'      => 'suggestedGender',
	];

	/**
	 * The schema.org variesBy URLs for the given attributes, in order, without repeats.
	 *
	 * @param array<int, string> $attribute_slugs Attribute names (`pa_color`, `attribute_pa_color`, `Size`).
	 * @return array<int, string>
	 */
	public static function for_attributes( array $attribute_slugs ): array {
		$map        = self::map();
		$properties = [];

		foreach ( $attribute_slugs as $slug ) {
			$property = $map[ self::normalize( (string) $slug ) ] ?? null;
			if ( null !== $property ) {
				$properties[] = 'https://schema.org/' . $property;
			}
		}

		return array_values( array_unique( $properties ) );
	}

	/**
	 * The schema.org property name of one attribute, or null when it maps to none.
	 *
	 * @param string $attribute_slug Attribute name (`pa_color`, `attribute_pa_color`, `Size`).
	 * @return string|null
	 */
	public static function property( string $attribute_slug ): ?string {
		return self::map()[ self::normalize( $attribute_slug ) ] ?? null;
	}

	/**
	 * The slug => property map, filtered, with sanitized keys.
	 *
	 * @return array<string, string>
	 */
	private static function map(): array {
		/**
		 * Filters which attribute slugs map to which schema.org property in ProductGroup markup.
		 *
		 * @param mixed $map Attribute slug (without `pa_`) => schema.org property name; a non-array return is ignored.
		 */
		$map = apply_filters( 'lw_seo_woo_varies_by_map', self::MAP );

		$normalized = [];
		foreach ( is_array( $map ) ? $map : [] as $slug => $property ) {
			if ( is_string( $property ) && '' !== $property ) {
				$normalized[ sanitize_title( (string) $slug ) ] = $property;
			}
		}

		return $normalized;
	}

	/**
	 * Strip the `attribute_` and `pa_` prefixes, then sanitize.
	 *
	 * @param string $slug Attribute name.
	 * @return string
	 */
	private static function normalize( string $slug ): string {
		$slug = (string) preg_replace( '/^attribute_/', '', $slug );
		$slug = (string) preg_replace( '/^pa_/', '', $slug );

		return sanitize_title( $slug );
	}
}
