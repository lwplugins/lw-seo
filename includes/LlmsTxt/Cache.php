<?php
/**
 * LLMS.txt cache.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\LlmsTxt;

use LightweightPlugins\SEO\Options;

/**
 * Transient cache for the generated files, cleared on content changes.
 */
final class Cache {

	/**
	 * Transient key prefix.
	 */
	private const PREFIX = 'lw_seo_llms_';

	/**
	 * Cache lifetime in seconds (one day).
	 */
	private const TTL = 86400;

	/**
	 * Cached documents.
	 */
	private const KEYS = [ 'index', 'full' ];

	/**
	 * Hooks after which the documents are rebuilt.
	 */
	private const INVALIDATING_HOOKS = [
		'save_post',
		'deleted_post',
		'trashed_post',
		'untrashed_post',
		'edited_term',
		'update_option_blogname',
		'update_option_blogdescription',
		'update_option_permalink_structure',
		'update_option_' . Options::OPTION_NAME,
	];

	/**
	 * Get a cached document or build and store it.
	 *
	 * @param string   $key   Document key (index|full).
	 * @param callable $build Builder returning the document.
	 * @return string
	 */
	public static function remember( string $key, callable $build ): string {
		$cached = get_transient( self::PREFIX . $key );
		if ( is_string( $cached ) ) {
			return $cached;
		}

		$content = self::build_as_visitor( $build );
		set_transient( self::PREFIX . $key, $content, self::TTL );

		return $content;
	}

	/**
	 * Run the builder as a logged-out visitor. The result is cached and
	 * served to everyone, so it must never carry what the_content,
	 * shortcodes or membership plugins show the requesting user (often an
	 * admin opening the link from the settings page). The requester is
	 * restored even when the builder throws.
	 *
	 * @param callable $build Builder returning the document.
	 * @return string
	 */
	private static function build_as_visitor( callable $build ): string {
		$user_id = get_current_user_id();
		wp_set_current_user( 0 );

		try {
			return (string) $build();
		} finally {
			wp_set_current_user( $user_id );
		}
	}

	/**
	 * Drop every cached document.
	 *
	 * @return void
	 */
	public static function flush(): void {
		foreach ( self::KEYS as $key ) {
			delete_transient( self::PREFIX . $key );
		}
	}

	/**
	 * Flush the cache whenever listed content may have changed.
	 *
	 * @return void
	 */
	public static function register_invalidation(): void {
		foreach ( self::INVALIDATING_HOOKS as $hook ) {
			add_action( $hook, [ self::class, 'flush' ] );
		}
	}
}
