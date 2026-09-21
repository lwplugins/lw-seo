<?php
/**
 * LLMS.txt cache invalidation.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

use LightweightPlugins\SEO\Options;

/**
 * Decides which WordPress changes make the cached documents stale and
 * drops the cache for those only.
 */
final class Invalidation {

	/**
	 * Hooks after which the documents are always rebuilt.
	 */
	private const INVALIDATING_HOOKS = [
		'deleted_post',
		'trashed_post',
		'untrashed_post',
		'edited_term',
		'update_option_blogname',
		'update_option_blogdescription',
		'update_option_permalink_structure',
		'update_option_home',
		'update_option_siteurl',
	];

	/**
	 * Post meta hooks; only the plugin's own keys change the documents.
	 */
	private const META_HOOKS = [ 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ];

	/**
	 * Flush the cache whenever listed content may have changed.
	 *
	 * @return void
	 */
	public static function register(): void {
		foreach ( self::INVALIDATING_HOOKS as $hook ) {
			add_action( $hook, [ Cache::class, 'flush' ] );
		}

		foreach ( self::META_HOOKS as $hook ) {
			add_action( $hook, [ self::class, 'flush_for_meta' ], 10, 3 );
		}

		add_action( 'save_post', [ self::class, 'flush_for_post' ], 10, 2 );
		add_action( 'transition_post_status', [ self::class, 'flush_for_transition' ], 10, 3 );
	}

	/**
	 * Flush the cache when the plugin settings change. Registered even
	 * while llms.txt is off, so turning it back on never serves a copy
	 * cached before it was turned off.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		add_action( 'update_option_' . Options::OPTION_NAME, [ Cache::class, 'flush' ] );
	}

	/**
	 * Flush after a save that can change the documents.
	 *
	 * @param int   $post_id Post ID.
	 * @param mixed $post    Post object.
	 * @return void
	 */
	public static function flush_for_post( int $post_id, mixed $post ): void {
		if ( $post instanceof \WP_Post && self::should_flush_for_post( $post ) ) {
			Cache::flush();
		}
	}

	/**
	 * Flush when a listed post enters or leaves the published state
	 * (scheduled posts going live, unpublishing, trashing).
	 *
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param mixed  $post       Post object.
	 * @return void
	 */
	public static function flush_for_transition( string $new_status, string $old_status, mixed $post ): void {
		if ( $post instanceof \WP_Post && self::should_flush_for_transition( $new_status, $old_status, $post ) ) {
			Cache::flush();
		}
	}

	/**
	 * Flush when one of the plugin's post meta keys (SEO description,
	 * noindex, content signals) changes.
	 *
	 * @param mixed $meta_id   Meta ID(s).
	 * @param mixed $object_id Post ID.
	 * @param mixed $meta_key  Meta key.
	 * @return void
	 */
	public static function flush_for_meta( mixed $meta_id, mixed $object_id, mixed $meta_key ): void {
		if ( is_string( $meta_key ) && self::is_seo_meta_key( $meta_key ) ) {
			Cache::flush();
		}
	}

	/**
	 * Whether saving this post can change the documents: only published
	 * posts of a listed type are in them. Revisions and autosave revisions
	 * ("inherit"), drafts and auto-drafts are not; a post moving into or
	 * out of "publish" is handled by should_flush_for_transition().
	 *
	 * @param \WP_Post $post Saved post.
	 * @return bool
	 */
	public static function should_flush_for_post( \WP_Post $post ): bool {
		return 'publish' === $post->post_status && self::is_listed_type( (string) $post->post_type );
	}

	/**
	 * Whether a status change adds a listed post to the documents or
	 * removes one. Updates within "publish" go through save_post.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post.
	 * @return bool
	 */
	public static function should_flush_for_transition( string $new_status, string $old_status, \WP_Post $post ): bool {
		return ( 'publish' === $new_status ) !== ( 'publish' === $old_status )
			&& self::is_listed_type( (string) $post->post_type );
	}

	/**
	 * Whether a post meta key is one of the plugin's own.
	 *
	 * @param string $meta_key Meta key.
	 * @return bool
	 */
	public static function is_seo_meta_key( string $meta_key ): bool {
		return str_starts_with( $meta_key, Options::META_PREFIX );
	}

	/**
	 * Whether a post type has a section in llms.txt.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	private static function is_listed_type( string $post_type ): bool {
		return array_key_exists( $post_type, SectionCollector::post_types() );
	}
}
