<?php
/**
 * Persistent helpers for the permalink watcher.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

/**
 * Thin storage adapter: fetches product_cat terms in a shape the rule builder
 * understands, and persists the skipped-categories list for the WC settings
 * admin notice.
 */
final class PermalinkStore {

	private const SKIPPED_OPTION = 'lw_seo_skipped_permalinks';

	/**
	 * Fetch all product_cat terms as [term_id => [slug, parent]].
	 *
	 * @return array<int, array{slug: string, parent: int}>
	 */
	public function fetch_categories(): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);

		if ( ! is_array( $terms ) ) {
			return [];
		}

		$indexed = [];
		foreach ( $terms as $term ) {
			$indexed[ (int) $term->term_id ] = [
				'slug'   => (string) $term->slug,
				'parent' => (int) $term->parent,
			];
		}
		return $indexed;
	}

	/**
	 * Persist the skipped-categories list (only writes if it actually changed).
	 *
	 * @param array<string> $skipped Leaf slugs of skipped categories.
	 * @return void
	 */
	public function persist_skipped( array $skipped ): void {
		$skipped = array_values( array_unique( $skipped ) );
		if ( get_option( self::SKIPPED_OPTION ) !== $skipped ) {
			update_option( self::SKIPPED_OPTION, $skipped, false );
		}
	}

	/**
	 * Read the persisted skipped-categories list.
	 *
	 * @return array<string>
	 */
	public static function get_skipped(): array {
		$value = get_option( self::SKIPPED_OPTION, [] );
		return is_array( $value ) ? $value : [];
	}
}
