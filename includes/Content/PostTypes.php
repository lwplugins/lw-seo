<?php
/**
 * Public content types exposed to machine-readable outputs.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Content;

/**
 * Lists the post types and taxonomies the sitemap and llms.txt can expose.
 */
final class PostTypes {

	/**
	 * Post types never exposed (attachments have no page worth listing).
	 */
	private const EXCLUDED_POST_TYPES = [ 'attachment' ];

	/**
	 * Taxonomies never exposed.
	 */
	private const EXCLUDED_TAXONOMIES = [ 'post_format' ];

	/**
	 * Public, viewable post types.
	 *
	 * @return array<string, string> Post type name => plural label.
	 */
	public static function post_types(): array {
		/**
		 * Filtered post types.
		 *
		 * @var array<string, string>
		 */
		$types = [];

		/**
		 * Public post type objects.
		 *
		 * @var array<string, \WP_Post_Type>
		 */
		$items = get_post_types( [ 'public' => true ], 'objects' );

		foreach ( $items as $name => $object ) {
			$name = (string) $name;

			if ( in_array( $name, self::EXCLUDED_POST_TYPES, true ) || ! is_post_type_viewable( $object ) ) {
				continue;
			}
			$types[ $name ] = (string) $object->labels->name;
		}

		return $types;
	}

	/**
	 * Public taxonomies that have archive pages.
	 *
	 * @return array<string, string> Taxonomy name => plural label.
	 */
	public static function taxonomies(): array {
		/**
		 * Filtered taxonomies.
		 *
		 * @var array<string, string>
		 */
		$taxonomies = [];

		/**
		 * Public taxonomy objects.
		 *
		 * @var array<string, object>
		 */
		$items = get_taxonomies( [ 'public' => true ], 'objects' );

		foreach ( $items as $name => $object ) {
			$name = (string) $name;

			if ( in_array( $name, self::EXCLUDED_TAXONOMIES, true ) || ! is_taxonomy_viewable( $object ) ) {
				continue;
			}
			$taxonomies[ $name ] = (string) $object->labels->name;
		}

		return $taxonomies;
	}
}
