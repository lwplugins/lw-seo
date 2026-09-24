<?php
/**
 * Flush rewrites when a WooCommerce permalink flag changes.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Soft-flushes rewrites and clears the slug-blocker cache whenever one of
 * the three WooCommerce permalink flags changes, from any save path (the
 * settings REST API, WP-CLI, migrators).
 */
final class PermalinkFlagFlusher {

	/**
	 * The permalink flags.
	 */
	private const KEYS = [ 'wc_remove_category_base', 'wc_remove_category_parent_slugs', 'wc_remove_product_base' ];

	/**
	 * Hook the option update.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'update_option_' . Options::OPTION_NAME, [ $this, 'flush_on_permalink_change' ], 10, 2 );
	}

	/**
	 * Flush when any flag changed.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 * @return void
	 */
	public function flush_on_permalink_change( $old_value, $new_value ): void {
		foreach ( self::KEYS as $key ) {
			$old = is_array( $old_value ) ? ! empty( $old_value[ $key ] ) : false;
			$new = is_array( $new_value ) ? ! empty( $new_value[ $key ] ) : false;
			if ( $old !== $new ) {
				SlugCollisionDetector::invalidate();
				flush_rewrite_rules( false );
				return;
			}
		}
	}
}
