<?php
/**
 * Query-context helpers for archive head meta.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

/**
 * Resolves the URLs and IDs the head meta needs on non-singular views.
 */
final class ArchiveContext {

	/**
	 * Whether the current view is the blog posts page (a static front page's
	 * separate posts page), not the front page itself.
	 *
	 * @return bool
	 */
	public static function is_posts_page(): bool {
		return is_home() && ! is_front_page() && self::posts_page_id() > 0;
	}

	/**
	 * ID of the page assigned as the posts page.
	 *
	 * @return int
	 */
	public static function posts_page_id(): int {
		return (int) get_option( 'page_for_posts' );
	}

	/**
	 * Archive link of the queried post type archive.
	 *
	 * @return string Empty string when the post type cannot be resolved or has no archive.
	 */
	public static function post_type_archive_link(): string {
		$post_type = get_query_var( 'post_type' );

		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		if ( ! is_string( $post_type ) || '' === $post_type ) {
			$queried   = get_queried_object();
			$post_type = $queried instanceof \WP_Post_Type ? $queried->name : '';
		}

		if ( '' === $post_type ) {
			return '';
		}

		$link = get_post_type_archive_link( $post_type );

		return is_string( $link ) ? $link : '';
	}

	/**
	 * Point a paged archive's canonical at itself instead of page one.
	 *
	 * @param string $base The unpaged URL.
	 * @return string
	 */
	public static function paged_url( string $base ): string {
		$paged = (int) get_query_var( 'paged' );

		if ( $paged < 2 || '' === $base ) {
			return $base;
		}

		global $wp_rewrite;

		if ( ! $wp_rewrite instanceof \WP_Rewrite || ! $wp_rewrite->using_permalinks() ) {
			return add_query_arg( 'paged', $paged, $base );
		}

		return self::append_pagination( $base, $wp_rewrite->pagination_base, $paged );
	}

	/**
	 * Append a pretty pagination segment to a URL, keeping any query string.
	 *
	 * @param string $base            The unpaged URL.
	 * @param string $pagination_base The rewrite pagination base (usually "page").
	 * @param int    $paged           Current page number, 2 or higher.
	 * @return string
	 */
	public static function append_pagination( string $base, string $pagination_base, int $paged ): string {
		$query    = '';
		$fragment = strpos( $base, '?' );

		if ( false !== $fragment ) {
			$query = substr( $base, $fragment );
			$base  = substr( $base, 0, $fragment );
		}

		return trailingslashit( $base ) . $pagination_base . '/' . $paged . '/' . $query;
	}
}
