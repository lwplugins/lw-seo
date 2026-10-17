<?php
/**
 * Hidden and out-of-stock products in the sitemap and robots meta.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Integrations\WooCommerce;

use LightweightPlugins\SEO\Options;

/**
 * Keeps products with catalog visibility "hidden" (and, optionally, out of
 * stock ones) out of the sitemap, llms.txt and Markdown, and noindexes
 * hidden ones. Reads the product_visibility terms and the stock meta
 * directly, without loading WC_Product.
 */
final class CatalogVisibility {

	/**
	 * Register the filters.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'lw_seo_post_is_eligible', [ $this, 'filter_eligible' ], 10, 2 );
		add_filter( 'lw_seo_post_noindex', [ $this, 'filter_noindex' ], 10, 2 );
	}

	/**
	 * Drop hidden (and optionally out-of-stock) products from the exposed posts.
	 *
	 * @param mixed    $eligible Whether the post is exposed so far.
	 * @param \WP_Post $post     The post.
	 * @return bool
	 */
	public function filter_eligible( $eligible, \WP_Post $post ): bool {
		if ( ! $eligible || 'product' !== $post->post_type ) {
			return (bool) $eligible;
		}

		if ( Options::get( 'woo_sitemap_exclude_hidden', true ) && $this->is_hidden( $post ) ) {
			return false;
		}

		return ! ( Options::get( 'woo_sitemap_exclude_outofstock', false ) && $this->is_out_of_stock( $post ) );
	}

	/**
	 * Noindex hidden products.
	 *
	 * @param mixed    $noindex Whether the post is noindex so far.
	 * @param \WP_Post $post    The post.
	 * @return bool
	 */
	public function filter_noindex( $noindex, \WP_Post $post ): bool {
		if ( $noindex ) {
			return true;
		}

		return 'product' === $post->post_type
			&& Options::get( 'woo_noindex_hidden', true )
			&& $this->is_hidden( $post );
	}

	/**
	 * Catalog visibility "hidden": excluded from both the catalog and search.
	 *
	 * @param \WP_Post $post The product post.
	 * @return bool
	 */
	private function is_hidden( \WP_Post $post ): bool {
		return has_term( 'exclude-from-catalog', 'product_visibility', $post )
			&& has_term( 'exclude-from-search', 'product_visibility', $post );
	}

	/**
	 * Stock status "outofstock", from the _stock_status meta.
	 *
	 * @param \WP_Post $post The product post.
	 * @return bool
	 */
	private function is_out_of_stock( \WP_Post $post ): bool {
		return 'outofstock' === get_post_meta( $post->ID, '_stock_status', true );
	}
}
