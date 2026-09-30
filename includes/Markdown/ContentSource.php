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
	 * HTML a builder supplies (Bricks keeps its content outside
	 * post_content), otherwise post_content through the_content (blocks,
	 * shortcodes, and builders that hook it, such as Elementor).
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	public static function html( \WP_Post $post ): string {
		/**
		 * HTML of a post's main content from a source other than
		 * post_content, such as a page builder's own data ('' = none).
		 *
		 * @internal Used by the Bricks integration; not a public API yet.
		 *
		 * @param mixed    $html '' by default; anything but a non-empty string is ignored.
		 * @param \WP_Post $post Post object.
		 */
		$html = apply_filters( 'lw_seo_markdown_source_html', '', $post );

		if ( is_string( $html ) && '' !== $html ) {
			return $html;
		}

		return (string) apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WP filter.
	}
}
