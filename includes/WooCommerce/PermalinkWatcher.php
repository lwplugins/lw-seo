<?php
/**
 * Slug-only WooCommerce permalink watcher.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Implements RankMath-compatible slug-only category & product permalinks with
 * an upfront slug-collision check that RankMath itself doesn't perform.
 *
 * The 3 options live on the Woo settings tab:
 *   - wc_remove_category_base         strips /product-category/
 *   - wc_remove_category_parent_slugs exposes leaf-only category URLs
 *   - wc_remove_product_base          strips /product/
 *
 * Categories whose root-exposed slug collides with a page / reserved slug /
 * CPT base are silently SKIPPED — they keep their default URL — and surfaced
 * in the `lw_seo_skipped_permalinks` option for the admin notice.
 */
final class PermalinkWatcher {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! $this->any_enabled() ) {
			return;
		}

		if ( Options::get( 'wc_remove_product_base', false ) ) {
			add_filter( 'post_type_link', [ $this, 'filter_product_link' ], 1, 2 );
		}

		if ( Options::get( 'wc_remove_category_base', false ) || Options::get( 'wc_remove_category_parent_slugs', false ) ) {
			add_action( 'created_product_cat', [ $this, 'soft_flush' ] );
			add_action( 'edited_product_cat', [ $this, 'soft_flush' ] );
			add_action( 'delete_product_cat', [ $this, 'soft_flush' ] );
			add_filter( 'term_link', [ $this, 'filter_term_link' ], 0, 3 );
		}

		// Page CRUD affects the blocker set — invalidate so a newly created
		// page can claim back its slug from a now-skipped category.
		add_action( 'save_post_page', [ $this, 'soft_flush' ] );
		add_action( 'deleted_post', [ $this, 'soft_flush' ] );

		add_filter( 'rewrite_rules_array', [ $this, 'inject_rules' ], 99 );
	}

	/**
	 * Whether any of the three permalink flags are turned on.
	 *
	 * @return bool
	 */
	private function any_enabled(): bool {
		return (bool) Options::get( 'wc_remove_category_base', false )
			|| (bool) Options::get( 'wc_remove_category_parent_slugs', false )
			|| (bool) Options::get( 'wc_remove_product_base', false );
	}

	/**
	 * Soft-flush rewrite rules and clear the slug-blocker cache.
	 *
	 * @return void
	 */
	public function soft_flush(): void {
		SlugCollisionDetector::invalidate();
		flush_rewrite_rules( false );
	}

	/**
	 * Strip the product base from a product permalink.
	 *
	 * @param string   $permalink Existing permalink URL.
	 * @param \WP_Post $post      Post object.
	 * @return string
	 */
	public function filter_product_link( string $permalink, \WP_Post $post ): string {
		if ( 'product' !== $post->post_type || ! get_option( 'permalink_structure', '' ) ) {
			return $permalink;
		}
		$permalink_structure = wc_get_permalink_structure();
		$base                = (string) ( $permalink_structure['product_rewrite_slug'] ?? '' );
		$base                = str_replace( '%product_cat%', '', $base );
		$base                = '/' . trim( $base, '/' ) . '/';
		return str_replace( $base, '/', $permalink );
	}

	/**
	 * Strip the category base / parent slugs from a category permalink.
	 *
	 * @param string   $link     Term link URL.
	 * @param \WP_Term $term     Term object.
	 * @param string   $taxonomy Taxonomy slug.
	 * @return string
	 */
	public function filter_term_link( string $link, \WP_Term $term, string $taxonomy ): string {
		if ( 'product_cat' !== $taxonomy ) {
			return $link;
		}

		$blockers = ( new SlugCollisionDetector() )->get_blockers();
		if ( in_array( strtolower( $term->slug ), $blockers, true ) ) {
			return $link;
		}

		$permalink_structure = wc_get_permalink_structure();
		$category_base       = trailingslashit( (string) ( $permalink_structure['category_rewrite_slug'] ?? '' ) );

		if ( Options::get( 'wc_remove_category_base', false ) ) {
			$link          = str_replace( $category_base, '', $link );
			$category_base = '';
		}

		if ( Options::get( 'wc_remove_category_parent_slugs', false ) ) {
			$link = home_url( user_trailingslashit( $category_base . $term->slug ) );
		}

		return $link;
	}

	/**
	 * Inject slug-only rewrite rules ahead of WC's defaults.
	 *
	 * @param array<string, string> $rules Existing rules.
	 * @return array<string, string>
	 */
	public function inject_rules( array $rules ): array {
		$store      = new PermalinkStore();
		$categories = $store->fetch_categories();
		if ( empty( $categories ) ) {
			return $rules;
		}

		$result = ( new PermalinkRuleBuilder() )->build(
			$categories,
			(bool) Options::get( 'wc_remove_category_base', false ),
			(bool) Options::get( 'wc_remove_category_parent_slugs', false ),
			(bool) Options::get( 'wc_remove_product_base', false ),
			( new SlugCollisionDetector() )->get_blockers()
		);

		$store->persist_skipped( $result['skipped'] );

		return $result['rules'] + $rules;
	}

	/**
	 * Read the persisted skipped-categories list (proxy to PermalinkStore).
	 *
	 * @return array<string>
	 */
	public static function get_skipped(): array {
		return PermalinkStore::get_skipped();
	}
}
