<?php
/**
 * Builds slug-only rewrite rules for product categories.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\WooCommerce;

/**
 * Pure-data helper that turns a {term_id, slug, parent} category set into the
 * RankMath-compatible slug-only rewrite-rule set.
 *
 * Five rules per non-skipped category (root, embed, two feed variants,
 * paginated). Optionally also rewrites product permalinks when the product
 * permalink structure uses %product_cat%.
 */
final class PermalinkRuleBuilder {

	/**
	 * Build rewrite rules for the given category set.
	 *
	 * @param array<int, array{slug: string, parent: int}> $categories      Indexed by term_id.
	 * @param bool                                         $remove_base     Strip /product-category/.
	 * @param bool                                         $remove_parents  Strip parent slugs (expose leaf at root).
	 * @param bool                                         $remove_product_base Strip /product/ when category-aware.
	 * @param array<string>                                $blockers        Lowercase slug list to skip.
	 * @return array{rules: array<string, string>, skipped: array<string>}
	 */
	public function build(
		array $categories,
		bool $remove_base,
		bool $remove_parents,
		bool $remove_product_base,
		array $blockers
	): array {
		global $wp_rewrite;

		$feed         = '(' . trim( implode( '|', (array) $wp_rewrite->feeds ) ) . ')';
		$permalink    = wc_get_permalink_structure();
		$cat_base     = $remove_base ? '' : ( $permalink['category_rewrite_slug'] ?? '' );
		$cat_base     = '' === $cat_base ? '' : trailingslashit( ltrim( $cat_base, '/' ) );
		$use_cat_slug = $remove_product_base && false !== strpos( (string) ( $permalink['product_rewrite_slug'] ?? '' ), '%product_cat%' );

		$rules   = [];
		$skipped = [];

		foreach ( $categories as $category ) {
			$leaf     = $category['slug'];
			$cat_path = $remove_parents ? $leaf : $this->full_path( $category, $categories );
			$key      = strtolower( $cat_base . $cat_path );

			if ( $this->is_blocked( $cat_base, $cat_path, $blockers ) ) {
				$skipped[] = $leaf;
				continue;
			}

			$slug  = urldecode( $cat_base . $cat_path );
			$rules = array_merge(
				$rules,
				$this->category_rules( $slug, $leaf, $wp_rewrite, $feed ),
				$use_cat_slug ? $this->product_rules( urldecode( $cat_path ), $wp_rewrite ) : []
			);

			unset( $key );
		}

		return [
			'rules'   => $rules,
			'skipped' => $skipped,
		];
	}

	/**
	 * Decide whether a category's exposed root slug collides with a blocker.
	 *
	 * Without a category base, the FIRST path component lands at the root —
	 * that's the only one that can shadow another piece of content.
	 *
	 * @param string        $cat_base Trailing-slashed base (empty when removed).
	 * @param string        $cat_path Slash-joined category path.
	 * @param array<string> $blockers Lowercase reserved/page slugs.
	 * @return bool
	 */
	private function is_blocked( string $cat_base, string $cat_path, array $blockers ): bool {
		if ( '' !== $cat_base ) {
			return false;
		}
		$first = strtolower( explode( '/', $cat_path )[0] );
		return in_array( $first, $blockers, true );
	}

	/**
	 * Build the 5 rewrite rules for a single category.
	 *
	 * @param string      $slug       URL slug, already cat_base-prefixed.
	 * @param string      $leaf       Category leaf slug (target for `?product_cat=`).
	 * @param \WP_Rewrite $rewrite  WP rewrite singleton.
	 * @param string      $feed       Feed regex group.
	 * @return array<string, string>
	 */
	private function category_rules( string $slug, string $leaf, \WP_Rewrite $rewrite, string $feed ): array {
		$base = 'index.php?product_cat=' . $leaf;
		return [
			"{$slug}/?\$"                               => $base,
			"{$slug}/embed/?\$"                         => $base . '&embed=true',
			"{$slug}/{$rewrite->feed_base}/{$feed}/?\$" => $base . '&feed=$matches[1]',
			"{$slug}/{$feed}/?\$"                       => $base . '&feed=$matches[1]',
			"{$slug}/{$rewrite->pagination_base}/?([0-9]{1,})/?\$" => $base . '&paged=$matches[1]',
		];
	}

	/**
	 * Build slug-only product rules nested under a category path.
	 *
	 * @param string      $cat_path Full category path (no base, no leading slash).
	 * @param \WP_Rewrite $rewrite  WP rewrite singleton.
	 * @return array<string, string>
	 */
	private function product_rules( string $cat_path, \WP_Rewrite $rewrite ): array {
		return [
			$cat_path . '/([^/]+)/?$' => 'index.php?product=$matches[1]',
			$cat_path . '/([^/]+)/' . $rewrite->comments_pagination_base . '-([0-9]{1,})/?$' => 'index.php?product=$matches[1]&cpage=$matches[2]',
		];
	}

	/**
	 * Recursively build a category's full slug path.
	 *
	 * @param array{slug: string, parent: int}             $category   Target category.
	 * @param array<int, array{slug: string, parent: int}> $categories All categories indexed by term_id.
	 * @return string
	 */
	private function full_path( array $category, array $categories ): string {
		$parent = (int) ( $category['parent'] ?? 0 );
		if ( $parent > 0 && isset( $categories[ $parent ] ) ) {
			return $this->full_path( $categories[ $parent ], $categories ) . '/' . $category['slug'];
		}
		return $category['slug'];
	}
}
