<?php
/**
 * Slug collision detection for slug-only WooCommerce permalinks.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

/**
 * Builds the set of root-level slugs that would shadow another piece of content.
 *
 * Slug-only product / category permalinks register rewrite rules at the very
 * top of the rules array, so they win over any other rule with the same
 * path. To avoid silently breaking pages, CPT archives, taxonomy bases or
 * WordPress endpoints, we skip categories/products whose root-level slug
 * already belongs to one of those. The collision list is cached in a
 * transient and invalidated on page/option changes.
 */
final class SlugCollisionDetector {

	private const TRANSIENT_KEY = 'lw_seo_permalink_blockers';
	private const TTL           = 12 * HOUR_IN_SECONDS;

	/**
	 * WP / known reserved slugs that always block.
	 */
	private const RESERVED = [
		'feed',
		'embed',
		'page',
		'comments',
		'comment-page',
		'attachment',
		'trackback',
		'wp-json',
		'wp-admin',
		'wp-content',
		'wp-includes',
		'wp-login.php',
		'xmlrpc.php',
	];

	/**
	 * Get the cached set of blocking root slugs.
	 *
	 * @return array<string>
	 */
	public function get_blockers(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$blockers = $this->build();
		set_transient( self::TRANSIENT_KEY, $blockers, self::TTL );

		return $blockers;
	}

	/**
	 * Invalidate the cache. Call from page CRUD + option save hooks.
	 *
	 * @return void
	 */
	public static function invalidate(): void {
		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Check if a category leaf slug would collide with existing content.
	 *
	 * @param string $slug Category slug (leaf form, no parents).
	 * @return bool True if collision; the caller should skip this category.
	 */
	public function collides( string $slug ): bool {
		return in_array( strtolower( $slug ), $this->get_blockers(), true );
	}

	/**
	 * Build the blocker set from scratch.
	 *
	 * @return array<string>
	 */
	private function build(): array {
		$blockers = array_merge(
			self::RESERVED,
			$this->root_page_slugs(),
			$this->woo_special_slugs(),
			$this->taxonomy_and_cpt_bases(),
			$this->root_post_slugs_if_postname_permalink()
		);

		$blockers = array_map( 'strtolower', $blockers );
		$blockers = array_filter( $blockers );
		return array_values( array_unique( $blockers ) );
	}

	/**
	 * Slugs of published pages that live at root (post_parent = 0).
	 *
	 * @return array<string>
	 */
	private function root_page_slugs(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached via transient; required for accuracy.
		return (array) $wpdb->get_col(
			"SELECT post_name FROM {$wpdb->posts}
			 WHERE post_type = 'page'
			 AND post_status = 'publish'
			 AND post_parent = 0"
		);
	}

	/**
	 * Slugs of published posts at root, but only when permalink structure
	 * exposes posts at root (e.g. /%postname%/ or /%category%/%postname%/
	 * where the post itself lives one slug deep). Anything else and posts
	 * cannot collide.
	 *
	 * @return array<string>
	 */
	private function root_post_slugs_if_postname_permalink(): array {
		$structure = (string) get_option( 'permalink_structure', '' );
		if ( '/%postname%/' !== $structure && '/%postname%' !== $structure ) {
			return [];
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached via transient.
		return (array) $wpdb->get_col(
			"SELECT post_name FROM {$wpdb->posts}
			 WHERE post_type = 'post'
			 AND post_status = 'publish'"
		);
	}

	/**
	 * Slugs of WooCommerce's reserved pages (shop / cart / checkout / etc.).
	 *
	 * @return array<string>
	 */
	private function woo_special_slugs(): array {
		$slugs    = [];
		$page_ids = [
			(int) get_option( 'woocommerce_shop_page_id', 0 ),
			(int) get_option( 'woocommerce_cart_page_id', 0 ),
			(int) get_option( 'woocommerce_checkout_page_id', 0 ),
			(int) get_option( 'woocommerce_myaccount_page_id', 0 ),
			(int) get_option( 'woocommerce_terms_page_id', 0 ),
		];

		foreach ( array_filter( $page_ids ) as $page_id ) {
			$slug = get_post_field( 'post_name', $page_id );
			if ( is_string( $slug ) && '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		return $slugs;
	}

	/**
	 * Rewrite bases of registered taxonomies and public CPTs.
	 *
	 * @return array<string>
	 */
	private function taxonomy_and_cpt_bases(): array {
		$bases = [];

		foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $taxonomy ) {
			if ( ! empty( $taxonomy->rewrite['slug'] ) ) {
				$bases[] = explode( '/', (string) $taxonomy->rewrite['slug'] )[0];
			}
		}

		foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $post_type ) {
			if ( ! empty( $post_type->rewrite['slug'] ) ) {
				$bases[] = explode( '/', (string) $post_type->rewrite['slug'] )[0];
			}
		}

		return $bases;
	}
}
