<?php
/**
 * Post types with SEO editor fields.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Editor;

/**
 * The post types that get the SEO fields: the classic meta box, the block
 * editor panel and the `lw_seo` REST field.
 */
final class EditorPostTypes {

	/**
	 * Public post types except attachment, filtered.
	 *
	 * @return array<int, string>
	 */
	public static function get(): array {
		$post_types = get_post_types( [ 'public' => true ], 'names' );

		unset( $post_types['attachment'] );

		/**
		 * Filter the post types that get the SEO meta box.
		 *
		 * @param array $post_types Array of post type names.
		 */
		return array_values( (array) apply_filters( 'lw_seo_meta_box_post_types', array_values( $post_types ) ) );
	}
}
