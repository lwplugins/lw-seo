<?php
/**
 * Posts left out of the XML sitemap by ID.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Sitemap;

/**
 * IDs of posts that pass the eligibility checks but must not be listed,
 * because something other than LW SEO marks them noindex.
 */
final class ExcludedPosts {

	/**
	 * Post IDs to leave out of a post type's sitemap.
	 *
	 * @param string $post_type Post type of the sitemap being built.
	 * @return array<int, int>
	 */
	public static function ids( string $post_type ): array {
		/**
		 * Filter the IDs of posts left out of the XML sitemap.
		 *
		 * Holds the WooCommerce cart, checkout and my account pages when
		 * WooCommerce is active; add IDs to leave more posts out.
		 *
		 * @since 1.6.2
		 *
		 * @param int[]  $ids       Post IDs.
		 * @param string $post_type Post type of the sitemap being built.
		 */
		$ids = (array) apply_filters( 'lw_seo_sitemap_excluded_ids', [], $post_type );

		return array_values( array_filter( array_map( 'intval', $ids ), static fn( int $id ): bool => $id > 0 ) );
	}
}
