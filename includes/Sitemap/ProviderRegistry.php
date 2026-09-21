<?php
/**
 * Sitemap provider registry.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

use LightweightPlugins\SEO\Content\PostTypes;
use LightweightPlugins\SEO\Options;

/**
 * Builds one sitemap provider per enabled post type and taxonomy.
 */
final class ProviderRegistry {

	/**
	 * Legacy boolean option per built-in post type.
	 */
	public const POST_TYPE_TOGGLES = [
		'post'    => 'sitemap_posts',
		'page'    => 'sitemap_pages',
		'product' => 'sitemap_products',
	];

	/**
	 * Legacy boolean option per built-in taxonomy.
	 */
	public const TAXONOMY_TOGGLES = [
		'category'    => 'sitemap_categories',
		'post_tag'    => 'sitemap_tags',
		'product_cat' => 'sitemap_product_cat',
		'product_tag' => 'sitemap_product_tag',
	];

	/**
	 * Objects that also require the WooCommerce integration to be on.
	 */
	private const WOO_OBJECTS = [ 'product', 'product_cat', 'product_tag' ];

	/**
	 * Whether a post type belongs in the sitemap.
	 *
	 * Custom post types are included unless switched off.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public static function post_type_enabled( string $post_type ): bool {
		return self::enabled( $post_type, self::POST_TYPE_TOGGLES, 'sitemap_post_types', true );
	}

	/**
	 * Whether a taxonomy belongs in the sitemap.
	 *
	 * Custom taxonomies are opt-in.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 */
	public static function taxonomy_enabled( string $taxonomy ): bool {
		return self::enabled( $taxonomy, self::TAXONOMY_TOGGLES, 'sitemap_taxonomies', false );
	}

	/**
	 * Build the providers for the current request.
	 *
	 * @return array<string, ProviderInterface> Sitemap name => provider.
	 */
	public static function build(): array {
		$post_types = array_values( array_filter( array_keys( PostTypes::post_types() ), [ self::class, 'post_type_enabled' ] ) );

		/**
		 * Filter post types included in the sitemap.
		 *
		 * @param array<int, string> $post_types Post type names.
		 */
		$post_types = (array) apply_filters( 'lw_seo_sitemap_post_types', $post_types );

		$providers = [];
		foreach ( $post_types as $post_type ) {
			$providers[ (string) $post_type ] = new PostProvider( (string) $post_type );
		}

		foreach ( array_keys( PostTypes::taxonomies() ) as $taxonomy ) {
			if ( ! isset( $providers[ $taxonomy ] ) && self::taxonomy_enabled( $taxonomy ) ) {
				$providers[ $taxonomy ] = new TaxonomyProvider( $taxonomy );
			}
		}

		return $providers;
	}

	/**
	 * Resolve an object's toggle.
	 *
	 * @param string                $name        Object name.
	 * @param array<string, string> $legacy      Legacy option key per built-in object.
	 * @param string                $map_option  Option holding the name => bool map.
	 * @param bool                  $map_default State for names missing from the map.
	 * @return bool
	 */
	private static function enabled( string $name, array $legacy, string $map_option, bool $map_default ): bool {
		if ( in_array( $name, self::WOO_OBJECTS, true ) && ! Options::get( 'woo_enabled' ) ) {
			return false;
		}

		if ( isset( $legacy[ $name ] ) ) {
			return (bool) Options::get( $legacy[ $name ] );
		}

		$map = Options::get( $map_option );

		return is_array( $map ) && array_key_exists( $name, $map ) ? (bool) $map[ $name ] : $map_default;
	}
}
