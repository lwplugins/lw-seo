<?php
/**
 * Post content source.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Markdown;

/**
 * Rendered HTML of a post's main content, taken from where the site
 * actually renders it.
 */
final class ContentSource {

	/**
	 * Bricks content for a Bricks page, otherwise post_content through
	 * the_content (blocks, shortcodes, and builders that hook it, such as
	 * Elementor).
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public static function html( \WP_Post $post ): string {
		$html = BricksContent::html( $post );
		if ( '' !== $html ) {
			return $html;
		}

		return (string) apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP filter.
	}
}
