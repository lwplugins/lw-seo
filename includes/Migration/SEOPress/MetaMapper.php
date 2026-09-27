<?php
/**
 * Maps SEOPress post / term meta to LW SEO fields.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\SEOPress;

/**
 * Pure mapping of one object's `_seopress_*` meta values to LW SEO fields.
 *
 * The first non-empty source per target wins (Facebook before Twitter).
 * Robots: only the exact string 'yes' in `_seopress_robots_index` /
 * `_seopress_robots_follow` means noindex / nofollow, as in SEOPress
 * (inc/functions/options-titles-metas.php:783-804, 853-855).
 */
final class MetaMapper {

	/**
	 * Variable converter.
	 *
	 * @var VariableConverter
	 */
	private VariableConverter $variables;

	/**
	 * Constructor.
	 *
	 * @param VariableConverter $variables Variable converter.
	 */
	public function __construct( VariableConverter $variables ) {
		$this->variables = $variables;
	}

	/**
	 * Meta keys the mapper reads.
	 *
	 * @return array<string>
	 */
	public static function keys(): array {
		return array_merge( array_keys( Mappings::META_MAP ), [ '_seopress_robots_index', '_seopress_robots_follow' ] );
	}

	/**
	 * LW SEO fields (without prefix) for one object.
	 *
	 * @param array<string, mixed> $meta Meta key => stored value.
	 * @param bool                 $term Whether the object is a term.
	 * @return array<string, string>
	 */
	public function fields( array $meta, bool $term = false ): array {
		$fields = [];

		foreach ( Mappings::META_MAP as $key => $field ) {
			if ( isset( $fields[ $field ] ) && '' !== $fields[ $field ] ) {
				continue;
			}
			$fields[ $field ] = $this->value( $field, $meta[ $key ] ?? '' );
		}

		$fields['noindex']  = 'yes' === ( $meta['_seopress_robots_index'] ?? '' ) ? '1' : '';
		$fields['nofollow'] = 'yes' === ( $meta['_seopress_robots_follow'] ?? '' ) ? '1' : '';

		if ( $term ) {
			$fields = array_intersect_key( $fields, array_flip( Mappings::TERM_FIELDS ) );
		}

		return $fields;
	}

	/**
	 * One converted value.
	 *
	 * @param string $field LW SEO field.
	 * @param mixed  $value Stored value.
	 * @return string Converted value, '' for anything that is not a string.
	 */
	private function value( string $field, mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return in_array( $field, Mappings::TEMPLATE_FIELDS, true ) ? $this->variables->convert( $value ) : trim( $value );
	}
}
