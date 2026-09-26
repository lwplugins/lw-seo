<?php
/**
 * Canonical URL resolution and hand-off with WordPress core.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * Resolves the canonical URL LW SEO prints, runs it through the public
 * `lw_seo_canonical_url` filter, and keeps WordPress core's canonical in line.
 *
 * Core prints its own `<link rel="canonical">` on singular views
 * (`rel_canonical()` on `wp_head`). It is removed only at the moment LW SEO
 * prints its tag, so a view LW SEO skips keeps core's tag instead of ending
 * up with none.
 */
final class Canonical {

	/**
	 * Register the core canonical URL filter.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'get_canonical_url', [ $this, 'filter_core_url' ], 10, 2 );
	}

	/**
	 * Make wp_get_canonical_url() return the custom canonical set in the
	 * LW SEO meta box, so everything reading core's value agrees with the tag.
	 *
	 * Callbacks earlier on the filter may hand over anything, so both
	 * arguments are checked before use.
	 *
	 * @param mixed $url  Canonical URL from core or an earlier callback.
	 * @param mixed $post The post core passes.
	 * @return mixed
	 */
	public function filter_core_url( mixed $url, mixed $post ): mixed {
		if ( ! $post instanceof \WP_Post || HeadMeta::is_conflicting_plugin_active() ) {
			return $url;
		}

		$custom = self::custom_for_post( (int) $post->ID );

		return '' !== $custom ? $custom : $url;
	}

	/**
	 * Canonical URL of a singular view: the custom canonical, else core's
	 * (which adds the page of a paginated post and the comment page), else
	 * the permalink (core returns none for an unpublished post).
	 *
	 * @param \WP_Post $post The post.
	 * @return string
	 */
	public static function for_post( \WP_Post $post ): string {
		$custom = self::custom_for_post( (int) $post->ID );

		if ( '' !== $custom ) {
			return $custom;
		}

		$core = wp_get_canonical_url( $post );

		return is_string( $core ) && '' !== $core ? $core : (string) get_permalink( $post );
	}

	/**
	 * Run the canonical URL of the current view through the public filter.
	 *
	 * @param string $url    Canonical URL LW SEO resolved.
	 * @param mixed  $object Queried object (WP_Post, WP_Term, WP_User, WP_Post_Type) or null.
	 * @return string The URL to print; '' prints no canonical tag.
	 */
	public static function filter( string $url, mixed $object ): string {
		/**
		 * Filter the canonical URL LW SEO prints; og:url follows it.
		 *
		 * Return an empty string to print no canonical tag: og:url then keeps
		 * the unfiltered URL, and WordPress core's own tag stays on singular views.
		 *
		 * @since 1.6.2
		 *
		 * @param string $url    Canonical URL.
		 * @param mixed  $object Queried object, or null (e.g. the front page).
		 */
		return (string) apply_filters( 'lw_seo_canonical_url', $url, $object );
	}

	/**
	 * The custom canonical URL saved for a post, or '' when none is set.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function custom_for_post( int $post_id ): string {
		return MetaCoerce::as_string( Options::get_post_meta( $post_id, 'canonical' ) );
	}

	/**
	 * LW SEO printed its canonical: drop core's rel_canonical() from the
	 * running wp_head (it sits at priority 10, after LW SEO's priority 1).
	 *
	 * @return void
	 */
	public static function replace_core_tag(): void {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
