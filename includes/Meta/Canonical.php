<?php
/**
 * Canonical URL hand-off with WordPress core.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Meta;

use LightweightPlugins\SEO\Helpers\MetaCoerce;
use LightweightPlugins\SEO\Options;

/**
 * Keeps WordPress core's canonical in line with the one LW SEO prints.
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
