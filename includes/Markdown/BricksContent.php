<?php
/**
 * Bricks Builder content.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Renders the content of a page built with Bricks. Bricks keeps it in its
 * own post meta and renders it from its templates, never through
 * the_content, so post_content of such a page is empty or stale.
 *
 * Follows the Bricks theme's own Rank Math integration
 * (add_bricks_content_for_parse_html_images), which renders another
 * post's Bricks data the same way.
 */
final class BricksContent {

	/**
	 * Rendered HTML of the post's own Bricks content.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string '' when Bricks is not active, the post does not render
	 *                with Bricks, or it has no Bricks content of its own.
	 */
	public static function html( \WP_Post $post ): string {
		if ( ! class_exists( \Bricks\Database::class ) || ! class_exists( \Bricks\Helpers::class ) || ! class_exists( \Bricks\Frontend::class ) ) {
			return '';
		}

		$post_id  = (int) $post->ID;
		$previous = \Bricks\Database::$page_data['preview_or_post_id'] ?? 0;

		// Dynamic data in the elements reads the post from here.
		\Bricks\Database::$page_data['preview_or_post_id'] = $post_id;

		try {
			if ( ! \Bricks\Helpers::render_with_bricks( $post_id ) ) {
				return '';
			}

			$elements = \Bricks\Database::get_data( $post_id, 'content' );

			return [] === $elements ? '' : (string) \Bricks\Frontend::render_data( $elements );
		} finally {
			\Bricks\Database::$page_data['preview_or_post_id'] = $previous;
		}
	}
}
