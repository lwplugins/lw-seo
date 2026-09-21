<?php
/**
 * Shared content eligibility gate.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Content;

use LightweightPlugins\SEO\Options;

/**
 * Decides whether a post may appear in machine-readable outputs
 * (XML sitemap, llms.txt, Markdown endpoint).
 */
final class Eligibility {

	/**
	 * Whether a post type is indexable by its global noindex setting.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public static function is_type_indexable( string $post_type ): bool {
		return ! Options::get( 'noindex_' . $post_type );
	}

	/**
	 * Whether a post may be exposed.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	public static function is_post_eligible( \WP_Post $post ): bool {
		$eligible = 'publish' === $post->post_status
			&& '' === (string) $post->post_password
			&& is_post_type_viewable( $post->post_type )
			&& self::is_type_indexable( (string) $post->post_type )
			&& ! Options::get_post_meta( (int) $post->ID, 'noindex' );

		if ( ! $eligible ) {
			return false;
		}

		/**
		 * Filter whether a post is exposed in the sitemap, llms.txt and the
		 * Markdown endpoint. Only consulted for posts that passed the built-in
		 * checks, so it can remove posts but never add ineligible ones.
		 *
		 * @param bool     $eligible Always true here.
		 * @param \WP_Post $post     The post.
		 */
		return (bool) apply_filters( 'lw_seo_post_is_eligible', true, $post );
	}

	/**
	 * Meta query clause that excludes objects flagged noindex.
	 *
	 * @return array<int|string, mixed>
	 */
	public static function noindex_meta_query(): array {
		return [
			'relation' => 'OR',
			[
				'key'     => Options::META_PREFIX . 'noindex',
				'compare' => 'NOT EXISTS',
			],
			[
				'key'     => Options::META_PREFIX . 'noindex',
				'value'   => '1',
				'compare' => '!=',
			],
		];
	}
}
